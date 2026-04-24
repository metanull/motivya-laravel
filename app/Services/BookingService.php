<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\SessionStatus;
use App\Events\BookingCancelled;
use App\Events\SessionConfirmed;
use App\Exceptions\AlreadyBookedException;
use App\Exceptions\SessionFullException;
use App\Exceptions\SessionNotBookableException;
use App\Models\Booking;
use App\Models\SportSession;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class BookingService
{
    /**
     * Create a booking atomically and update session capacity.
     */
    public function book(SportSession $session, User $athlete): Booking
    {
        $result = DB::transaction(function () use ($session, $athlete): array {
            $lockedSession = SportSession::query()
                ->lockForUpdate()
                ->findOrFail($session->getKey());

            if (! in_array($lockedSession->status, [SessionStatus::Published, SessionStatus::Confirmed], true)) {
                throw new SessionNotBookableException;
            }

            if ($lockedSession->current_participants >= $lockedSession->max_participants) {
                throw new SessionFullException;
            }

            if ($lockedSession->bookings()->where('athlete_id', $athlete->getKey())->exists()) {
                throw new AlreadyBookedException;
            }

            $booking = $lockedSession->bookings()->create([
                'athlete_id' => $athlete->getKey(),
                'status' => BookingStatus::PendingPayment->value,
            ]);

            $nextParticipants = $lockedSession->current_participants + 1;
            $shouldDispatchSessionConfirmed = $lockedSession->status === SessionStatus::Published
                && $nextParticipants >= $lockedSession->min_participants;

            $lockedSession->forceFill([
                'current_participants' => $nextParticipants,
                'status' => $shouldDispatchSessionConfirmed
                    ? SessionStatus::Confirmed
                    : $lockedSession->status,
            ])->save();

            return [
                'booking' => $booking,
                'session_confirmed' => $shouldDispatchSessionConfirmed,
            ];
        });

        if ($result['session_confirmed']) {
            SessionConfirmed::dispatch($session->getKey());
        }

        return $result['booking']->refresh();
    }

    /**
     * Cancel a booking for the given athlete.
     */
    public function cancel(Booking $booking, User $athlete): void
    {
        $result = DB::transaction(function () use ($booking, $athlete): array {
            $lockedBooking = Booking::query()
                ->with('sportSession')
                ->lockForUpdate()
                ->findOrFail($booking->getKey());

            if ($lockedBooking->athlete_id !== $athlete->getKey()) {
                throw new AuthorizationException;
            }

            if (! in_array($lockedBooking->status, [BookingStatus::PendingPayment, BookingStatus::Confirmed], true)) {
                throw new InvalidArgumentException('Only pending payment or confirmed bookings can be cancelled.');
            }

            $lockedSession = SportSession::query()
                ->lockForUpdate()
                ->findOrFail($lockedBooking->sport_session_id);

            if (! in_array($lockedSession->status, [SessionStatus::Published, SessionStatus::Confirmed], true)) {
                throw new InvalidArgumentException('Only bookings for published or confirmed sessions can be cancelled.');
            }

            $refundEligible = $this->isRefundEligibleForSession($lockedSession);

            $lockedBooking->forceFill([
                'status' => BookingStatus::Cancelled,
                'cancelled_at' => now(),
            ])->save();

            $lockedSession->forceFill([
                'current_participants' => max($lockedSession->current_participants - 1, 0),
            ])->save();

            return [
                'booking_id' => $lockedBooking->getKey(),
                'refund_eligible' => $refundEligible,
            ];
        });

        BookingCancelled::dispatch($result['booking_id'], 'athlete_cancelled', $result['refund_eligible']);
    }

    public function isRefundEligibleForCancellation(Booking $booking): bool
    {
        $booking->loadMissing('sportSession');

        return $this->isRefundEligibleForSession($booking->sportSession);
    }

    private function isRefundEligibleForSession(SportSession $session): bool
    {
        $minimumHoursBeforeStart = match ($session->status) {
            SessionStatus::Confirmed => 48,
            SessionStatus::Published => 24,
            default => null,
        };

        if ($minimumHoursBeforeStart === null) {
            return false;
        }

        $startDateTime = Carbon::parse(sprintf(
            '%s %s',
            $session->date->format('Y-m-d'),
            (string) $session->start_time,
        ));

        return now()->lessThanOrEqualTo($startDateTime->copy()->subHours($minimumHoursBeforeStart));
    }
}
