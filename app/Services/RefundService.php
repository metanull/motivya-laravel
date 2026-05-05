<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AuditEventType;
use App\Enums\AuditOperation;
use App\Enums\BookingStatus;
use App\Events\BookingRefunded;
use App\Models\Booking;
use App\Services\Audit\AuditService;
use App\Services\Audit\AuditSubject;
use Closure;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Stripe\Refund as StripeRefund;
use Stripe\Stripe;

final class RefundService
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly ?Closure $createRefundUsing = null,
    ) {}

    /**
     * Create a Stripe refund for a booking and mark it as refunded.
     */
    public function refund(Booking $booking): void
    {
        // Guard: already refunded — no-op, no audit.
        if ($booking->status === BookingStatus::Refunded || $booking->refunded_at !== null) {
            return;
        }

        // Step 1: record refund.requested in its own transaction (always commits).
        DB::transaction(function () use ($booking): void {
            $this->auditService->record(
                AuditEventType::RefundRequested,
                AuditOperation::Payment,
                $booking,
                subjects: [AuditSubject::primary($booking)],
                oldValues: ['status' => $booking->status->value],
                metadata: ['stripe_payment_intent_id' => $booking->stripe_payment_intent_id],
            );
        });

        try {
            // Step 2: main transaction — lock, validate, Stripe call, save, audit completed.
            $result = DB::transaction(function () use ($booking): array {
                $lockedBooking = Booking::query()
                    ->lockForUpdate()
                    ->findOrFail($booking->getKey());

                if ($lockedBooking->status === BookingStatus::Refunded || $lockedBooking->refunded_at !== null) {
                    return [
                        'booking_id' => $lockedBooking->getKey(),
                        'dispatch_event' => false,
                    ];
                }

                if (! in_array($lockedBooking->status, [BookingStatus::Cancelled, BookingStatus::Confirmed], true)) {
                    throw new InvalidArgumentException('Only cancelled or confirmed bookings can be refunded.');
                }

                if (! is_string($lockedBooking->stripe_payment_intent_id) || $lockedBooking->stripe_payment_intent_id === '') {
                    throw new InvalidArgumentException('Booking must have a Stripe payment intent before it can be refunded.');
                }

                $this->createStripeRefund([
                    'payment_intent' => $lockedBooking->stripe_payment_intent_id,
                ]);

                $lockedBooking->forceFill([
                    'status' => BookingStatus::Refunded,
                    'refunded_at' => now(),
                ])->save();

                $this->auditService->record(
                    AuditEventType::RefundCompleted,
                    AuditOperation::Payment,
                    $lockedBooking,
                    subjects: [AuditSubject::primary($lockedBooking)],
                    oldValues: ['status' => BookingStatus::Cancelled->value],
                    newValues: ['status' => BookingStatus::Refunded->value],
                );

                return [
                    'booking_id' => $lockedBooking->getKey(),
                    'dispatch_event' => true,
                ];
            });
        } catch (\Throwable $e) {
            // Step 3: record refund.failed in its own transaction, then rethrow.
            DB::transaction(function () use ($booking, $e): void {
                $this->auditService->record(
                    AuditEventType::RefundFailed,
                    AuditOperation::Payment,
                    $booking,
                    subjects: [AuditSubject::primary($booking)],
                    metadata: ['error' => $e->getMessage()],
                );
            });

            throw $e;
        }

        if ($result['dispatch_event']) {
            BookingRefunded::dispatch($result['booking_id']);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function createStripeRefund(array $payload): StripeRefund
    {
        if ($this->createRefundUsing instanceof Closure) {
            return ($this->createRefundUsing)($payload);
        }

        Stripe::setApiKey((string) config('services.stripe.secret'));

        return StripeRefund::create($payload);
    }
}
