<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\BookingStatus;
use App\Events\SessionCancelled;
use App\Services\RefundService;

final class RefundAllBookingsOnSessionCancellation
{
    public function __construct(
        private readonly RefundService $refundService,
    ) {}

    public function handle(SessionCancelled $event): void
    {
        $event->session->bookings()
            ->where('status', BookingStatus::Confirmed)
            ->get()
            ->each(fn ($booking) => $this->refundService->refund($booking));
    }
}
