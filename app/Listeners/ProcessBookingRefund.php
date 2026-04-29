<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\BookingCancelled;
use App\Models\Booking;
use App\Services\RefundService;
use Illuminate\Support\Facades\Log;

final class ProcessBookingRefund
{
    public function __construct(
        private readonly RefundService $refundService,
    ) {}

    public function handle(BookingCancelled $event): void
    {
        if (! $event->refundEligible) {
            return;
        }

        $booking = Booking::find($event->bookingId);

        if ($booking === null || $booking->amount_paid <= 0) {
            return;
        }

        try {
            $this->refundService->refund($booking);
        } catch (\Throwable $e) {
            Log::error('Failed to process booking refund.', [
                'booking_id' => $event->bookingId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
