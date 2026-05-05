<?php

declare(strict_types=1);

use App\Enums\BookingStatus;
use App\Events\BookingRefunded;
use App\Models\Booking;
use App\Services\Audit\AuditService;
use App\Services\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Stripe\Refund as StripeRefund;

uses(RefreshDatabase::class);

describe('refund', function () {
    it('creates Stripe refund and marks booking as refunded', function () {
        Event::fake([BookingRefunded::class]);

        $payloads = [];
        $booking = Booking::factory()->cancelled()->create([
            'stripe_payment_intent_id' => 'pi_refund_service_test',
        ]);

        $service = new RefundService(
            auditService: app(AuditService::class),
            createRefundUsing: function (array $payload) use (&$payloads): StripeRefund {
                $payloads[] = $payload;

                return StripeRefund::constructFrom([
                    'id' => 're_refund_service_test',
                ]);
            },
        );

        $service->refund($booking);

        expect($payloads)->toBe([
            ['payment_intent' => 'pi_refund_service_test'],
        ]);
        expect($booking->fresh()->status)->toBe(BookingStatus::Refunded)
            ->and($booking->fresh()->refunded_at)->not->toBeNull();

        Event::assertDispatched(
            BookingRefunded::class,
            fn (BookingRefunded $event): bool => $event->bookingId === $booking->id
        );
    });

    it('does not create a duplicate refund for an already refunded booking', function () {
        Event::fake([BookingRefunded::class]);

        $payloads = [];
        $booking = Booking::factory()->refunded()->create([
            'stripe_payment_intent_id' => 'pi_refund_service_existing',
        ]);

        $service = new RefundService(
            auditService: app(AuditService::class),
            createRefundUsing: function (array $payload) use (&$payloads): StripeRefund {
                $payloads[] = $payload;

                return StripeRefund::constructFrom([
                    'id' => 're_refund_service_existing',
                ]);
            },
        );

        $service->refund($booking);

        expect($payloads)->toBeEmpty();
        Event::assertNotDispatched(BookingRefunded::class);
    });
});
