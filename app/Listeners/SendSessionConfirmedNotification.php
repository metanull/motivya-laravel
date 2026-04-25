<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\BookingStatus;
use App\Events\SessionConfirmed;
use App\Models\SportSession;
use App\Notifications\SessionConfirmedNotification;

class SendSessionConfirmedNotification
{
    public function handle(SessionConfirmed $event): void
    {
        $session = SportSession::with('coach')->findOrFail($event->sessionId);

        $bookings = $session->bookings()
            ->where('status', BookingStatus::Confirmed)
            ->with('athlete')
            ->get();

        $session->coach->notify(
            (new SessionConfirmedNotification($event->sessionId))
                ->locale($session->coach->locale ?? 'fr'),
        );

        foreach ($bookings as $booking) {
            $booking->athlete->notify(
                (new SessionConfirmedNotification($event->sessionId))
                    ->locale($booking->athlete->locale ?? 'fr'),
            );
        }
    }
}
