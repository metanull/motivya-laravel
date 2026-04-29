<?php

declare(strict_types=1);

namespace App\Livewire\Booking;

use App\Enums\BookingStatus;
use App\Enums\SessionStatus;
use App\Models\Booking;
use App\Models\SportSession;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Livewire\Component;

final class Cancel extends Component
{
    public SportSession $sportSession;

    public ?Booking $booking = null;

    public bool $confirmingCancellation = false;

    public function mount(SportSession $sportSession): void
    {
        $this->sportSession = $sportSession;

        $this->syncState();
    }

    public function confirmCancellation(): void
    {
        if (! $this->canCancel()) {
            return;
        }

        $this->confirmingCancellation = true;
    }

    public function closeConfirmation(): void
    {
        $this->confirmingCancellation = false;
    }

    public function processCancellation(BookingService $bookingService): void
    {
        if ($this->booking === null) {
            return;
        }

        $athlete = auth()->user();
        abort_unless($athlete instanceof User, 403);

        Gate::authorize('cancel', $this->booking);

        $refundEligible = $bookingService->isRefundEligibleForCancellation($this->booking);
        $hasAmountPaid = $this->booking->amount_paid > 0;

        try {
            $bookingService->cancel($this->booking, $athlete);
        } catch (InvalidArgumentException) {
            $this->syncState();
            $this->confirmingCancellation = false;

            $this->dispatch('notify', type: 'error', message: __('bookings.cancel_error_unavailable'));

            return;
        }

        $this->syncState();
        $this->confirmingCancellation = false;

        $this->dispatch(
            'notify',
            type: 'success',
            message: $refundEligible && $hasAmountPaid
                ? __('bookings.cancel_success_refund')
                : __('bookings.cancel_success_no_refund'),
        );
    }

    public function render(): View
    {
        return view('livewire.booking.cancel', [
            'canCancel' => $this->canCancel(),
            'refundEligible' => $this->refundEligible(),
            'refundWindowHours' => $this->refundWindowHours(),
        ]);
    }

    private function canCancel(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && $this->booking !== null
            && $user->can('cancel', $this->booking)
            && in_array($this->sportSession->status, [SessionStatus::Published, SessionStatus::Confirmed], true)
            && in_array($this->booking->status, [BookingStatus::PendingPayment, BookingStatus::Confirmed], true);
    }

    private function refundEligible(): bool
    {
        if ($this->booking === null) {
            return false;
        }

        return app(BookingService::class)->isRefundEligibleForCancellation($this->booking);
    }

    private function refundWindowHours(): int
    {
        return $this->sportSession->status === SessionStatus::Confirmed ? 48 : 24;
    }

    private function syncState(): void
    {
        $this->sportSession->refresh();

        $user = auth()->user();

        if (! $user instanceof User) {
            $this->booking = null;

            return;
        }

        $this->booking = Booking::query()
            ->where('sport_session_id', $this->sportSession->getKey())
            ->where('athlete_id', $user->getKey())
            ->first();
    }
}
