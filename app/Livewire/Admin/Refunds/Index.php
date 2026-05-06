<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Refunds;

use App\Enums\BookingStatus;
use App\Enums\RefundAuditStatus;
use App\Models\AdminRefundAudit;
use App\Models\Booking;
use App\Services\AnomalyDetectorService;
use App\Services\RefundService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;

final class Index extends Component
{
    use WithPagination;

    // ── Filter properties ─────────────────────────────────────────────────

    public string $statusFilter = '';

    // ── Action state ──────────────────────────────────────────────────────

    public ?int $refundingBookingId = null;

    public string $refundReason = '';

    // ── Lifecycle ─────────────────────────────────────────────────────────

    public function mount(): void
    {
        Gate::authorize('access-admin-panel');
    }

    // ── Filter hook ───────────────────────────────────────────────────────

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    // ── Refund action ─────────────────────────────────────────────────────

    /**
     * Open the inline refund form for the given booking.
     */
    public function confirmRefund(int $bookingId): void
    {
        $this->refundingBookingId = $bookingId;
        $this->refundReason = '';
    }

    /**
     * Dismiss the inline refund form without performing any action.
     */
    public function cancelRefundAction(): void
    {
        $this->refundingBookingId = null;
        $this->refundReason = '';
    }

    /**
     * Process an exceptional refund for a confirmed paid booking.
     *
     * Distinguishes between five failure cases so each can be surfaced
     * with a specific, actionable message:
     *   1. Already refunded
     *   2. Not confirmed (wrong status)
     *   3. No amount paid
     *   4. Missing Stripe payment intent (requires reconciliation first)
     *   5. Stripe API failure
     *
     * Every attempt — regardless of outcome — is recorded in admin_refund_audits
     * for a full audit trail.
     */
    public function processRefund(int $bookingId, RefundService $refundService): void
    {
        $this->validate([
            'refundReason' => ['required', 'string', 'max:1000'],
        ]);

        $booking = Booking::with(['athlete', 'sportSession'])->findOrFail($bookingId);

        Gate::authorize('refund', $booking);

        // Case 1: Already refunded — specific guard to prevent double-refund.
        if ($booking->status === BookingStatus::Refunded) {
            $this->recordRefundFailure($booking, 'admin.refunds_error_already_refunded');
            $this->dispatch('notify', type: 'error', message: __('admin.refunds_error_already_refunded'));

            return;
        }

        // Case 2: Not confirmed.
        if ($booking->status !== BookingStatus::Confirmed) {
            $this->recordRefundFailure($booking, 'admin.refunds_error_not_confirmed');
            $this->dispatch('notify', type: 'error', message: __('admin.refunds_error_not_confirmed'));

            return;
        }

        // Case 3: No amount paid.
        if ($booking->amount_paid <= 0) {
            $this->recordRefundFailure($booking, 'admin.refunds_error_no_amount');
            $this->dispatch('notify', type: 'error', message: __('admin.refunds_error_no_amount'));

            return;
        }

        // Case 4: Missing Stripe payment intent — reconciliation required before retrying.
        if (empty($booking->stripe_payment_intent_id)) {
            $this->recordRefundFailure($booking, 'admin.refunds_error_missing_payment_intent');
            $this->dispatch('notify', type: 'warning', message: __('admin.refunds_error_missing_payment_intent'));

            return;
        }

        /** @var AdminRefundAudit $audit */
        $audit = AdminRefundAudit::create([
            'admin_id' => auth()->id(),
            'booking_id' => $booking->id,
            'refund_amount' => $booking->amount_paid,
            'reason' => $this->refundReason,
            'status' => RefundAuditStatus::Attempted,
        ]);

        try {
            $refundService->refund($booking);

            $audit->update(['status' => RefundAuditStatus::Succeeded]);

            $this->refundingBookingId = null;
            $this->refundReason = '';

            $this->dispatch('notify', type: 'success', message: __('admin.refunds_success'));
        } catch (\Throwable $e) {
            Log::error('Admin exceptional refund failed', [
                'booking_id' => $bookingId,
                'admin_id' => auth()->id(),
                'exception_class' => $e::class,
                'error' => $e->getMessage(),
            ]);

            $audit->update([
                'status' => RefundAuditStatus::Failed,
                'error_message' => $e->getMessage(),
            ]);

            $this->dispatch('notify', type: 'error', message: __('admin.refunds_error_stripe_failure'));
        }
    }

    // ── Render ────────────────────────────────────────────────────────────

    public function render(AnomalyDetectorService $anomalyDetector): View
    {
        $bookings = Booking::query()
            ->with(['athlete', 'sportSession.coach'])
            ->when(
                $this->statusFilter !== '',
                fn ($q) => $q->where('status', $this->statusFilter),
                // Story 1.5: Default view shows confirmed paid bookings (the actionable refund queue).
                fn ($q) => $q->where('status', BookingStatus::Confirmed)->where('amount_paid', '>', 0),
            )
            ->orderByDesc('created_at')
            ->paginate(15);

        // Story 1.4/1.5: Pre-compute per-booking anomaly flags so the blade
        // consumes computed data rather than duplicating classification logic.
        $bookingFlags = $bookings->getCollection()->mapWithKeys(
            fn (Booking $booking): array => [$booking->id => $anomalyDetector->classifyBooking($booking)],
        );

        // Story 4.3: Load the most recent refund audit per booking for display.
        $bookingIds = $bookings->getCollection()->pluck('id')->all();
        $lastAudits = AdminRefundAudit::query()
            ->whereIn('booking_id', $bookingIds)
            ->orderByDesc('created_at')
            ->get()
            ->unique('booking_id')
            ->keyBy('booking_id');

        // Story 4.3: Pre-compute per-booking eligibility badges.
        $eligibilityBadges = $bookings->getCollection()->mapWithKeys(
            fn (Booking $booking): array => [
                $booking->id => $this->eligibilityBadge($booking, $bookingFlags[$booking->id] ?? []),
            ],
        );

        return view('livewire.admin.refunds.index', [
            'bookings' => $bookings,
            'bookingFlags' => $bookingFlags,
            'lastAudits' => $lastAudits,
            'eligibilityBadges' => $eligibilityBadges,
            'statuses' => BookingStatus::cases(),
        ])->title(__('admin.refunds_title'));
    }

    // ── Private helpers ───────────────────────────────────────────────────

    /**
     * Record a failed refund attempt in the audit log.
     */
    private function recordRefundFailure(Booking $booking, string $messageKey): void
    {
        AdminRefundAudit::create([
            'admin_id' => auth()->id(),
            'booking_id' => $booking->id,
            'refund_amount' => $booking->amount_paid,
            'reason' => $this->refundReason,
            'status' => RefundAuditStatus::Failed,
            'error_message' => __($messageKey),
        ]);
    }

    /**
     * Return the eligibility badge key for a booking.
     *
     * Possible values:
     *   eligible, already_refunded, missing_payment_intent, unpaid, pending_payment, cancelled
     *
     * @param  array<string, bool>  $flags  pre-computed anomaly flags for this booking
     */
    public function eligibilityBadge(Booking $booking, array $flags): string
    {
        if ($booking->status === BookingStatus::Refunded) {
            return 'already_refunded';
        }

        if ($booking->status === BookingStatus::Cancelled) {
            return 'cancelled';
        }

        if ($booking->status === BookingStatus::PendingPayment) {
            return 'pending_payment';
        }

        if ($booking->status !== BookingStatus::Confirmed) {
            return 'cancelled';
        }

        if ($booking->amount_paid <= 0) {
            return 'unpaid';
        }

        if (! empty($flags['missing_payment_intent'])) {
            return 'missing_payment_intent';
        }

        return 'eligible';
    }
}
