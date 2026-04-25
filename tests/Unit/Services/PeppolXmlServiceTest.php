<?php

declare(strict_types=1);

use App\Enums\InvoiceType;
use App\Models\CoachProfile;
use App\Models\Invoice;
use App\Models\User;
use App\Services\PeppolXmlService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Build a minimal valid PEPPOL BIS 3.0 XML string for use in validate() tests.
 */
function validPeppolXml(): string
{
    $customizationId = 'urn:cen.eu:en16931:2017#compliant#urn:fdc:peppol.eu:2017:poacc:billing:3.0';
    $profileId = 'urn:fdc:peppol.eu:2017:poacc:billing:01:1.0';
    $cbc = 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2';
    $cac = 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2';
    $ubl = 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2';

    return <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <ubl:Invoice xmlns:ubl="{$ubl}" xmlns:cbc="{$cbc}" xmlns:cac="{$cac}">
          <cbc:CustomizationID>{$customizationId}</cbc:CustomizationID>
          <cbc:ProfileID>{$profileId}</cbc:ProfileID>
          <cbc:ID>INV-2026-000001</cbc:ID>
          <cbc:IssueDate>2026-01-15</cbc:IssueDate>
          <cbc:InvoiceTypeCode>380</cbc:InvoiceTypeCode>
          <cbc:DocumentCurrencyCode>EUR</cbc:DocumentCurrencyCode>
          <cac:AccountingSupplierParty><cac:Party><cac:PartyName><cbc:Name>Motivya</cbc:Name></cac:PartyName></cac:Party></cac:AccountingSupplierParty>
          <cac:AccountingCustomerParty><cac:Party><cac:PartyName><cbc:Name>Coach</cbc:Name></cac:PartyName></cac:Party></cac:AccountingCustomerParty>
          <cac:TaxTotal><cbc:TaxAmount currencyID="EUR">21.00</cbc:TaxAmount></cac:TaxTotal>
          <cac:LegalMonetaryTotal>
            <cbc:LineExtensionAmount currencyID="EUR">100.00</cbc:LineExtensionAmount>
            <cbc:TaxExclusiveAmount currencyID="EUR">100.00</cbc:TaxExclusiveAmount>
            <cbc:TaxInclusiveAmount currencyID="EUR">121.00</cbc:TaxInclusiveAmount>
            <cbc:PayableAmount currencyID="EUR">121.00</cbc:PayableAmount>
          </cac:LegalMonetaryTotal>
          <cac:InvoiceLine>
            <cbc:ID>1</cbc:ID>
            <cbc:InvoicedQuantity unitCode="C62">1</cbc:InvoicedQuantity>
            <cbc:LineExtensionAmount currencyID="EUR">100.00</cbc:LineExtensionAmount>
            <cac:Item><cbc:Name>Coaching services</cbc:Name></cac:Item>
            <cac:Price><cbc:PriceAmount currencyID="EUR">100.00</cbc:PriceAmount></cac:Price>
          </cac:InvoiceLine>
        </ubl:Invoice>
        XML;
}

// ---------------------------------------------------------------------------
// E4-S05a · generate() — invoice XML
// ---------------------------------------------------------------------------

describe('PeppolXmlService::generate (invoice)', function () {

    beforeEach(function () {
        Storage::fake();

        $this->service = new PeppolXmlService;

        $coach = User::factory()->coach()->create(['name' => 'Jean Dupont']);
        CoachProfile::factory()->vatSubject()->create(['user_id' => $coach->id, 'enterprise_number' => '0123.456.789']);

        $this->invoice = Invoice::factory()->vatSubject()->create([
            'coach_id' => $coach->id,
            'revenue_ttc' => 12100,
            'revenue_htva' => 10000,
            'vat_amount' => 2100,
            'tax_category_code' => 'S',
            'type' => InvoiceType::Invoice->value,
            'billing_period_start' => '2026-01-01',
            'billing_period_end' => '2026-01-31',
        ]);
    });

    it('returns a non-empty string', function () {
        $xml = $this->service->generate($this->invoice);

        expect($xml)->toBeString()->not->toBeEmpty();
    });

    it('produces well-formed XML', function () {
        $xml = $this->service->generate($this->invoice);

        $dom = new DOMDocument;
        expect(@$dom->loadXML($xml))->toBeTrue();
    });

    it('includes CustomizationID with correct PEPPOL BIS 3.0 value', function () {
        $xml = $this->service->generate($this->invoice);

        expect($xml)->toContain('urn:cen.eu:en16931:2017#compliant#urn:fdc:peppol.eu:2017:poacc:billing:3.0');
    });

    it('includes ProfileID with correct value', function () {
        $xml = $this->service->generate($this->invoice);

        expect($xml)->toContain('urn:fdc:peppol.eu:2017:poacc:billing:01:1.0');
    });

    it('includes the invoice number as the document ID', function () {
        $xml = $this->service->generate($this->invoice);

        expect($xml)->toContain($this->invoice->invoice_number);
    });

    it('uses InvoiceTypeCode 380 for a regular invoice', function () {
        $xml = $this->service->generate($this->invoice);

        expect($xml)->toContain('<cbc:InvoiceTypeCode>380</cbc:InvoiceTypeCode>');
    });

    it('sets DocumentCurrencyCode to EUR', function () {
        $xml = $this->service->generate($this->invoice);

        expect($xml)->toContain('<cbc:DocumentCurrencyCode>EUR</cbc:DocumentCurrencyCode>');
    });

    it('includes the supplier name from config', function () {
        config(['peppol.supplier.name' => 'TestMotivya']);

        $xml = $this->service->generate($this->invoice);

        expect($xml)->toContain('TestMotivya');
    });

    it('includes the supplier enterprise number from config', function () {
        config(['peppol.supplier.enterprise_number' => 'BE0987654321']);

        $xml = $this->service->generate($this->invoice);

        expect($xml)->toContain('BE0987654321');
    });

    it('includes the coach name in AccountingCustomerParty', function () {
        $xml = $this->service->generate($this->invoice);

        expect($xml)->toContain('Jean Dupont');
    });

    it('includes the coach enterprise number in AccountingCustomerParty', function () {
        $xml = $this->service->generate($this->invoice);

        expect($xml)->toContain('0123.456.789');
    });

    it('includes TaxTotal with correct VAT amount for S-category coach', function () {
        $xml = $this->service->generate($this->invoice);

        // 2100 cents → 21.00 EUR
        expect($xml)->toContain('21.00');
    });

    it('includes TaxCategory ID S for a VAT-subject coach', function () {
        $xml = $this->service->generate($this->invoice);

        expect($xml)->toContain('<cbc:ID>S</cbc:ID>');
    });

    it('includes TaxCategory Percent 21 for a VAT-subject coach', function () {
        $xml = $this->service->generate($this->invoice);

        expect($xml)->toContain('<cbc:Percent>21</cbc:Percent>');
    });

    it('includes LegalMonetaryTotal with line extension, tax exclusive, tax inclusive, payable', function () {
        $xml = $this->service->generate($this->invoice);

        expect($xml)->toContain('LineExtensionAmount')
            ->toContain('TaxExclusiveAmount')
            ->toContain('TaxInclusiveAmount')
            ->toContain('PayableAmount');
    });

    it('includes at least one InvoiceLine', function () {
        $xml = $this->service->generate($this->invoice);

        expect($xml)->toContain('<cac:InvoiceLine>');
    });

    it('stores the XML file to the filesystem', function () {
        $this->service->generate($this->invoice);

        $year = $this->invoice->billing_period_start->format('Y');
        Storage::assertExists("invoices/{$year}/{$this->invoice->invoice_number}.xml");
    });

    it('updates xml_path on the invoice record', function () {
        $this->service->generate($this->invoice);

        $year = $this->invoice->billing_period_start->format('Y');
        $expected = "invoices/{$year}/{$this->invoice->invoice_number}.xml";

        expect($this->invoice->fresh()->xml_path)->toBe($expected);
    });

    it('generates valid PEPPOL XML for a non-VAT-subject (E-category) coach', function () {
        $coach = User::factory()->coach()->create(['name' => 'Marie Martin']);
        CoachProfile::factory()->nonVatSubject()->create([
            'user_id' => $coach->id,
            'enterprise_number' => '0456.789.012',
        ]);

        $invoice = Invoice::factory()->nonVatSubject()->create([
            'coach_id' => $coach->id,
            'revenue_ttc' => 10000,
            'revenue_htva' => 10000,
            'vat_amount' => 0,
            'tax_category_code' => 'E',
            'billing_period_start' => '2026-01-01',
            'billing_period_end' => '2026-01-31',
        ]);

        $xml = $this->service->generate($invoice);

        expect($xml)->toContain('<cbc:ID>E</cbc:ID>')
            ->toContain('<cbc:Percent>0</cbc:Percent>');
    });

});

// ---------------------------------------------------------------------------
// E4-S05b · generate() — credit note XML
// ---------------------------------------------------------------------------

describe('PeppolXmlService::generate (credit note)', function () {

    beforeEach(function () {
        Storage::fake();

        $this->service = new PeppolXmlService;

        $coach = User::factory()->coach()->create(['name' => 'Luc Bernard']);
        CoachProfile::factory()->vatSubject()->create([
            'user_id' => $coach->id,
            'enterprise_number' => '0234.567.890',
        ]);

        $this->originalInvoice = Invoice::factory()->invoice()->issued()->create([
            'coach_id' => $coach->id,
            'revenue_ttc' => 12100,
            'revenue_htva' => 10000,
            'vat_amount' => 2100,
            'tax_category_code' => 'S',
            'billing_period_start' => '2026-02-01',
            'billing_period_end' => '2026-02-28',
        ]);

        $this->creditNote = Invoice::factory()->creditNote()->create([
            'coach_id' => $coach->id,
            'revenue_ttc' => 12100,
            'revenue_htva' => 10000,
            'vat_amount' => 2100,
            'tax_category_code' => 'S',
            'related_invoice_id' => $this->originalInvoice->id,
            'billing_period_start' => '2026-02-01',
            'billing_period_end' => '2026-02-28',
        ]);
    });

    it('uses InvoiceTypeCode 381 for a credit note', function () {
        $xml = $this->service->generate($this->creditNote);

        expect($xml)->toContain('<cbc:InvoiceTypeCode>381</cbc:InvoiceTypeCode>');
    });

    it('includes a BillingReference with the original invoice number', function () {
        $xml = $this->service->generate($this->creditNote);

        expect($xml)->toContain('BillingReference')
            ->toContain($this->originalInvoice->invoice_number);
    });

    it('outputs negated (reversed) amounts for the credit note', function () {
        $xml = $this->service->generate($this->creditNote);

        // 10000 cents → -100.00 EUR; 12100 cents → -121.00 EUR; 2100 cents → -21.00 EUR
        expect($xml)->toContain('-100.00')
            ->toContain('-121.00')
            ->toContain('-21.00');
    });

    it('stores the credit note XML to the filesystem', function () {
        $this->service->generate($this->creditNote);

        $year = $this->creditNote->billing_period_start->format('Y');
        Storage::assertExists("invoices/{$year}/{$this->creditNote->invoice_number}.xml");
    });

    it('updates xml_path on the credit note record', function () {
        $this->service->generate($this->creditNote);

        $year = $this->creditNote->billing_period_start->format('Y');
        $expected = "invoices/{$year}/{$this->creditNote->invoice_number}.xml";

        expect($this->creditNote->fresh()->xml_path)->toBe($expected);
    });

    it('omits BillingReference when no related invoice is set', function () {
        $this->creditNote->related_invoice_id = null;
        $this->creditNote->save();

        $xml = $this->service->generate($this->creditNote);

        expect($xml)->not->toContain('BillingReference');
    });

});

// ---------------------------------------------------------------------------
// E4-S05c · validate() — PEPPOL structural validation
// ---------------------------------------------------------------------------

describe('PeppolXmlService::validate', function () {

    beforeEach(function () {
        $this->service = new PeppolXmlService;
    });

    it('returns true for a valid PEPPOL BIS 3.0 invoice XML', function () {
        expect($this->service->validate(validPeppolXml()))->toBeTrue();
    });

    it('returns false for completely malformed XML', function () {
        expect($this->service->validate('<not valid xml <<'))->toBeFalse();
    });

    it('returns false for an empty string', function () {
        expect($this->service->validate(''))->toBeFalse();
    });

    it('returns false when CustomizationID is missing', function () {
        $xml = str_replace('<cbc:CustomizationID>urn:cen.eu:en16931:2017#compliant#urn:fdc:peppol.eu:2017:poacc:billing:3.0</cbc:CustomizationID>', '', validPeppolXml());

        expect($this->service->validate($xml))->toBeFalse();
    });

    it('returns false when CustomizationID has an incorrect value', function () {
        $xml = str_replace(
            'urn:cen.eu:en16931:2017#compliant#urn:fdc:peppol.eu:2017:poacc:billing:3.0',
            'urn:invalid:customization',
            validPeppolXml()
        );

        expect($this->service->validate($xml))->toBeFalse();
    });

    it('returns false when ProfileID is missing', function () {
        $xml = str_replace('<cbc:ProfileID>urn:fdc:peppol.eu:2017:poacc:billing:01:1.0</cbc:ProfileID>', '', validPeppolXml());

        expect($this->service->validate($xml))->toBeFalse();
    });

    it('returns false when ID is missing', function () {
        $xml = str_replace('<cbc:ID>INV-2026-000001</cbc:ID>', '', validPeppolXml());

        expect($this->service->validate($xml))->toBeFalse();
    });

    it('returns false when IssueDate is missing', function () {
        $xml = str_replace('<cbc:IssueDate>2026-01-15</cbc:IssueDate>', '', validPeppolXml());

        expect($this->service->validate($xml))->toBeFalse();
    });

    it('returns false when InvoiceTypeCode is missing', function () {
        $xml = str_replace('<cbc:InvoiceTypeCode>380</cbc:InvoiceTypeCode>', '', validPeppolXml());

        expect($this->service->validate($xml))->toBeFalse();
    });

    it('returns false when InvoiceTypeCode is not 380 or 381', function () {
        $xml = str_replace('<cbc:InvoiceTypeCode>380</cbc:InvoiceTypeCode>', '<cbc:InvoiceTypeCode>999</cbc:InvoiceTypeCode>', validPeppolXml());

        expect($this->service->validate($xml))->toBeFalse();
    });

    it('returns true when InvoiceTypeCode is 381 (credit note)', function () {
        $xml = str_replace('<cbc:InvoiceTypeCode>380</cbc:InvoiceTypeCode>', '<cbc:InvoiceTypeCode>381</cbc:InvoiceTypeCode>', validPeppolXml());

        expect($this->service->validate($xml))->toBeTrue();
    });

    it('returns false when DocumentCurrencyCode is missing', function () {
        $xml = str_replace('<cbc:DocumentCurrencyCode>EUR</cbc:DocumentCurrencyCode>', '', validPeppolXml());

        expect($this->service->validate($xml))->toBeFalse();
    });

    it('returns false when DocumentCurrencyCode is not EUR', function () {
        $xml = str_replace('<cbc:DocumentCurrencyCode>EUR</cbc:DocumentCurrencyCode>', '<cbc:DocumentCurrencyCode>USD</cbc:DocumentCurrencyCode>', validPeppolXml());

        expect($this->service->validate($xml))->toBeFalse();
    });

    it('returns false when AccountingSupplierParty is missing', function () {
        $xml = preg_replace('/<cac:AccountingSupplierParty>.*?<\/cac:AccountingSupplierParty>/s', '', validPeppolXml());

        expect($this->service->validate($xml))->toBeFalse();
    });

    it('returns false when AccountingCustomerParty is missing', function () {
        $xml = preg_replace('/<cac:AccountingCustomerParty>.*?<\/cac:AccountingCustomerParty>/s', '', validPeppolXml());

        expect($this->service->validate($xml))->toBeFalse();
    });

    it('returns false when TaxTotal is missing', function () {
        $xml = preg_replace('/<cac:TaxTotal>.*?<\/cac:TaxTotal>/s', '', validPeppolXml());

        expect($this->service->validate($xml))->toBeFalse();
    });

    it('returns false when LegalMonetaryTotal is missing', function () {
        $xml = preg_replace('/<cac:LegalMonetaryTotal>.*?<\/cac:LegalMonetaryTotal>/s', '', validPeppolXml());

        expect($this->service->validate($xml))->toBeFalse();
    });

    it('returns false when InvoiceLine is missing', function () {
        $xml = preg_replace('/<cac:InvoiceLine>.*?<\/cac:InvoiceLine>/s', '', validPeppolXml());

        expect($this->service->validate($xml))->toBeFalse();
    });

    it('passes validation for XML generated by generate()', function () {
        Storage::fake();

        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->vatSubject()->create(['user_id' => $coach->id]);

        $invoice = Invoice::factory()->vatSubject()->create([
            'coach_id' => $coach->id,
            'revenue_ttc' => 12100,
            'revenue_htva' => 10000,
            'vat_amount' => 2100,
            'billing_period_start' => '2026-01-01',
            'billing_period_end' => '2026-01-31',
        ]);

        $xml = (new PeppolXmlService)->generate($invoice);

        expect((new PeppolXmlService)->validate($xml))->toBeTrue();
    });

});
