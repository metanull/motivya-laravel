<?php

declare(strict_types=1);

use App\Enums\BookingStatus;
use App\Events\BookingCreated;
use App\Models\Booking;
use App\Models\ProcessedWebhook;
use App\Models\SportSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->stripeWebhookSecret = 'whsec_payment_succeeded';
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

describe('payment_intent.succeeded webhook', function () {
    it('confirms the booking and dispatches BookingCreated', function () {
        Event::fake([BookingCreated::class]);

        $session = SportSession::factory()->published()->create();
        $booking = Booking::factory()->pendingPayment()->for($session, 'sportSession')->create([
            'stripe_payment_intent_id' => 'pi_booking_success',
            'amount_paid' => 1500,
        ]);

        $response = ($this->postStripeWebhook)('evt_payment_success', 'payment_intent.succeeded', [
            'id' => 'pi_booking_success',
            'amount_received' => 1800,
            'metadata' => [
                'session_id' => (string) $session->id,
                'athlete_id' => (string) $booking->athlete_id,
            ],
        ]);

        $response->assertOk()->assertJson(['status' => 'processed']);

        $booking = $booking->fresh();

        expect($booking->status)->toBe(BookingStatus::Confirmed);
        expect($booking->amount_paid)->toBe(1800);
        expect(ProcessedWebhook::where('stripe_event_id', 'evt_payment_success')->exists())->toBeTrue();

        Event::assertDispatched(BookingCreated::class, fn (BookingCreated $event): bool => $event->bookingId === $booking->id);
    });
});
