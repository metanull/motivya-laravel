<?php

declare(strict_types=1);

use App\Events\Stripe\AccountUpdated;
use App\Events\Stripe\ChargeRefunded;
use App\Events\Stripe\PaymentIntentFailed;
use App\Events\Stripe\PaymentIntentSucceeded;
use App\Models\ProcessedWebhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

function generateStripeSignature(string $payload, string $secret): string
{
    $timestamp = time();
    $signedPayload = $timestamp.'.'.$payload;
    $signature = hash_hmac('sha256', $signedPayload, $secret);

    return 't='.$timestamp.',v1='.$signature;
}

function makeStripePayload(string $eventId, string $eventType, array $data = []): string
{
    return json_encode([
        'id' => $eventId,
        'type' => $eventType,
        'object' => 'event',
        'api_version' => '2024-06-20',
        'created' => time(),
        'data' => [
            'object' => $data ?: ['id' => 'obj_test_123'],
        ],
        'livemode' => false,
        'pending_webhooks' => 1,
        'request' => ['id' => 'req_test_123', 'idempotency_key' => null],
    ]);
}

describe('signature verification', function () {

    it('rejects requests with invalid signature', function () {
        config(['services.stripe.webhook.secret' => 'whsec_test_secret']);

        $payload = makeStripePayload('evt_test_123', 'payment_intent.succeeded');

        $response = $this->postJson('/stripe/webhook', [], [
            'HTTP_STRIPE_SIGNATURE' => 'invalid_signature',
            'CONTENT_TYPE' => 'application/json',
        ]);

        $response->assertStatus(400);
        $response->assertJson(['error' => 'Invalid signature']);
    });

    it('accepts requests with valid signature', function () {
        $secret = 'whsec_test_secret';
        config(['services.stripe.webhook.secret' => $secret]);
        Event::fake();

        $payload = makeStripePayload('evt_test_valid', 'payment_intent.succeeded');
        $signature = generateStripeSignature($payload, $secret);

        $response = $this->call('POST', '/stripe/webhook', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'processed']);
    });

});

describe('idempotency', function () {

    it('skips duplicate events', function () {
        $secret = 'whsec_test_secret';
        config(['services.stripe.webhook.secret' => $secret]);
        Event::fake();

        // Pre-record the event
        ProcessedWebhook::create([
            'stripe_event_id' => 'evt_duplicate_123',
            'event_type' => 'payment_intent.succeeded',
        ]);

        $payload = makeStripePayload('evt_duplicate_123', 'payment_intent.succeeded');
        $signature = generateStripeSignature($payload, $secret);

        $response = $this->call('POST', '/stripe/webhook', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'already_processed']);

        // Should still be only one record
        expect(ProcessedWebhook::where('stripe_event_id', 'evt_duplicate_123')->count())->toBe(1);
    });

    it('records processed events', function () {
        $secret = 'whsec_test_secret';
        config(['services.stripe.webhook.secret' => $secret]);
        Event::fake();

        $payload = makeStripePayload('evt_new_123', 'payment_intent.succeeded');
        $signature = generateStripeSignature($payload, $secret);

        $this->call('POST', '/stripe/webhook', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        expect(ProcessedWebhook::where('stripe_event_id', 'evt_new_123')->exists())->toBeTrue();
    });

});

describe('event dispatching', function () {

    it('dispatches PaymentIntentSucceeded for payment_intent.succeeded', function () {
        $secret = 'whsec_test_secret';
        config(['services.stripe.webhook.secret' => $secret]);
        Event::fake();

        $payload = makeStripePayload('evt_pi_success', 'payment_intent.succeeded');
        $signature = generateStripeSignature($payload, $secret);

        $this->call('POST', '/stripe/webhook', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        Event::assertDispatched(PaymentIntentSucceeded::class);
    });

    it('dispatches PaymentIntentFailed for payment_intent.payment_failed', function () {
        $secret = 'whsec_test_secret';
        config(['services.stripe.webhook.secret' => $secret]);
        Event::fake();

        $payload = makeStripePayload('evt_pi_failed', 'payment_intent.payment_failed');
        $signature = generateStripeSignature($payload, $secret);

        $this->call('POST', '/stripe/webhook', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        Event::assertDispatched(PaymentIntentFailed::class);
    });

    it('dispatches AccountUpdated for account.updated', function () {
        $secret = 'whsec_test_secret';
        config(['services.stripe.webhook.secret' => $secret]);
        Event::fake();

        $payload = makeStripePayload('evt_acct_updated', 'account.updated');
        $signature = generateStripeSignature($payload, $secret);

        $this->call('POST', '/stripe/webhook', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        Event::assertDispatched(AccountUpdated::class);
    });

    it('dispatches ChargeRefunded for charge.refunded', function () {
        $secret = 'whsec_test_secret';
        config(['services.stripe.webhook.secret' => $secret]);
        Event::fake();

        $payload = makeStripePayload('evt_charge_refunded', 'charge.refunded');
        $signature = generateStripeSignature($payload, $secret);

        $this->call('POST', '/stripe/webhook', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        Event::assertDispatched(ChargeRefunded::class);
    });

    it('does not dispatch for unhandled event types', function () {
        $secret = 'whsec_test_secret';
        config(['services.stripe.webhook.secret' => $secret]);
        Event::fake();

        $payload = makeStripePayload('evt_unknown', 'customer.created');
        $signature = generateStripeSignature($payload, $secret);

        $this->call('POST', '/stripe/webhook', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        Event::assertNotDispatched(PaymentIntentSucceeded::class);
        Event::assertNotDispatched(PaymentIntentFailed::class);
        Event::assertNotDispatched(AccountUpdated::class);
        Event::assertNotDispatched(ChargeRefunded::class);
    });

});
