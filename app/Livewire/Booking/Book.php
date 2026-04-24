<?php

declare(strict_types=1);

namespace App\Livewire\Booking;

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
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Livewire\Component;
use RuntimeException;
use Stripe\PaymentIntent;

final class Book extends Component
{
    public SportSession $sportSession;

    public ?Booking $existingBooking = null;

    public function mount(SportSession $sportSession): void
    {
        $this->sportSession = $sportSession->loadMissing('coach.coachProfile');

        $this->syncState();
    }

    public function book(BookingService $bookingService, PaymentService $paymentService): mixed
    {
        $athlete = auth()->user();
        abort_unless($athlete instanceof User, 403);

        Gate::authorize('create', [Booking::class, $this->sportSession]);

        try {
            $paymentIntent = DB::transaction(function () use ($athlete, $bookingService, $paymentService): PaymentIntent {
                $booking = $bookingService->book($this->sportSession, $athlete);
                $paymentIntent = $paymentService->createPaymentIntent($booking);

                $this->existingBooking = $booking->fresh();

                return $paymentIntent;
            });
        } catch (AlreadyBookedException|SessionFullException|SessionNotBookableException|InvalidArgumentException|RuntimeException $exception) {
            $this->syncState();

            $this->dispatch('notify', type: 'error', message: $exception->getMessage());

            return null;
        }

        $this->syncState();

        $redirectUrl = $this->resolveRedirectUrl($paymentIntent);

        if ($redirectUrl === null) {
            $this->dispatch('notify', type: 'error', message: __('bookings.error_payment_redirect_unavailable'));

            return null;
        }

        return redirect()->away($redirectUrl);
    }

    public function render(): View
    {
        $spotsRemaining = max($this->sportSession->max_participants - $this->sportSession->current_participants, 0);

        return view('livewire.booking.book', [
            'availabilityMessage' => $this->availabilityMessage(),
            'canBook' => $this->canBook(),
            'spotsRemaining' => $spotsRemaining,
        ]);
    }

    private function canBook(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && $user->can('create', [Booking::class, $this->sportSession])
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

    private function resolveRedirectUrl(PaymentIntent $paymentIntent): ?string
    {
        $redirectUrl = Arr::get($paymentIntent->toArray(), 'next_action.redirect_to_url.url')
            ?? ($paymentIntent->next_action?->redirect_to_url?->url ?? null);

        return is_string($redirectUrl) && $redirectUrl !== '' ? $redirectUrl : null;
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
