<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AuditEventType;
use App\Enums\AuditOperation;
use App\Enums\BookingStatus;
use App\Enums\PaymentAnomalyType;
use App\Enums\SessionStatus;
use App\Models\Booking;
use App\Models\CoachProfile;
use App\Models\Invoice;
use App\Models\PaymentAnomaly;
use App\Models\SportSession;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\Audit\AuditSubject;
use Illuminate\Support\Facades\DB;

final class AnomalyDetectorService
{
    public function __construct(private readonly AuditService $auditService) {}

    /**
     * Run all five detection routines and persist newly discovered anomalies.
     */
    public function detectAll(): void
    {
        $this->detectConfirmedBookingsMissingPayment();
        $this->detectPaidBookingsCancelledWithoutRefund();
        $this->detectCompletedSessionsWithoutInvoice();
        $this->detectInvoiceTotalMismatches();
        $this->detectCoachStripeIncomplete();
    }

    /**
     * Confirmed bookings where amount_paid = 0 or stripe_payment_intent_id is null.
     */
    public function detectConfirmedBookingsMissingPayment(): void
    {
        Booking::where('status', BookingStatus::Confirmed)
            ->where(function ($q): void {
                $q->where('amount_paid', 0)
                    ->orWhereNull('stripe_payment_intent_id');
            })
            ->each(function (Booking $booking): void {
                $this->createIfNotOpen(
                    type: PaymentAnomalyType::ConfirmedBookingMissingPayment,
                    modelType: Booking::class,
                    modelId: $booking->id,
                    attributes: [
                        'related_booking_id' => $booking->id,
                        'description' => "Booking #{$booking->id} is confirmed but has no payment record (amount_paid={$booking->amount_paid}, stripe_payment_intent_id is null).",
                        'recommended_action' => 'Verify the Stripe payment intent for this booking. If payment was collected, update the record. If not, cancel the booking.',
                    ],
                );
            });
    }

    /**
     * Paid bookings that were cancelled without a corresponding refund.
     */
    public function detectPaidBookingsCancelledWithoutRefund(): void
    {
        Booking::where('status', BookingStatus::Cancelled)
            ->where('amount_paid', '>', 0)
            ->whereNull('refunded_at')
            ->each(function (Booking $booking): void {
                $this->createIfNotOpen(
                    type: PaymentAnomalyType::PaidBookingCancelledWithoutRefund,
                    modelType: Booking::class,
                    modelId: $booking->id,
                    attributes: [
                        'related_booking_id' => $booking->id,
                        'description' => "Booking #{$booking->id} was cancelled with amount_paid={$booking->amount_paid} cents but has no refund record.",
                        'recommended_action' => 'Process a refund via Stripe for this booking or verify that a manual refund was already issued.',
                    ],
                );
            });
    }

    /**
     * Completed sessions that have no associated invoice.
     */
    public function detectCompletedSessionsWithoutInvoice(): void
    {
        SportSession::where('status', SessionStatus::Completed)
            ->whereDoesntHave('invoices')
            ->each(function (SportSession $session): void {
                $this->createIfNotOpen(
                    type: PaymentAnomalyType::CompletedSessionWithoutInvoice,
                    modelType: SportSession::class,
                    modelId: $session->id,
                    attributes: [
                        'related_session_id' => $session->id,
                        'related_coach_id' => $session->coach_id,
                        'description' => "Session #{$session->id} (\"{$session->title}\") is completed but has no invoice.",
                        'recommended_action' => 'Manually generate an invoice for this session or investigate why invoice generation failed.',
                    ],
                );
            });
    }

    /**
     * Invoices where revenue_htva + vat_amount ≠ revenue_ttc.
     */
    public function detectInvoiceTotalMismatches(): void
    {
        Invoice::all()->each(function (Invoice $invoice): void {
            $expectedTtc = $invoice->revenue_htva + $invoice->vat_amount;

            if ($expectedTtc !== $invoice->revenue_ttc) {
                $this->createIfNotOpen(
                    type: PaymentAnomalyType::InvoiceTotalMismatch,
                    modelType: Invoice::class,
                    modelId: $invoice->id,
                    attributes: [
                        'related_invoice_id' => $invoice->id,
                        'related_coach_id' => $invoice->coach_id,
                        'description' => "Invoice #{$invoice->invoice_number}: revenue_htva ({$invoice->revenue_htva}) + vat_amount ({$invoice->vat_amount}) = {$expectedTtc}, but revenue_ttc = {$invoice->revenue_ttc}.",
                        'recommended_action' => 'Review the invoice calculation. A reissue or correction may be required.',
                    ],
                );
            }
        });
    }

    /**
     * Coaches with incomplete Stripe onboarding who have at least one published session.
     */
    public function detectCoachStripeIncomplete(): void
    {
        CoachProfile::where('stripe_onboarding_complete', false)
            ->whereHas('user.sportSessions', function ($q): void {
                $q->where('status', SessionStatus::Published);
            })
            ->each(function (CoachProfile $profile): void {
                $this->createIfNotOpen(
                    type: PaymentAnomalyType::CoachStripeIncomplete,
                    modelType: CoachProfile::class,
                    modelId: $profile->id,
                    attributes: [
                        'related_coach_id' => $profile->user_id,
                        'description' => "Coach #{$profile->user_id} has published sessions but Stripe onboarding is incomplete.",
                        'recommended_action' => 'Contact the coach to complete Stripe onboarding or unpublish their sessions.',
                    ],
                );
            });
    }

    /**
     * Classify a single booking and return a set of anomaly flags.
     *
     * Livewire components and Blade views MUST consume these flags rather
     * than duplicating booking-anomaly query logic inline.
     *
     * @return array{
     *   missing_payment_intent: bool,
     *   confirmed_without_payment: bool,
     *   paid_cancelled_without_refund: bool,
     *   has_anomaly: bool,
     * }
     */
    public function classifyBooking(Booking $booking): array
    {
        $missingPaymentIntent = $booking->status === BookingStatus::Confirmed
            && $booking->amount_paid > 0
            && empty($booking->stripe_payment_intent_id);

        $confirmedWithoutPayment = $booking->status === BookingStatus::Confirmed
            && $booking->amount_paid <= 0;

        $paidCancelledWithoutRefund = $booking->status === BookingStatus::Cancelled
            && $booking->amount_paid > 0
            && $booking->refunded_at === null;

        return [
            'missing_payment_intent' => $missingPaymentIntent,
            'confirmed_without_payment' => $confirmedWithoutPayment,
            'paid_cancelled_without_refund' => $paidCancelledWithoutRefund,
            'has_anomaly' => $missingPaymentIntent || $confirmedWithoutPayment || $paidCancelledWithoutRefund,
        ];
    }

    /**
     * Check whether the given coach has any confirmed paid bookings with no payment intent.
     * Used by the coach dashboard to show a targeted anomaly warning.
     */
    public function coachHasMissingPaymentIntents(User $coach): bool
    {
        return Booking::join('sport_sessions', 'bookings.sport_session_id', '=', 'sport_sessions.id')
            ->where('sport_sessions.coach_id', $coach->id)
            ->where('bookings.status', BookingStatus::Confirmed)
            ->where('bookings.amount_paid', '>', 0)
            ->whereNull('bookings.stripe_payment_intent_id')
            ->exists();
    }

    public function resolve(PaymentAnomaly $anomaly, User $actor, string $reason): void
    {
        DB::transaction(function () use ($anomaly, $actor, $reason): void {
            $oldStatus = $anomaly->resolution_status;

            $anomaly->update([
                'resolution_status' => 'resolved',
                'resolution_reason' => $reason,
                'resolved_by' => $actor->id,
                'resolved_at' => now(),
            ]);

            $this->auditService->record(
                AuditEventType::AnomalyResolved,
                AuditOperation::StateChange,
                $anomaly,
                subjects: [
                    AuditSubject::primary($anomaly),
                    AuditSubject::related($actor, 'actor'),
                ],
                oldValues: ['resolution_status' => $oldStatus],
                newValues: ['resolution_status' => 'resolved'],
                metadata: ['reason' => $reason],
            );
        });
    }

    /**
     * Ignore an open anomaly with a reason.
     */
    public function ignore(PaymentAnomaly $anomaly, User $actor, string $reason): void
    {
        DB::transaction(function () use ($anomaly, $actor, $reason): void {
            $oldStatus = $anomaly->resolution_status;

            $anomaly->update([
                'resolution_status' => 'ignored',
                'resolution_reason' => $reason,
                'resolved_by' => $actor->id,
                'resolved_at' => now(),
            ]);

            $this->auditService->record(
                AuditEventType::AnomalyIgnored,
                AuditOperation::StateChange,
                $anomaly,
                subjects: [
                    AuditSubject::primary($anomaly),
                    AuditSubject::related($actor, 'actor'),
                ],
                oldValues: ['resolution_status' => $oldStatus],
                newValues: ['resolution_status' => 'ignored'],
                metadata: ['reason' => $reason],
            );
        });
    }

    /**
     * Create an anomaly record only if there is no existing open anomaly of the same type
     * for the same model (prevents duplicates on re-runs).
     *
     * @param  array<string, mixed>  $attributes
     */
    private function createIfNotOpen(
        PaymentAnomalyType $type,
        string $modelType,
        int $modelId,
        array $attributes,
    ): void {
        $alreadyOpen = PaymentAnomaly::where('anomaly_type', $type->value)
            ->where('anomalous_model_type', $modelType)
            ->where('anomalous_model_id', $modelId)
            ->where('resolution_status', 'open')
            ->exists();

        if ($alreadyOpen) {
            return;
        }

        PaymentAnomaly::create(array_merge([
            'anomaly_type' => $type->value,
            'anomalous_model_type' => $modelType,
            'anomalous_model_id' => $modelId,
        ], $attributes));
    }
}
