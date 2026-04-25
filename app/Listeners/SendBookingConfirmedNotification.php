<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\BookingCreated;
use App\Models\Booking;
use App\Notifications\BookingConfirmedNotification;

class SendBookingConfirmedNotification
{
    public function handle(BookingCreated $event): void
    {
        $booking = Booking::with('athlete')->findOrFail($event->bookingId);

        $booking->athlete->notify(
            (new BookingConfirmedNotification($event->bookingId))
                ->locale($booking->athlete->locale ?? 'fr'),
        );
    }
}
