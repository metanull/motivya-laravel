<?php

declare(strict_types=1);

use App\Enums\BookingStatus;
use App\Enums\SessionStatus;
use App\Events\BookingCreated;
use App\Events\SessionConfirmed;
use App\Models\Booking;
use App\Models\ProcessedWebhook;
use App\Models\SportSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->stripeWebhookSecret = 'whsec_checkout_completed';
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

describe('checkout.session.completed webhook', function () {
    it('confirms the booking and dispatches BookingCreated', function () {
        Event::fake([BookingCreated::class, SessionConfirmed::class]);

        $sportSession = SportSession::factory()->published()->create();
        $booking = Booking::factory()->pendingPayment()->for($sportSession, 'sportSession')->create([
            'stripe_checkout_session_id' => 'cs_booking_completed',
        ]);

        $response = ($this->postStripeWebhook)('evt_checkout_completed', 'checkout.session.completed', [
            'id' => 'cs_booking_completed',
            'payment_intent' => 'pi_from_checkout',
            'amount_total' => 2500,
            'metadata' => [
                'session_id' => (string) $sportSession->id,
                'athlete_id' => (string) $booking->athlete_id,
            ],
        ]);

        $response->assertOk()->assertJson(['status' => 'processed']);

        $booking = $booking->fresh();

        expect($booking->status)->toBe(BookingStatus::Confirmed);
        expect($booking->amount_paid)->toBe(2500);
        expect($booking->stripe_payment_intent_id)->toBe('pi_from_checkout');
        expect(ProcessedWebhook::where('stripe_event_id', 'evt_checkout_completed')->exists())->toBeTrue();

        Event::assertDispatched(BookingCreated::class, fn (BookingCreated $event): bool => $event->bookingId === $booking->id);
    });

    it('confirms the session when confirmed bookings reach the min_participants threshold', function () {
        Event::fake([BookingCreated::class, SessionConfirmed::class]);

        $sportSession = SportSession::factory()->published()->create([
            'min_participants' => 2,
            'max_participants' => 5,
            'current_participants' => 2,
        ]);

        // First confirmed booking already exists
        Booking::factory()->confirmed()->for($sportSession, 'sportSession')->create();

        // Second booking is pending payment and about to be confirmed by the webhook
        $pendingBooking = Booking::factory()->pendingPayment()->for($sportSession, 'sportSession')->create([
            'stripe_checkout_session_id' => 'cs_threshold_confirm',
        ]);

        $response = ($this->postStripeWebhook)('evt_cs_threshold', 'checkout.session.completed', [
            'id' => 'cs_threshold_confirm',
            'payment_intent' => 'pi_threshold',
            'amount_total' => 2000,
            'metadata' => [
                'session_id' => (string) $sportSession->id,
                'athlete_id' => (string) $pendingBooking->athlete_id,
            ],
        ]);

        $response->assertOk()->assertJson(['status' => 'processed']);

        expect($pendingBooking->fresh()->status)->toBe(BookingStatus::Confirmed);
        expect($sportSession->fresh()->status)->toBe(SessionStatus::Confirmed);

        Event::assertDispatched(
            SessionConfirmed::class,
            fn (SessionConfirmed $event): bool => $event->sessionId === $sportSession->id
        );
    });

    it('does not confirm an expired pending-payment booking', function () {
        Event::fake([BookingCreated::class, SessionConfirmed::class]);

        $sportSession = SportSession::factory()->published()->create();
        $booking = Booking::factory()->withExpiredPayment()->for($sportSession, 'sportSession')->create([
            'stripe_checkout_session_id' => 'cs_expired_booking',
        ]);

        $response = ($this->postStripeWebhook)('evt_cs_expired', 'checkout.session.completed', [
            'id' => 'cs_expired_booking',
            'payment_intent' => 'pi_expired',
            'amount_total' => 1500,
            'metadata' => [
                'session_id' => (string) $sportSession->id,
                'athlete_id' => (string) $booking->athlete_id,
            ],
        ]);

        $response->assertOk()->assertJson(['status' => 'processed']);

        // Booking must stay in pending_payment — webhook must not confirm it
        expect($booking->fresh()->status)->toBe(BookingStatus::PendingPayment);

        Event::assertNotDispatched(BookingCreated::class);
        Event::assertNotDispatched(SessionConfirmed::class);
    });

    it('falls back to metadata when stripe_checkout_session_id is not stored', function () {
        Event::fake([BookingCreated::class]);

        $sportSession = SportSession::factory()->published()->create();
        $booking = Booking::factory()->pendingPayment()->for($sportSession, 'sportSession')->create([
            'stripe_checkout_session_id' => null,
        ]);

        $response = ($this->postStripeWebhook)('evt_cs_metadata_fallback', 'checkout.session.completed', [
            'id' => 'cs_unknown_id',
            'payment_intent' => 'pi_fallback',
            'amount_total' => 1800,
            'metadata' => [
                'session_id' => (string) $sportSession->id,
                'athlete_id' => (string) $booking->athlete_id,
            ],
        ]);

        $response->assertOk()->assertJson(['status' => 'processed']);

        expect($booking->fresh()->status)->toBe(BookingStatus::Confirmed);
        expect($booking->fresh()->stripe_payment_intent_id)->toBe('pi_fallback');
    });
});
