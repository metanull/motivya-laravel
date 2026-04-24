<?php

declare(strict_types=1);

use App\Enums\BookingStatus;
use App\Events\BookingCancelled;
use App\Models\Booking;
use App\Models\SportSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->stripeWebhookSecret = 'whsec_payment_failed';
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

describe('payment_intent.payment_failed webhook', function () {
    it('cancels the booking, releases the slot, and dispatches BookingCancelled', function () {
        Event::fake([BookingCancelled::class]);

        $session = SportSession::factory()->published()->create([
            'current_participants' => 1,
        ]);
        $booking = Booking::factory()->pendingPayment()->for($session, 'sportSession')->create([
            'stripe_payment_intent_id' => 'pi_booking_failed',
        ]);

        $response = ($this->postStripeWebhook)('evt_payment_failed', 'payment_intent.payment_failed', [
            'id' => 'pi_booking_failed',
            'metadata' => [
                'session_id' => (string) $session->id,
                'athlete_id' => (string) $booking->athlete_id,
            ],
        ]);

        $response->assertOk()->assertJson(['status' => 'processed']);

        expect($booking->fresh()->status)->toBe(BookingStatus::Cancelled);
        expect($booking->fresh()->cancelled_at)->not->toBeNull();
        expect($session->fresh()->current_participants)->toBe(0);

        Event::assertDispatched(
            BookingCancelled::class,
            fn (BookingCancelled $event): bool => $event->bookingId === $booking->id
                && $event->reason === 'payment_failed'
                && $event->refundEligible === false
        );
    });
});
