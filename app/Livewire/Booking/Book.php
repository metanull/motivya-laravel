<?php

declare(strict_types=1);

namespace App\Livewire\Booking;

use App\Enums\BookingStatus;
use App\Enums\SessionStatus;
use App\Enums\UserRole;
use App\Exceptions\AlreadyBookedException;
use App\Exceptions\SessionFullException;
use App\Exceptions\SessionNotBookableException;
use App\Models\Booking;
use App\Models\SportSession;
use App\Models\User;
use App\Services\BookingService;
use App\Services\PaymentService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

final class Book extends Component
{
    public SportSession $sportSession;

    public ?Booking $existingBooking = null;

    /** Controls visibility of the booking confirmation modal. */
    public bool $showConfirmModal = false;

    public function mount(SportSession $sportSession): void
    {
        $this->sportSession = $sportSession;

        $this->syncState();
    }

    /**
     * Open the confirmation modal after checking auth and authorization.
     * Does NOT create a booking.
     */
    public function openConfirmModal(): mixed
    {
        $athlete = auth()->user();
        abort_unless($athlete instanceof User, 403);

        if ($athlete instanceof MustVerifyEmail && ! $athlete->hasVerifiedEmail()) {
            $this->dispatch('notify', type: 'warning', message: __('auth.booking_requires_verified_email'));
            $this->redirect(route('verification.notice'));

            return null;
        }

        Gate::authorize('create', [Booking::class, $this->sportSession]);

        $this->showConfirmModal = true;

        return null;
    }

    public function book(BookingService $bookingService, PaymentService $paymentService): mixed
    {
        $athlete = auth()->user();
        abort_unless($athlete instanceof User, 403);

        if ($athlete instanceof MustVerifyEmail && ! $athlete->hasVerifiedEmail()) {
            $this->dispatch('notify', type: 'warning', message: __('auth.booking_requires_verified_email'));
            $this->redirect(route('verification.notice'));

            return null;
        }

        Gate::authorize('create', [Booking::class, $this->sportSession]);

        $this->showConfirmModal = false;

        // Story 1.2: Create the booking hold atomically, but outside the Stripe call.
        // A failed Stripe session must not roll back the capacity reservation.
        $booking = null;

        try {
            $booking = $bookingService->book($this->sportSession, $athlete);
        } catch (AlreadyBookedException) {
            // Story 1.2: Reuse a valid, non-expired pending hold rather than failing.
            $existingPending = Booking::query()
                ->where('sport_session_id', $this->sportSession->id)
                ->where('athlete_id', $athlete->id)
                ->where('status', BookingStatus::PendingPayment->value)
                ->first();

            if ($existingPending !== null && ! $existingPending->isPaymentExpired()) {
                $booking = $existingPending;
            } else {
                $this->syncState();
                $this->dispatch('notify', type: 'error', message: __('bookings.error_already_booked'));

                return null;
            }
        } catch (SessionFullException|SessionNotBookableException $exception) {
            $this->syncState();
            $this->dispatch('notify', type: 'error', message: $exception->getMessage());

            return null;
        }

        // Story 1.2: Stripe checkout call is outside the booking transaction.
        // Story 1.1: Catch all Throwable to prevent silent failures.
        try {
            $checkoutSession = $paymentService->createCheckoutSession($booking);
        } catch (\Throwable $exception) {
            Log::error('Payment start failed', [
                'booking_id' => $booking->id,
                'sport_session_id' => $this->sportSession->id,
                'athlete_id' => $athlete->id,
                'exception_class' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
            $this->existingBooking = $booking->fresh();
            $this->syncState();
            $this->dispatch('notify', type: 'error', message: __('bookings.error_payment_redirect_unavailable'));

            return null;
        }

        // Story 1.1: Validate that the returned URL is non-empty before redirecting.
        if (! is_string($checkoutSession->url) || $checkoutSession->url === '') {
            Log::error('Stripe checkout session returned no URL', [
                'booking_id' => $booking->id,
                'sport_session_id' => $this->sportSession->id,
                'athlete_id' => $athlete->id,
                'checkout_session_id' => $checkoutSession->id ?? null,
            ]);
            $this->existingBooking = $booking->fresh();
            $this->syncState();
            $this->dispatch('notify', type: 'error', message: __('bookings.error_payment_redirect_unavailable'));

            return null;
        }

        $this->existingBooking = $booking->fresh();
        $this->syncState();

        return redirect()->away($checkoutSession->url);
    }

    public function render(): View
    {
        $spotsRemaining = max($this->sportSession->max_participants - $this->sportSession->current_participants, 0);

        // Determine cancellation policy message for the confirmation modal
        $cancellationPolicy = match ($this->sportSession->status) {
            SessionStatus::Confirmed => __('bookings.confirm_modal_cancellation_confirmed'),
            default => __('bookings.confirm_modal_cancellation_published'),
        };

        return view('livewire.booking.book', [
            'availabilityMessage' => $this->availabilityMessage(),
            'canBook' => $this->canBook(),
            'isGuest' => ! (auth()->user() instanceof User),
            'spotsRemaining' => $spotsRemaining,
            'cancellationPolicy' => $cancellationPolicy,
            'paymentHoldExpiry' => __('bookings.confirm_modal_hold_expiry_value'),
        ]);
    }

    private function canBook(): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            return false;
        }

        return $user->can('create', [Booking::class, $this->sportSession])
            && $this->existingBooking === null
            && $this->hasPaymentSetup()
            && in_array($this->sportSession->status, [SessionStatus::Published, SessionStatus::Confirmed], true)
            && $this->sportSession->current_participants < $this->sportSession->max_participants;
    }

    private function availabilityMessage(): string
    {
        if ($this->existingBooking !== null) {
            return __('bookings.already_booked');
        }

        $user = auth()->user();

        if (! $user instanceof User || $user->role !== UserRole::Athlete) {
            return __('bookings.only_athletes_can_book');
        }

        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            return __('auth.booking_requires_verified_email');
        }

        if (! $this->hasPaymentSetup()) {
            return __('bookings.payment_unavailable');
        }

        if ($this->sportSession->current_participants >= $this->sportSession->max_participants) {
            return __('bookings.error_session_full');
        }

        if (! in_array($this->sportSession->status, [SessionStatus::Published, SessionStatus::Confirmed], true)) {
            return __('bookings.error_session_not_bookable');
        }

        return __('bookings.ready_to_book');
    }

    private function hasPaymentSetup(): bool
    {
        $coachProfile = $this->sportSession->coach->coachProfile;

        return is_string($coachProfile?->stripe_account_id)
            && $coachProfile->stripe_account_id !== ''
            && $coachProfile->stripe_onboarding_complete;
    }

    private function syncState(): void
    {
        $this->sportSession->refresh()->loadMissing('coach.coachProfile');

        $user = auth()->user();

        if (! $user instanceof User) {
            $this->existingBooking = null;

            return;
        }

        $this->existingBooking = Booking::query()
            ->where('sport_session_id', $this->sportSession->getKey())
            ->where('athlete_id', $user->getKey())
            ->first();
    }
}
