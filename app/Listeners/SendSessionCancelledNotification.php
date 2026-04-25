<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\BookingStatus;
use App\Events\SessionCancelled;
use App\Notifications\SessionCancelledNotification;

class SendSessionCancelledNotification
{
    public function handle(SessionCancelled $event): void
    {
        $session = $event->session;
        $session->load('coach');

        $bookings = $session->bookings()
            ->whereIn('status', [
                BookingStatus::Confirmed->value,
                BookingStatus::PendingPayment->value,
            ])
            ->with('athlete')
            ->get();

        $session->coach->notify(
            (new SessionCancelledNotification($session->getKey()))
                ->locale($session->coach->locale ?? 'fr'),
        );

        foreach ($bookings as $booking) {
            $booking->athlete->notify(
                (new SessionCancelledNotification($session->getKey()))
                    ->locale($booking->athlete->locale ?? 'fr'),
            );
        }
    }
}
