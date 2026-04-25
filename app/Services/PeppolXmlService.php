<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\InvoiceType;
use App\Models\Invoice;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

final class PeppolXmlService
{
    private const CUSTOMIZATION_ID = 'urn:cen.eu:en16931:2017#compliant#urn:fdc:peppol.eu:2017:poacc:billing:3.0';

    private const PROFILE_ID = 'urn:fdc:peppol.eu:2017:poacc:billing:01:1.0';

    private const CURRENCY = 'EUR';

    private const UBL_INVOICE_NS = 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2';

    private const CAC_NS = 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2';

    private const CBC_NS = 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2';

    /** Line item description used in all generated PEPPOL XML documents. */
    private const LINE_DESCRIPTION = 'Coaching services';

    /**
     * Generate a PEPPOL BIS 3.0 compliant UBL XML string for the given invoice or credit note.
     *
     * The generated XML is stored to the local filesystem at
     * `invoices/{year}/{invoice_number}.xml` and the invoice's `xml_path`
     * column is updated accordingly.
     *
     * @param  Invoice  $invoice  The invoice (or credit note) to serialise.
     * @return string The generated XML string.
     */
    public function generate(Invoice $invoice): string
    {
        $xml = $this->buildXml($invoice);

        $year = $invoice->billing_period_start->format('Y');
        $path = "invoices/{$year}/{$invoice->invoice_number}.xml";

        Storage::put($path, $xml);

        $invoice->xml_path = $path;
        $invoice->save();

        return $xml;
    }

    /**
     * Validate the given XML string against PEPPOL BIS 3.0 structural rules.
     *
     * Checks that the XML is well-formed, that all mandatory UBL elements are
     * present, and that key values conform to the PEPPOL BIS 3.0 specification.
     * Validation errors are written to the application log.
     *
     * @param  string  $xml  The raw XML string to validate.
     * @return bool True when the document passes all checks.
     */
    public function validate(string $xml): bool
    {
        $dom = new DOMDocument('1.0', 'UTF-8');

        if ($xml === '' || ! @$dom->loadXML($xml)) {
            Log::error('PEPPOL XML validation failed: document is not well-formed');

            return false;
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('ubl', self::UBL_INVOICE_NS);
        $xpath->registerNamespace('cbc', self::CBC_NS);
        $xpath->registerNamespace('cac', self::CAC_NS);

        $errors = [];

        // Required elements per PEPPOL BIS 3.0 §5.
        // /ubl:Invoice/cbc:ID specifically targets the document-level ID,
        // distinguishing it from IDs inside TaxCategory or TaxScheme.
        $required = [
            '//cbc:CustomizationID' => 'CustomizationID',
            '//cbc:ProfileID' => 'ProfileID',
            '/ubl:Invoice/cbc:ID' => 'ID',
            '//cbc:IssueDate' => 'IssueDate',
            '//cbc:InvoiceTypeCode' => 'InvoiceTypeCode',
            '//cbc:DocumentCurrencyCode' => 'DocumentCurrencyCode',
            '//cac:AccountingSupplierParty' => 'AccountingSupplierParty',
            '//cac:AccountingCustomerParty' => 'AccountingCustomerParty',
            '//cac:TaxTotal' => 'TaxTotal',
            '//cac:LegalMonetaryTotal' => 'LegalMonetaryTotal',
        ];

        foreach ($required as $expr => $label) {
            $nodes = $xpath->query($expr);

            if ($nodes === false || $nodes->length === 0) {
                $errors[] = "Missing required element: {$label}";
            }
        }

        // At least one InvoiceLine is mandatory
        $lines = $xpath->query('//cac:InvoiceLine');

        if ($lines === false || $lines->length === 0) {
            $errors[] = 'Missing required element: InvoiceLine';
        }

        // CustomizationID must match the PEPPOL BIS 3.0 value
        $customizationNodes = $xpath->query('//cbc:CustomizationID');

        if ($customizationNodes !== false && $customizationNodes->length > 0) {
            $value = $customizationNodes->item(0)?->textContent ?? '';

            if ($value !== self::CUSTOMIZATION_ID) {
                $errors[] = "Invalid CustomizationID: {$value}";
            }
        }

        // InvoiceTypeCode must be 380 (invoice) or 381 (credit note)
        $typeCodeNodes = $xpath->query('//cbc:InvoiceTypeCode');

        if ($typeCodeNodes !== false && $typeCodeNodes->length > 0) {
            $typeCode = $typeCodeNodes->item(0)?->textContent ?? '';

            if (! in_array($typeCode, ['380', '381'], true)) {
                $errors[] = "Invalid InvoiceTypeCode: {$typeCode}";
            }
        }

        // DocumentCurrencyCode must be EUR
        $currencyNodes = $xpath->query('//cbc:DocumentCurrencyCode');

        if ($currencyNodes !== false && $currencyNodes->length > 0) {
            $currency = $currencyNodes->item(0)?->textContent ?? '';

            if ($currency !== self::CURRENCY) {
                $errors[] = "Invalid DocumentCurrencyCode: {$currency}";
            }
        }

        if ($errors !== []) {
            Log::error('PEPPOL XML validation failed', ['errors' => $errors]);

            return false;
        }

        return true;
    }

    /**
     * Build the PEPPOL BIS 3.0 UBL XML document for the given invoice.
     *
     * Credit notes use InvoiceTypeCode 381, include a BillingReference to the
     * original invoice, and carry negated monetary amounts.
     */
    private function buildXml(Invoice $invoice): string
    {
        $isCreditNote = $invoice->type === InvoiceType::CreditNote;
        $typeCode = $isCreditNote ? '381' : '380';
        $sign = $isCreditNote ? -1 : 1;

        $coach = $invoice->coach;
        $coachProfile = $coach->coachProfile;

        $suppName = (string) config('peppol.supplier.name', 'Motivya');
        $suppEnterpriseNumber = (string) config('peppol.supplier.enterprise_number', 'BE0000000000');

        $custName = $coach->name;
        $custEnterpriseNumber = $coachProfile?->enterprise_number ?? '';

        $issueDate = ($invoice->issued_at ?? now())->format('Y-m-d');

        $taxCategoryCode = $invoice->tax_category_code;
        $vatPercent = $taxCategoryCode === 'S' ? '21' : '0';

        $lineExtension = $this->centsToDecimal($invoice->revenue_htva * $sign);
        $taxExclusive = $lineExtension;
        $taxInclusive = $this->centsToDecimal($invoice->revenue_ttc * $sign);
        $payable = $taxInclusive;
        $vatAmount = $this->centsToDecimal($invoice->vat_amount * $sign);

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        // Root element with explicit namespace declarations
        $root = $dom->createElementNS(self::UBL_INVOICE_NS, 'ubl:Invoice');
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:cac', self::CAC_NS);
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:cbc', self::CBC_NS);
        $dom->appendChild($root);

        // Mandatory header elements (PEPPOL BIS 3.0 §6.1)
        $this->cbc($dom, $root, 'CustomizationID', self::CUSTOMIZATION_ID);
        $this->cbc($dom, $root, 'ProfileID', self::PROFILE_ID);
        $this->cbc($dom, $root, 'ID', $invoice->invoice_number);
        $this->cbc($dom, $root, 'IssueDate', $issueDate);
        $this->cbc($dom, $root, 'InvoiceTypeCode', $typeCode);
        $this->cbc($dom, $root, 'DocumentCurrencyCode', self::CURRENCY);

        // BillingReference — mandatory for credit notes (PEPPOL BIS 3.0 §6.2)
        if ($isCreditNote && $invoice->relatedInvoice !== null) {
            $billingRef = $this->cac($dom, $root, 'BillingReference');
            $docRef = $this->cac($dom, $billingRef, 'InvoiceDocumentReference');
            $this->cbc($dom, $docRef, 'ID', $invoice->relatedInvoice->invoice_number);
        }

        // AccountingSupplierParty (Motivya)
        $supplierParty = $this->cac($dom, $root, 'AccountingSupplierParty');
        $supplierPartyEl = $this->cac($dom, $supplierParty, 'Party');
        $supplierPartyName = $this->cac($dom, $supplierPartyEl, 'PartyName');
        $this->cbc($dom, $supplierPartyName, 'Name', $suppName);
        $supplierLegal = $this->cac($dom, $supplierPartyEl, 'PartyLegalEntity');
        $this->cbc($dom, $supplierLegal, 'RegistrationName', $suppName);
        $this->cbc($dom, $supplierLegal, 'CompanyID', $suppEnterpriseNumber);

        // AccountingCustomerParty (coach)
        $customerParty = $this->cac($dom, $root, 'AccountingCustomerParty');
        $customerPartyEl = $this->cac($dom, $customerParty, 'Party');
        $customerPartyName = $this->cac($dom, $customerPartyEl, 'PartyName');
        $this->cbc($dom, $customerPartyName, 'Name', $custName);
        $customerLegal = $this->cac($dom, $customerPartyEl, 'PartyLegalEntity');
        $this->cbc($dom, $customerLegal, 'RegistrationName', $custName);
        $this->cbc($dom, $customerLegal, 'CompanyID', $custEnterpriseNumber);

        // TaxTotal
        $taxTotal = $this->cac($dom, $root, 'TaxTotal');
        $taxTotalAmount = $dom->createElementNS(self::CBC_NS, 'cbc:TaxAmount');
        $taxTotalAmount->setAttribute('currencyID', self::CURRENCY);
        $taxTotalAmount->appendChild($dom->createTextNode($vatAmount));
        $taxTotal->appendChild($taxTotalAmount);

        $taxSubtotal = $this->cac($dom, $taxTotal, 'TaxSubtotal');

        $taxableAmountEl = $dom->createElementNS(self::CBC_NS, 'cbc:TaxableAmount');
        $taxableAmountEl->setAttribute('currencyID', self::CURRENCY);
        $taxableAmountEl->appendChild($dom->createTextNode($taxExclusive));
        $taxSubtotal->appendChild($taxableAmountEl);

        $taxSubtotalAmount = $dom->createElementNS(self::CBC_NS, 'cbc:TaxAmount');
        $taxSubtotalAmount->setAttribute('currencyID', self::CURRENCY);
        $taxSubtotalAmount->appendChild($dom->createTextNode($vatAmount));
        $taxSubtotal->appendChild($taxSubtotalAmount);

        $taxCategory = $this->cac($dom, $taxSubtotal, 'TaxCategory');
        $this->cbc($dom, $taxCategory, 'ID', $taxCategoryCode);
        $this->cbc($dom, $taxCategory, 'Percent', $vatPercent);
        $taxScheme = $this->cac($dom, $taxCategory, 'TaxScheme');
        $this->cbc($dom, $taxScheme, 'ID', 'VAT');

        // LegalMonetaryTotal
        $monetaryTotal = $this->cac($dom, $root, 'LegalMonetaryTotal');

        $leaEl = $dom->createElementNS(self::CBC_NS, 'cbc:LineExtensionAmount');
        $leaEl->setAttribute('currencyID', self::CURRENCY);
        $leaEl->appendChild($dom->createTextNode($lineExtension));
        $monetaryTotal->appendChild($leaEl);

        $teaEl = $dom->createElementNS(self::CBC_NS, 'cbc:TaxExclusiveAmount');
        $teaEl->setAttribute('currencyID', self::CURRENCY);
        $teaEl->appendChild($dom->createTextNode($taxExclusive));
        $monetaryTotal->appendChild($teaEl);

        $tiaEl = $dom->createElementNS(self::CBC_NS, 'cbc:TaxInclusiveAmount');
        $tiaEl->setAttribute('currencyID', self::CURRENCY);
        $tiaEl->appendChild($dom->createTextNode($taxInclusive));
        $monetaryTotal->appendChild($tiaEl);

        $paEl = $dom->createElementNS(self::CBC_NS, 'cbc:PayableAmount');
        $paEl->setAttribute('currencyID', self::CURRENCY);
        $paEl->appendChild($dom->createTextNode($payable));
        $monetaryTotal->appendChild($paEl);

        // InvoiceLine
        $invoiceLine = $this->cac($dom, $root, 'InvoiceLine');
        $this->cbc($dom, $invoiceLine, 'ID', '1');

        $qtyEl = $dom->createElementNS(self::CBC_NS, 'cbc:InvoicedQuantity');
        $qtyEl->setAttribute('unitCode', 'C62');
        $qtyEl->appendChild($dom->createTextNode('1'));
        $invoiceLine->appendChild($qtyEl);

        $lineAmountEl = $dom->createElementNS(self::CBC_NS, 'cbc:LineExtensionAmount');
        $lineAmountEl->setAttribute('currencyID', self::CURRENCY);
        $lineAmountEl->appendChild($dom->createTextNode($lineExtension));
        $invoiceLine->appendChild($lineAmountEl);

        $item = $this->cac($dom, $invoiceLine, 'Item');
        $this->cbc($dom, $item, 'Description', self::LINE_DESCRIPTION);
        $this->cbc($dom, $item, 'Name', self::LINE_DESCRIPTION);

        $classifiedTaxCat = $this->cac($dom, $item, 'ClassifiedTaxCategory');
        $this->cbc($dom, $classifiedTaxCat, 'ID', $taxCategoryCode);
        $this->cbc($dom, $classifiedTaxCat, 'Percent', $vatPercent);
        $itemTaxScheme = $this->cac($dom, $classifiedTaxCat, 'TaxScheme');
        $this->cbc($dom, $itemTaxScheme, 'ID', 'VAT');

        $price = $this->cac($dom, $invoiceLine, 'Price');
        $priceAmountEl = $dom->createElementNS(self::CBC_NS, 'cbc:PriceAmount');
        $priceAmountEl->setAttribute('currencyID', self::CURRENCY);
        $priceAmountEl->appendChild($dom->createTextNode($lineExtension));
        $price->appendChild($priceAmountEl);

        return (string) $dom->saveXML();
    }

    /**
     * Append a `cbc:` element with text content to the given parent node.
     */
    private function cbc(DOMDocument $dom, DOMNode $parent, string $localName, string $value): DOMElement
    {
        $element = $dom->createElementNS(self::CBC_NS, "cbc:{$localName}");
        $element->appendChild($dom->createTextNode($value));
        $parent->appendChild($element);

        return $element;
    }

    /**
     * Append a `cac:` container element to the given parent node and return it.
     */
    private function cac(DOMDocument $dom, DOMNode $parent, string $localName): DOMElement
    {
        $element = $dom->createElementNS(self::CAC_NS, "cac:{$localName}");
        $parent->appendChild($element);

        return $element;
    }

    /**
     * Convert an integer cent value to a two-decimal-place string for XML output.
     *
     * Negative values (used for credit notes) are preserved.
     */
    private function centsToDecimal(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';

        return $sign.number_format(abs($cents) / 100, 2, '.', '');
    }
}
