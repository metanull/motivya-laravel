<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\AuditEventType;
use App\Enums\AuditOperation;
use App\Enums\BookingStatus;
use App\Events\BookingCancelled;
use App\Models\Booking;
use App\Models\SchedulerHeartbeat;
use App\Models\SportSession;
use App\Services\Audit\AuditContextResolver;
use App\Services\Audit\AuditService;
use App\Services\Audit\AuditSubject;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class ExpireUnpaidBookings extends Command
{
    protected $signature = 'bookings:expire-unpaid';

    protected $description = 'Cancel pending-payment bookings whose payment window has expired and release capacity';

    public function __construct(
        private readonly AuditService $auditService,
        private readonly AuditContextResolver $contextResolver,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $expired = Booking::query()
            ->where('status', BookingStatus::PendingPayment->value)
            ->where('payment_expires_at', '<=', now())
            ->get();

        $auditContext = $this->contextResolver->forScheduler('bookings:expire-unpaid');

        foreach ($expired as $booking) {
            $bookingId = null;

            DB::transaction(function () use ($booking, &$bookingId, $auditContext): void {
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

                $this->auditService->record(
                    AuditEventType::BookingExpired,
                    AuditOperation::StateChange,
                    $locked,
                    subjects: [AuditSubject::primary($locked)],
                    oldValues: ['status' => BookingStatus::PendingPayment->value],
                    newValues: ['status' => BookingStatus::Cancelled->value],
                    context: $auditContext,
                );

                $bookingId = $locked->getKey();
            });

            if ($bookingId !== null) {
                BookingCancelled::dispatch($bookingId, 'payment_expired', false);
            }
        }

        SchedulerHeartbeat::record('bookings:expire-unpaid');

        return self::SUCCESS;
    }
}
