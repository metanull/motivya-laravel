<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BookingStatus;
use App\Events\BookingRefunded;
use App\Models\Booking;
use Closure;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Stripe\Refund as StripeRefund;
use Stripe\Stripe;

final class RefundService
{
    public function __construct(
        private readonly ?Closure $createRefundUsing = null,
    ) {}

    /**
     * Create a Stripe refund for a booking and mark it as refunded.
     */
    public function refund(Booking $booking): void
    {
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

            return [
                'booking_id' => $lockedBooking->getKey(),
                'dispatch_event' => true,
            ];
        });

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
