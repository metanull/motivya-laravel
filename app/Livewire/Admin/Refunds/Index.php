<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Refunds;

use App\Enums\BookingStatus;
use App\Enums\RefundAuditStatus;
use App\Models\AdminRefundAudit;
use App\Models\Booking;
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
     * Validation is performed first so field-level errors surface in the
     * inline form. Every attempt — regardless of outcome — is recorded in
     * admin_refund_audits for a full audit trail.
     */
    public function processRefund(int $bookingId, RefundService $refundService): void
    {
        $this->validate([
            'refundReason' => ['required', 'string', 'max:1000'],
        ]);

        $booking = Booking::with(['athlete', 'sportSession'])->findOrFail($bookingId);

        Gate::authorize('refund', $booking);

        // Guard: only confirmed bookings with a positive amount are eligible.
        if ($booking->status !== BookingStatus::Confirmed || $booking->amount_paid <= 0) {
            AdminRefundAudit::create([
                'admin_id' => auth()->id(),
                'booking_id' => $booking->id,
                'refund_amount' => $booking->amount_paid,
                'reason' => $this->refundReason,
                'status' => RefundAuditStatus::Failed,
                'error_message' => __('admin.refunds_ineligibility_reason'),
            ]);

            $this->dispatch('notify', type: 'error', message: __('admin.refunds_error'));

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
                'error' => $e->getMessage(),
            ]);

            $audit->update([
                'status' => RefundAuditStatus::Failed,
                'error_message' => $e->getMessage(),
            ]);

            $this->dispatch('notify', type: 'error', message: __('admin.refunds_error'));
        }
    }

    // ── Render ────────────────────────────────────────────────────────────

    public function render(): View
    {
        $bookings = Booking::query()
            ->with(['athlete', 'sportSession.coach'])
            ->when(
                $this->statusFilter !== '',
                fn ($q) => $q->where('status', $this->statusFilter),
            )
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('livewire.admin.refunds.index', [
            'bookings' => $bookings,
            'statuses' => BookingStatus::cases(),
        ])->title(__('admin.refunds_title'));
    }
}
