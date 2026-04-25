<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\BookingCancelled;
use App\Models\Booking;
use App\Notifications\BookingCancelledNotification;

class SendBookingCancelledNotification
{
    public function handle(BookingCancelled $event): void
    {
        $booking = Booking::with('athlete')->findOrFail($event->bookingId);

        $booking->athlete->notify(
            (new BookingCancelledNotification($event->bookingId, $event->refundEligible))
                ->locale($booking->athlete->locale ?? 'fr'),
        );
    }
}
