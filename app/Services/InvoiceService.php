<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\SportSession;
use Illuminate\Support\Facades\DB;

final class InvoiceService
{
    public function __construct(
        private readonly PayoutService $payoutService,
        private readonly VatService $vatService,
        private readonly PeppolXmlService $peppolXmlService,
    ) {}

    /**
     * Generate an invoice for a completed session.
     *
     * Sums revenue from all confirmed bookings, computes the payout breakdown
     * using the auto-best-plan algorithm, creates the Invoice record, and
     * generates the PEPPOL BIS 3.0 XML. The entire operation is wrapped in a
     * DB transaction so a failure at any step leaves no partial data.
     *
     * @param  SportSession  $session  A session in `completed` status.
     * @return Invoice The newly created invoice.
     */
    public function generateForCompletedSession(SportSession $session): Invoice
    {
        return DB::transaction(function () use ($session): Invoice {
            // Idempotency guard: return the existing invoice if one was already created
            // for this session (e.g. if the event listener is invoked more than once).
            $existing = Invoice::where('sport_session_id', $session->id)
                ->where('type', InvoiceType::Invoice)
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            $coach = $session->coach;
            $coachProfile = $coach->coachProfile;

            // Sum all confirmed booking amounts (revenue TTC in cents).
            $revenueTtc = (int) $session->bookings()
                ->where('status', BookingStatus::Confirmed->value)
                ->sum('amount_paid');

            // Estimate Stripe processing fee at the standard 1.5% rate.
            // Note: excludes the per-transaction fixed fee (€0.25 for Bancontact)
            // as it is applied per-booking, not as a single session-level deduction.
            $stripeFeeCents = (int) round($revenueTtc * 15 / 1000);

            // Compute the payout breakdown (picks the best plan automatically).
            $breakdown = $this->payoutService->calculatePayout($coachProfile, $revenueTtc, $stripeFeeCents);

            // Determine VAT details from the coach's VAT status.
            $vatAmount = $this->vatService->calculateVat($breakdown->revenue_htva, $coachProfile);
            $taxCategoryCode = $this->vatService->getTaxCategoryCode($coachProfile);

            $invoice = Invoice::create([
                'type' => InvoiceType::Invoice,
                'coach_id' => $session->coach_id,
                'sport_session_id' => $session->id,
                'billing_period_start' => $session->date,
                'billing_period_end' => $session->date,
                'revenue_ttc' => $breakdown->revenue_ttc,
                'revenue_htva' => $breakdown->revenue_htva,
                'vat_amount' => $vatAmount,
                'stripe_fee' => $breakdown->stripe_fee,
                'subscription_fee' => $breakdown->subscription_fee,
                'commission_amount' => $breakdown->commission_amount,
                'coach_payout' => $breakdown->coach_payout,
                'platform_margin' => $breakdown->platform_margin,
                'plan_applied' => $breakdown->applied_plan,
                'tax_category_code' => $taxCategoryCode,
                'status' => InvoiceStatus::Draft,
            ]);

            $this->peppolXmlService->generate($invoice);

            return $invoice;
        });
    }

    /**
     * Generate a credit note for a refunded booking, referencing the original invoice.
     *
     * Computes the payout breakdown for the refunded booking's amount, creates a
     * CreditNote Invoice record linked to the original invoice, and generates the
     * PEPPOL BIS 3.0 XML. The entire operation is wrapped in a DB transaction.
     *
     * @param  Booking  $booking  The booking that was refunded.
     * @param  Invoice  $originalInvoice  The invoice the credit note cancels against.
     * @return Invoice The newly created credit note.
     */
    public function generateCreditNote(Booking $booking, Invoice $originalInvoice): Invoice
    {
        return DB::transaction(function () use ($booking, $originalInvoice): Invoice {
            // Idempotency guard: skip if a credit note for this amount against the
            // same original invoice already exists (protects against duplicate event
            // delivery due to event auto-discovery double-registration).
            $existing = Invoice::where('related_invoice_id', $originalInvoice->id)
                ->where('type', InvoiceType::CreditNote)
                ->where('revenue_ttc', $booking->amount_paid)
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            $coachProfile = $originalInvoice->coach->coachProfile;

            $revenueTtc = $booking->amount_paid;
            $stripeFeeCents = (int) round($revenueTtc * 15 / 1000);

            $breakdown = $this->payoutService->calculatePayout($coachProfile, $revenueTtc, $stripeFeeCents);

            $vatAmount = $this->vatService->calculateVat($breakdown->revenue_htva, $coachProfile);
            $taxCategoryCode = $this->vatService->getTaxCategoryCode($coachProfile);

            $creditNote = Invoice::create([
                'type' => InvoiceType::CreditNote,
                'coach_id' => $originalInvoice->coach_id,
                'sport_session_id' => $booking->sport_session_id,
                'billing_period_start' => $originalInvoice->billing_period_start,
                'billing_period_end' => $originalInvoice->billing_period_end,
                'revenue_ttc' => $breakdown->revenue_ttc,
                'revenue_htva' => $breakdown->revenue_htva,
                'vat_amount' => $vatAmount,
                'stripe_fee' => $breakdown->stripe_fee,
                'subscription_fee' => $breakdown->subscription_fee,
                'commission_amount' => $breakdown->commission_amount,
                'coach_payout' => $breakdown->coach_payout,
                'platform_margin' => $breakdown->platform_margin,
                'plan_applied' => $breakdown->applied_plan,
                'tax_category_code' => $taxCategoryCode,
                'status' => InvoiceStatus::Draft,
                'related_invoice_id' => $originalInvoice->id,
            ]);

            $this->peppolXmlService->generate($creditNote);

            return $creditNote;
        });
    }
}
