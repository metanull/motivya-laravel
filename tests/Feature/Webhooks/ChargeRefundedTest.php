<?php

declare(strict_types=1);

use App\Enums\BookingStatus;
use App\Events\BookingRefunded;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->stripeWebhookSecret = 'whsec_charge_refunded';
    config(['services.stripe.webhook.secret' => $this->stripeWebhookSecret]);

    $this->postStripeWebhook = function (string $eventId, string $eventType, array $data): TestResponse {
        $payload = json_encode([
            'id' => $eventId,
            'type' => $eventType,
            'object' => 'event',
            'api_version' => '2024-06-20',
            'created' => time(),
            'data' => ['object' => $data],
            'livemode' => false,
            'pending_webhooks' => 1,
        ], JSON_THROW_ON_ERROR);

        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, $this->stripeWebhookSecret);

        return $this->call('POST', '/stripe/webhook', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => 't='.$timestamp.',v1='.$signature,
            'CONTENT_TYPE' => 'application/json',
        ], $payload);
    };
});

describe('charge.refunded webhook', function () {
    it('marks the booking as refunded and dispatches BookingRefunded', function () {
        Event::fake([BookingRefunded::class]);

        $booking = Booking::factory()->cancelled()->create([
            'stripe_payment_intent_id' => 'pi_charge_refunded',
        ]);

        $response = ($this->postStripeWebhook)('evt_charge_refunded_processed', 'charge.refunded', [
            'id' => 'ch_charge_refunded',
            'payment_intent' => 'pi_charge_refunded',
        ]);

        $response->assertOk()->assertJson(['status' => 'processed']);

        expect($booking->fresh()->status)->toBe(BookingStatus::Refunded)
            ->and($booking->fresh()->refunded_at)->not->toBeNull();

        Event::assertDispatched(
            BookingRefunded::class,
            fn (BookingRefunded $event): bool => $event->bookingId === $booking->id
        );
    });
});
