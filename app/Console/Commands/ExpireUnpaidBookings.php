<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Events\BookingCancelled;
use App\Models\Booking;
use App\Models\SportSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class ExpireUnpaidBookings extends Command
{
    protected $signature = 'bookings:expire-unpaid';

    protected $description = 'Cancel pending-payment bookings whose payment window has expired and release capacity';

    public function handle(): int
    {
        $expired = Booking::query()
            ->where('status', BookingStatus::PendingPayment->value)
            ->where('payment_expires_at', '<=', now())
            ->get();

        foreach ($expired as $booking) {
            $bookingId = null;

            DB::transaction(function () use ($booking, &$bookingId): void {
                $locked = Booking::query()
                    ->lockForUpdate()
                    ->find($booking->getKey());

                if ($locked === null || $locked->status !== BookingStatus::PendingPayment) {
                    return;
                }

                if ($locked->payment_expires_at === null || $locked->payment_expires_at->isFuture()) {
                    return;
                }

                $lockedSession = SportSession::query()
                    ->lockForUpdate()
                    ->find($locked->sport_session_id);

                if ($lockedSession !== null) {
                    $lockedSession->forceFill([
                        'current_participants' => max($lockedSession->current_participants - 1, 0),
                    ])->save();
                }

                $locked->forceFill([
                    'status' => BookingStatus::Cancelled->value,
                    'cancelled_at' => now(),
                ])->save();

                $bookingId = $locked->getKey();
            });

            if ($bookingId !== null) {
                BookingCancelled::dispatch($bookingId, 'payment_expired', false);
            }
        }

        return self::SUCCESS;
    }
}
