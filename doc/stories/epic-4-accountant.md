# Epic 4: Accountant Portal + Automated Invoicing & PEPPOL

> **Milestone**: Epic 4: Accountant Portal + Invoicing
> **Depends on**: Epic 3 (payments, bookings, refunds)
> **Goal**: PEPPOL-compliant invoicing, VAT engine, subscription auto-best-plan, credit notes, accountant read-only portal with exports.

---

## E4-S01 · VAT determination service

**Labels**: `invoicing`, `payments`
**Size**: M
**Dependencies**: E2-S02 (CoachProfile with `is_vat_subject`)

Service that determines the correct VAT treatment based on coach status.

### Acceptance Criteria

- [ ] `app/Services/VatService.php`
- [ ] `getVatRate(CoachProfile $coach): int` — returns 21 (percent) for VAT-subject, 0 for non-subject
- [ ] `getTaxCategoryCode(CoachProfile $coach): string` — returns `S` for subject, `E` for non-subject
- [ ] `calculateVat(int $amountHtva, CoachProfile $coach): int` — returns VAT amount in cents
- [ ] `toHtva(int $amountTtc): int` — converts TTC to HTVA (divide by 1.21, round half-up)
- [ ] All amounts are integers in cents
- [ ] Unit test: subject coach → 21% VAT; non-subject → 0%; HTVA conversion accuracy

### Files to create/modify

- `app/Services/VatService.php`
- `tests/Unit/Services/VatServiceTest.php`

---

## E4-S02 · Payout calculation service

**Labels**: `invoicing`, `payments`
**Size**: M
**Dependencies**: E4-S01

Calculates coach payout using the HTVA-based formula with auto-best-plan.

### Acceptance Criteria

- [ ] `app/Services/PayoutService.php`
- [ ] `calculatePayout(CoachProfile $coach, int $revenueTtc, int $stripeFeeCents): PayoutBreakdown`
- [ ] Returns a value object `PayoutBreakdown` with: `revenue_ttc`, `revenue_htva`, `stripe_fee`, `subscription_fee`, `commission_rate`, `commission_amount`, `coach_payout`, `platform_margin`, `applied_plan`
- [ ] Implements auto-best-plan: computes all 3 tiers (Freemium 30%, Active €39+20%, Premium €79+10%), picks the one with the highest coach payout
- [ ] Works for both VAT-subject and non-subject coaches
- [ ] All amounts in cents
- [ ] Unit tests with the worked examples from `doc/UseCases.md`:
  - Jean Freemium: January (€300 revenue) and February (€624)
  - Marie Active: January (stays Freemium) and February (Active better)
  - Loïc Premium: March (€1760 revenue)

### Files to create/modify

- `app/Services/PayoutService.php`
- `app/DataTransferObjects/PayoutBreakdown.php` (or simple value object)
- `tests/Unit/Services/PayoutServiceTest.php`

---

## E4-S03 · Invoice model + migration

**Labels**: `invoicing`, `infrastructure`
**Size**: S
**Dependencies**: E3-S01

Database model for tracking invoices and credit notes.

### Acceptance Criteria

- [ ] `invoices` table: `id`, `invoice_number` (string, unique, sequential e.g. `INV-2026-000001`), `type` (`invoice` or `credit_note`), `coach_id` (FK), `sport_session_id` (FK nullable, null for subscription invoices), `billing_period_start` (date), `billing_period_end` (date), `revenue_ttc` (integer cents), `revenue_htva`, `vat_amount`, `stripe_fee`, `subscription_fee`, `commission_amount`, `coach_payout`, `platform_margin`, `plan_applied` (string), `tax_category_code` (S or E), `xml_path` (string nullable — path to generated PEPPOL XML), `issued_at` (timestamp), `status` (draft, issued, sent, paid), `related_invoice_id` (FK nullable — for credit notes referencing original invoice), `timestamps`
- [ ] `Invoice` model with casts, relationships: `belongsTo(User, 'coach_id')`, `belongsTo(SportSession)`
- [ ] `InvoiceFactory`
- [ ] Invoice number auto-generation: `INV-{year}-{6-digit sequence}` for invoices, `CN-{year}-{6-digit sequence}` for credit notes
- [ ] Unit test for model, auto-numbering

### Files to create/modify

- `app/Models/Invoice.php`
- `app/Enums/InvoiceType.php` (backed enum: `invoice`, `credit_note`)
- `app/Enums/InvoiceStatus.php` (backed enum: `draft`, `issued`, `sent`, `paid`)
- `database/migrations/xxxx_create_invoices_table.php`
- `database/factories/InvoiceFactory.php`
- `tests/Unit/Models/InvoiceTest.php`

---

## E4-S04 · InvoicePolicy

**Labels**: `auth`, `invoicing`
**Size**: S
**Dependencies**: E4-S03

Authorization rules for invoice access.

### Acceptance Criteria

- [ ] `app/Policies/InvoicePolicy.php`
- [ ] `viewAny`: coach (own), accountant (all), admin (all)
- [ ] `view`: coach (own), athlete (own bookings' invoices), accountant (all), admin (all)
- [ ] `create`: system only (no manual creation by users)
- [ ] `export`: accountant, admin
- [ ] `before()`: admin bypass
- [ ] Feature test: all roles tested

### Files to create/modify

- `app/Policies/InvoicePolicy.php`
- `tests/Feature/Policies/InvoicePolicyTest.php`

---

## E4-S05 · PEPPOL BIS 3.0 XML generator

**Labels**: `invoicing`
**Size**: L (broken into children below)
**Dependencies**: E4-S03

Generate compliant PEPPOL XML from invoice records.

### E4-S05a · XML template + basic structure

**Labels**: `invoicing`
**Size**: M

- [ ] `app/Services/PeppolXmlService.php`
- [ ] `generate(Invoice $invoice): string` — outputs PEPPOL BIS 3.0 compliant UBL XML
- [ ] Includes all required elements: `CustomizationID`, `ProfileID`, `ID`, `IssueDate`, `InvoiceTypeCode` (380 or 381), `DocumentCurrencyCode` (EUR)
- [ ] `AccountingSupplierParty`: Motivya's legal entity (from config)
- [ ] `AccountingCustomerParty`: coach enterprise number + details from CoachProfile
- [ ] `TaxTotal` with correct rate (21% `S` or 0% `E`)
- [ ] `LegalMonetaryTotal`: line extension, tax exclusive, tax inclusive, payable
- [ ] At least one `InvoiceLine` with quantity, unit price, tax category
- [ ] XML stored to filesystem (`storage/app/invoices/{year}/{invoice_number}.xml`)
- [ ] `xml_path` updated on the invoice record
- [ ] Unit test: generated XML is well-formed; key elements present

### E4-S05b · Credit note XML generation

**Labels**: `invoicing`
**Size**: S

- [ ] Same service handles `InvoiceTypeCode` 381 (credit note)
- [ ] `BillingReference` includes original invoice number
- [ ] Amounts are negative/reversed
- [ ] Unit test: credit note XML references original invoice

### E4-S05c · PEPPOL Schematron validation

**Labels**: `invoicing`
**Size**: S

- [ ] Validate generated XML against PEPPOL BIS 3.0 Schematron rules before marking as `issued`
- [ ] Store validation result; log errors if validation fails
- [ ] Unit test: valid invoice passes; malformed invoice fails

### Files to create/modify

- `app/Services/PeppolXmlService.php`
- `config/peppol.php` (Motivya legal entity details, enterprise number)
- `tests/Unit/Services/PeppolXmlServiceTest.php`

---

## E4-S06 · Invoice generation on session completion

**Labels**: `invoicing`
**Size**: M
**Dependencies**: E4-S05a, E4-S02, E3-S25

Listener that generates invoices when a session completes.

### Acceptance Criteria

- [ ] `app/Services/InvoiceService.php`
- [ ] `generateForCompletedSession(SportSession $session): Invoice`
- [ ] Calls `PayoutService` to compute breakdown
- [ ] Calls `PeppolXmlService` to generate XML
- [ ] Creates `Invoice` record with all financial details
- [ ] `app/Listeners/GenerateInvoiceOnSessionCompletion.php` — listens to `SessionCompleted`
- [ ] Wrapped in DB transaction
- [ ] Feature test: completing a session generates an invoice with correct amounts

### Files to create/modify

- `app/Services/InvoiceService.php`
- `app/Listeners/GenerateInvoiceOnSessionCompletion.php`
- `app/Providers/EventServiceProvider.php`
- `tests/Feature/Listeners/GenerateInvoiceTest.php`

---

## E4-S07 · Credit note generation on refund

**Labels**: `invoicing`
**Size**: S
**Dependencies**: E4-S05b, E3-S13

Generate PEPPOL credit notes when refunds are processed.

### Acceptance Criteria

- [ ] `InvoiceService::generateCreditNote(Booking $booking, Invoice $originalInvoice): Invoice`
- [ ] Creates credit note referencing original invoice
- [ ] Generates credit note XML via `PeppolXmlService`
- [ ] `app/Listeners/GenerateCreditNoteOnRefund.php` — listens to `BookingRefunded`
- [ ] Feature test: refund triggers credit note with correct amounts

### Files to create/modify

- `app/Services/InvoiceService.php` (add credit note method)
- `app/Listeners/GenerateCreditNoteOnRefund.php`
- `app/Providers/EventServiceProvider.php`
- `tests/Feature/Listeners/GenerateCreditNoteTest.php`

---

## E4-S08 · Subscription tier model + auto-best-plan scheduler

**Labels**: `payments`, `invoicing`
**Size**: M
**Dependencies**: E4-S02

Monthly billing cycle that computes the best plan for each coach.

### Acceptance Criteria

- [ ] `coach_subscriptions` table: `id`, `coach_id` (FK), `plan` (freemium/active/premium), `month` (date, first of month), `revenue_ttc`, `applied_plan`, `subscription_fee`, `commission_rate`, `timestamps`
- [ ] `CoachSubscription` model
- [ ] Scheduled command (monthly): for each active coach, compute all 3 tiers, store the best plan for the billing period
- [ ] If Active or Premium is best → charge subscription fee via Stripe (Cashier `charge()` or invoice)
- [ ] Subscription invoice (separate from session completion invoices)
- [ ] Feature test with the worked examples from `doc/UseCases.md`

### Files to create/modify

- `app/Models/CoachSubscription.php`
- `database/migrations/xxxx_create_coach_subscriptions_table.php`
- `app/Console/Commands/ComputeMonthlySubscriptions.php`
- `app/Services/SubscriptionService.php`
- `tests/Feature/Commands/ComputeMonthlySubscriptionsTest.php`

---

## E4-S09 · Accountant dashboard — transaction overview

**Labels**: `accountant`, `ui`
**Size**: M
**Dependencies**: E4-S03, E4-S04

Read-only dashboard for accountants to view all financial transactions.

### Acceptance Criteria

- [ ] `app/Livewire/Accountant/Dashboard.php`
- [ ] Lists all invoices and credit notes with: number, coach name, date, type, amounts, status
- [ ] Filters: date range, coach, type (invoice/credit note), status
- [ ] Sortable columns
- [ ] Pagination
- [ ] Protected by `role:accountant,admin` middleware
- [ ] All strings localized
- [ ] Livewire component test

### Files to create/modify

- `app/Livewire/Accountant/Dashboard.php`
- `resources/views/livewire/accountant/dashboard.blade.php`
- `routes/web.php`
- `tests/Feature/Livewire/Accountant/DashboardTest.php`

---

## E4-S10 · Accountant — commission verification view

**Labels**: `accountant`, `ui`
**Size**: S
**Dependencies**: E4-S09

Detailed view of commission calculations for verification.

### Acceptance Criteria

- [ ] View per invoice showing full breakdown: revenue TTC, HTVA, Stripe fee, subscription fee, commission rate, commission amount, coach payout, platform margin, applied plan
- [ ] Highlights discrepancies (if any field doesn't match expected formula)
- [ ] Read-only
- [ ] Feature test

### Files to create/modify

- `app/Livewire/Accountant/InvoiceDetail.php`
- `resources/views/livewire/accountant/invoice-detail.blade.php`
- `tests/Feature/Livewire/Accountant/InvoiceDetailTest.php`

---

## E4-S11 · Financial export (CSV + Excel)

**Labels**: `accountant`
**Size**: M
**Dependencies**: E4-S09

Export financial data for external accounting software.

### Acceptance Criteria

- [ ] `app/Services/FinancialExportService.php`
- [ ] Exports: invoices, credit notes, coach payouts, Stripe transactions
- [ ] Formats: CSV and Excel (using `maatwebsite/excel` or `openspout/openspout`)
- [ ] Filters: date range, coach, type
- [ ] Download via Livewire action on the accountant dashboard
- [ ] Protected by `role:accountant,admin` middleware
- [ ] Feature test: export generates valid file with correct data

### Files to create/modify

- `app/Services/FinancialExportService.php`
- `app/Livewire/Accountant/Dashboard.php` (add export action)
- `composer.json` (export library dependency)
- `tests/Feature/Export/FinancialExportTest.php`

---

## E4-S12 · Coach revenue tracking + payout history

**Labels**: `coach`, `invoicing`
**Size**: S
**Dependencies**: E4-S03, E2-S11

Coach can view their invoice history and payout details.

### Acceptance Criteria

- [ ] Section on coach dashboard or dedicated page
- [ ] Lists invoices (own) with: month, revenue, commission, payout, plan applied, status
- [ ] Download invoice XML (link to stored file)
- [ ] All strings localized
- [ ] Feature test

### Files to create/modify

- `app/Livewire/Coach/PayoutHistory.php`
- `resources/views/livewire/coach/payout-history.blade.php`
- `routes/web.php`
- `tests/Feature/Livewire/Coach/PayoutHistoryTest.php`

---

## E4-S13 · Admin — database export (coaches, sessions, payments)

**Labels**: `admin`
**Size**: S
**Dependencies**: E4-S11

Admin can export full database CSV dumps for coaches, sessions, and payments.

### Acceptance Criteria

- [ ] Admin dashboard: export buttons for Coaches CSV, Sessions CSV, Payments CSV
- [ ] Uses the same export service infrastructure as E4-S11
- [ ] Protected by `role:admin` middleware
- [ ] Feature test: correct data exported

### Files to create/modify

- `app/Livewire/Admin/DataExport.php`
- `resources/views/livewire/admin/data-export.blade.php`
- `tests/Feature/Livewire/Admin/DataExportTest.php`

---

## E4-S14 · Payout email notification to coach

**Labels**: `messaging`, `coach`
**Size**: XS
**Dependencies**: E4-S06

Notify coach when their payout invoice is generated.

### Acceptance Criteria

- [ ] `app/Notifications/PayoutProcessedNotification.php` — email with payout summary
- [ ] `app/Listeners/SendPayoutNotification.php` — listens to `CoachPayoutProcessed`
- [ ] Content localized
- [ ] Feature test

### Files to create/modify

- `app/Notifications/PayoutProcessedNotification.php`
- `app/Listeners/SendPayoutNotification.php`
- `app/Events/CoachPayoutProcessed.php`
- `app/Providers/EventServiceProvider.php`
- `tests/Feature/Notifications/PayoutNotificationTest.php`

---

## Dependency Graph

```
E4-S01 (VAT service) ──→ E4-S02 (Payout service)
                          ├── E4-S06 (Invoice on completion)
                          └── E4-S08 (Subscription auto-plan)

E4-S03 (Invoice model) ──→ E4-S04 (InvoicePolicy)
                        ──→ E4-S05 (PEPPOL XML)
                             ├── E4-S05a (Invoice XML)
                             ├── E4-S05b (Credit note XML)
                             └── E4-S05c (Schematron validation)

E4-S05 + E4-S02 ──→ E4-S06 (Invoice on completion)
                 ──→ E4-S07 (Credit note on refund)
                 ──→ E4-S14 (Payout notification)

E4-S03 + E4-S04 ──→ E4-S09 (Accountant dashboard)
                     ├── E4-S10 (Commission verification)
                     └── E4-S11 (Financial export)
                          └── E4-S13 (Admin export)

E4-S03 ──→ E4-S12 (Coach payout history)
```

## Suggested Implementation Order

1. **E4-S01**, **E4-S03** — VAT service + invoice model (parallel)
2. **E4-S02**, **E4-S04** — payout service + invoice policy
3. **E4-S05a** — PEPPOL XML generator (invoices)
4. **E4-S05b**, **E4-S05c** — credit note XML + validation
5. **E4-S06** — invoice generation on session completion
6. **E4-S07** — credit note on refund
7. **E4-S08** — subscription auto-best-plan
8. **E4-S09** — accountant dashboard
9. **E4-S10**, **E4-S11** — commission view + exports
10. **E4-S12**, **E4-S13** — coach payout history + admin export
11. **E4-S14** — payout notification
