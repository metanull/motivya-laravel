---
description: "Use when working on invoice generation, PEPPOL XML output, coach payout calculations, VAT handling, credit notes, or billing-related service classes. Covers PEPPOL BIS 3.0 structure, Belgian VAT rules, and the non-subject coach margin formula."
applyTo: "app/Services/Invoice*,app/Services/*Billing*,app/Services/*Payout*,app/Services/*Vat*"
---
# PEPPOL Invoicing & Belgian VAT Rules

## PEPPOL BIS 3.0 Compliance

Belgium mandates PEPPOL BIS 3.0 structured e-invoicing since January 2026. All invoices and credit notes routed through Billit (via the Stripe App) must conform to these rules.

### Required XML Elements

Every invoice MUST include at minimum:

| UBL Element | Description | Example |
|-------------|-------------|---------|
| `cbc:CustomizationID` | PEPPOL BIS 3.0 specification ID | `urn:cen.eu:en16931:2017#compliant#urn:fdc:peppol.eu:2017:poacc:billing:3.0` |
| `cbc:ProfileID` | Business process | `urn:fdc:peppol.eu:2017:poacc:billing:01:1.0` |
| `cbc:ID` | Unique invoice number (sequential, no gaps) | `INV-2026-000142` |
| `cbc:IssueDate` | ISO 8601 date | `2026-01-15` |
| `cbc:InvoiceTypeCode` | `380` = invoice, `381` = credit note | `380` |
| `cbc:DocumentCurrencyCode` | Always EUR | `EUR` |
| `cac:AccountingSupplierParty` | Motivya's legal entity + enterprise number | — |
| `cac:AccountingCustomerParty` | Coach's legal entity + enterprise number | — |
| `cac:TaxTotal` | Breakdown per VAT rate | — |
| `cac:LegalMonetaryTotal` | Totals: line extension, tax exclusive, tax inclusive, payable | — |
| `cac:InvoiceLine` | At least one line item with quantity, unit price, and tax category | — |

### Tax Category Codes

| Code | Meaning | Use Case |
|------|---------|----------|
| `S` | Standard rate | VAT-subject coach (21%) |
| `E` | Exempt | Non-subject coach (franchise regime) |

### Validation

- Validate generated XML against the official PEPPOL BIS 3.0 Schematron rules before sending
- Store the raw XML alongside the invoice record for audit purposes
- Invoice numbers MUST be sequential with no gaps, prefixed by year

## Belgian VAT Rates

| Rate | Application |
|------|-------------|
| 21% | Standard rate — applies to all sports coaching services and platform commissions |
| 0% | Coach under franchise regime (non-subject) — no VAT on their invoice to Motivya |

## All Monetary Values in Cents

Per project convention, store and compute all amounts as **integers in cents** (EUR). Convert to decimal only at the display/XML output layer.

## Coach Payout Calculation

The payout formula depends on whether the coach is VAT-subject or not. The goal is to preserve Motivya's margin regardless of VAT status.

### Core Formula

```
revenue_ttc   = sum of all client payments (in cents, VAT-inclusive)
revenue_htva  = revenue_ttc / 1.21          (round half-up)
margin_htva   = revenue_htva * commission_rate
payout        = revenue_htva - margin_htva  (amount owed to coach, HTVA)
```

### VAT-Subject Coach

- Coach invoices Motivya **with 21% VAT** on the payout amount
- Motivya recovers that VAT → effective cost to Motivya = payout HTVA
- Coach receives: `payout + (payout * 0.21)` TTC

### Non-Subject Coach (Franchise Regime)

- Coach invoices Motivya **without VAT** (tax category `E`)
- Motivya cannot recover any VAT → effective cost to Motivya = payout amount
- Coach receives: `payout` (same HTVA amount, no VAT supplement)
- The system adjusts automatically — the coach simply earns less in absolute terms because there is no VAT add-on

### Commission Tiers

| Plan | Monthly Fee (TTC) | Commission Rate |
|------|-------------------|-----------------|
| Freemium | 0 € | 30% |
| Active | 39 € | 20% |
| Premium | 79 € | 10% |

The system must automatically compare plans and apply whichever is most advantageous for the coach (see `doc/UseCases.md` examples).

### Deductions

Subtract from payout before issuing:
1. Stripe processing fees (1.5% + €0.25 per Bancontact transaction, or Stripe's current rates)
2. Monthly subscription fee (if Active or Premium)

## Credit Notes

- Use invoice type code `381`
- Reference the original invoice number in `cac:BillingReference`
- Cancellation refunds (48h confirmed / 24h pending) generate automatic credit notes
- Credit note amounts must match the original line items being reversed

## Service Class Conventions

- Place all invoice logic in `app/Services/Invoice*` or `app/Services/*Billing*`
- VAT determination logic in `app/Services/*Vat*`
- Payout computation in `app/Services/*Payout*`
- Never compute VAT or payouts inside controllers or Livewire components
- Use the coach's `is_vat_subject` boolean (from their profile) to branch tax logic
- Wrap payout + invoice generation in a DB transaction
