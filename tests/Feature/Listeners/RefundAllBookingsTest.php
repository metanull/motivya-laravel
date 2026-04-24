<?php

declare(strict_types=1);

use App\Enums\BookingStatus;
use App\Events\SessionCancelled;
use App\Models\Booking;
use App\Models\SportSession;
use App\Services\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\Refund as StripeRefund;

uses(RefreshDatabase::class);

describe('RefundAllBookingsOnSessionCancellation', function () {
    it('refunds all confirmed bookings for a cancelled session', function () {
        $refundPayloads = [];
        app()->instance(RefundService::class, new RefundService(
            createRefundUsing: function (array $payload) use (&$refundPayloads): StripeRefund {
                $refundPayloads[] = $payload;

                return StripeRefund::constructFrom([
                    'id' => 're_listener_test_'.count($refundPayloads),
                ]);
            },
        ));

        $session = SportSession::factory()->confirmed()->create();
        $confirmedBookings = Booking::factory()
            ->count(3)
            ->confirmed()
            ->for($session, 'sportSession')
            ->sequence(
                ['stripe_payment_intent_id' => 'pi_listener_refund_1'],
                ['stripe_payment_intent_id' => 'pi_listener_refund_2'],
                ['stripe_payment_intent_id' => 'pi_listener_refund_3'],
            )
            ->create();

        $cancelledBooking = Booking::factory()
            ->cancelled()
            ->for($session, 'sportSession')
            ->create([
                'stripe_payment_intent_id' => 'pi_listener_refund_cancelled',
            ]);

        event(new SessionCancelled($session));

        expect($refundPayloads)->toHaveCount(3)
            ->and(collect($refundPayloads)->pluck('payment_intent')->all())->toBe([
                'pi_listener_refund_1',
                'pi_listener_refund_2',
                'pi_listener_refund_3',
            ]);

        expect($confirmedBookings->fresh()->every(fn (Booking $booking): bool => $booking->status === BookingStatus::Refunded))->toBeTrue();
        expect($cancelledBooking->fresh()->status)->toBe(BookingStatus::Cancelled);
    });
});
