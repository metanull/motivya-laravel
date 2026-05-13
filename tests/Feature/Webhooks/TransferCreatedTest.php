<?php

declare(strict_types=1);

use App\Enums\PaymentAnomalyType;
use App\Models\Booking;
use App\Models\PaymentAnomaly;
use App\Models\SportSession;
use App\Models\StripeTransfer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->stripeWebhookSecret = 'whsec_transfer_created';
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

describe('transfer.created webhook', function () {

    it('records a StripeTransfer linked to a booking resolved by PaymentIntent ID', function () {
        $session = SportSession::factory()->confirmed()->create();
        $booking = Booking::factory()->confirmed()->for($session, 'sportSession')->create([
            'stripe_payment_intent_id' => 'pi_transfer_test',
            'amount_paid' => 2000,
        ]);

        $response = ($this->postStripeWebhook)('evt_transfer_001', 'transfer.created', [
            'id' => 'tr_transfer_001',
            'object' => 'transfer',
            'amount' => 1400,
            'currency' => 'eur',
            'destination' => 'acct_coach_001',
            'source_transaction' => 'pi_transfer_test',
            'created' => time(),
            'metadata' => [],
        ]);

        $response->assertOk()->assertJson(['status' => 'processed']);

        $transfer = StripeTransfer::where('stripe_transfer_id', 'tr_transfer_001')->first();
        expect($transfer)->not->toBeNull();
        expect($transfer->booking_id)->toBe($booking->id);
        expect($transfer->amount)->toBe(1400);
        expect($transfer->destination_account_id)->toBe('acct_coach_001');
    });

    it('is idempotent on repeated delivery of the same transfer event', function () {
        $session = SportSession::factory()->confirmed()->create();
        Booking::factory()->confirmed()->for($session, 'sportSession')->create([
            'stripe_payment_intent_id' => 'pi_idem_transfer',
            'amount_paid' => 1500,
        ]);

        ($this->postStripeWebhook)('evt_transfer_idem', 'transfer.created', [
            'id' => 'tr_idem_001',
            'object' => 'transfer',
            'amount' => 1000,
            'currency' => 'eur',
            'destination' => 'acct_coach_002',
            'source_transaction' => 'pi_idem_transfer',
            'created' => time(),
            'metadata' => [],
        ]);

        // Second delivery with a different event ID but same transfer ID.
        ($this->postStripeWebhook)('evt_transfer_idem_dup', 'transfer.created', [
            'id' => 'tr_idem_001',
            'object' => 'transfer',
            'amount' => 1000,
            'currency' => 'eur',
            'destination' => 'acct_coach_002',
            'source_transaction' => 'pi_idem_transfer',
            'created' => time(),
            'metadata' => [],
        ]);

        expect(StripeTransfer::where('stripe_transfer_id', 'tr_idem_001')->count())->toBe(1);
    });

    it('creates a PaymentAnomaly when the booking cannot be resolved', function () {
        $response = ($this->postStripeWebhook)('evt_transfer_unresolved', 'transfer.created', [
            'id' => 'tr_unresolved_001',
            'object' => 'transfer',
            'amount' => 800,
            'currency' => 'eur',
            'destination' => 'acct_coach_003',
            'source_transaction' => 'pi_unknown_intent',
            'created' => time(),
            'metadata' => [],
        ]);

        $response->assertOk()->assertJson(['status' => 'processed']);

        expect(StripeTransfer::where('stripe_transfer_id', 'tr_unresolved_001')->exists())->toBeFalse();
        expect(
            PaymentAnomaly::where('anomaly_type', PaymentAnomalyType::UnresolvedStripeTransfer->value)->exists(),
        )->toBeTrue();
    });

    it('resolves the booking by metadata when PaymentIntent ID does not match any booking', function () {
        $session = SportSession::factory()->confirmed()->create();
        $booking = Booking::factory()->confirmed()->for($session, 'sportSession')->create([
            'stripe_payment_intent_id' => null,
            'amount_paid' => 1200,
        ]);

        $response = ($this->postStripeWebhook)('evt_transfer_meta', 'transfer.created', [
            'id' => 'tr_meta_001',
            'object' => 'transfer',
            'amount' => 900,
            'currency' => 'eur',
            'destination' => 'acct_coach_004',
            'source_transaction' => null,
            'created' => time(),
            'metadata' => [
                'session_id' => (string) $session->id,
                'athlete_id' => (string) $booking->athlete_id,
            ],
        ]);

        $response->assertOk()->assertJson(['status' => 'processed']);

        $transfer = StripeTransfer::where('stripe_transfer_id', 'tr_meta_001')->first();
        expect($transfer)->not->toBeNull();
        expect($transfer->booking_id)->toBe($booking->id);
    });
});
