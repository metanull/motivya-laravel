<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\SessionStatus;
use App\Events\SessionConfirmed;
use App\Exceptions\AlreadyBookedException;
use App\Exceptions\SessionFullException;
use App\Exceptions\SessionNotBookableException;
use App\Models\Booking;
use App\Models\SportSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;

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

            $shouldDispatchSessionConfirmed = $lockedSession->status === SessionStatus::Published
                && $lockedSession->current_participants + 1 >= $lockedSession->min_participants;

            $lockedSession->current_participants++;

            if ($shouldDispatchSessionConfirmed) {
                $lockedSession->status = SessionStatus::Confirmed;
            }

            $lockedSession->save();

            return [
                'booking' => $booking,
                'session_confirmed' => $shouldDispatchSessionConfirmed,
            ];
        });

        $session->refresh();

        if ($result['session_confirmed']) {
            SessionConfirmed::dispatch($session);
        }

        return $result['booking']->refresh();
    }
}
