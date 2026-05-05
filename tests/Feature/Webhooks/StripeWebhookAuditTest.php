<?php

declare(strict_types=1);

use App\Enums\AuditEventType;
use App\Models\AuditEvent;
use App\Models\Booking;
use App\Models\CoachProfile;
use App\Models\SportSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

/**
 * Generate a Stripe webhook signature for audit tests.
 * Uses a different name than generateStripeSignature in StripeWebhookTest.php.
 */
function generateWebhookAuditSignature(string $payload, string $secret): string
{
    $timestamp = time();
    $signedPayload = $timestamp.'.'.$payload;
    $signature = hash_hmac('sha256', $signedPayload, $secret);

    return 't='.$timestamp.',v1='.$signature;
}

function makeWebhookAuditPayload(string $eventId, string $eventType, array $data = []): string
{
    return json_encode([
        'id' => $eventId,
        'type' => $eventType,
        'object' => 'event',
        'api_version' => '2024-06-20',
        'created' => time(),
        'data' => ['object' => $data],
        'livemode' => false,
    ]);
}

/** Post a raw JSON Stripe webhook payload with a valid signature. */
function postWebhookAudit($test, string $payload, string $secret): TestResponse
{
    $sig = generateWebhookAuditSignature($payload, $secret);

    return $test->call('POST', '/stripe/webhook', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => $sig,
        'CONTENT_TYPE' => 'application/json',
    ], $payload);
}

describe('StripeWebhookController audit', function () {
    it('records booking.payment_confirmed when checkout.session.completed fires', function () {
        $secret = 'whsec_audit_test';
        config(['services.stripe.webhook.secret' => $secret]);

        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->for($coach)->create(['stripe_account_id' => 'acct_audit']);
        $session = SportSession::factory()->published()->for($coach, 'coach')->create([
            'min_participants' => 1,
            'current_participants' => 1,
        ]);
        $booking = Booking::factory()->pendingPayment()->for($session, 'sportSession')->create([
            'stripe_checkout_session_id' => 'cs_audit_checkout_001',
        ]);

        $payload = makeWebhookAuditPayload('evt_checkout_audit', 'checkout.session.completed', [
            'id' => 'cs_audit_checkout_001',
            'payment_intent' => 'pi_audit_checkout_001',
            'amount_total' => 2000,
            'metadata' => [
                'session_id' => (string) $session->id,
                'athlete_id' => (string) $booking->athlete_id,
            ],
        ]);

        postWebhookAudit($this, $payload, $secret);

        expect(
            AuditEvent::where('event_type', AuditEventType::BookingPaymentConfirmed->value)->exists()
        )->toBeTrue();
    });

    it('records booking.payment_confirmed when payment_intent.succeeded fires', function () {
        $secret = 'whsec_audit_pi_test';
        config(['services.stripe.webhook.secret' => $secret]);

        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->for($coach)->create(['stripe_account_id' => 'acct_audit_pi']);
        $session = SportSession::factory()->published()->for($coach, 'coach')->create([
            'min_participants' => 1,
            'current_participants' => 1,
        ]);
        $booking = Booking::factory()->pendingPayment()->for($session, 'sportSession')->create([
            'stripe_payment_intent_id' => 'pi_audit_pi_success',
        ]);

        $payload = makeWebhookAuditPayload('evt_pi_audit', 'payment_intent.succeeded', [
            'id' => 'pi_audit_pi_success',
            'amount_received' => 2000,
            'metadata' => [
                'session_id' => (string) $session->id,
                'athlete_id' => (string) $booking->athlete_id,
            ],
        ]);

        postWebhookAudit($this, $payload, $secret);

        expect(
            AuditEvent::where('event_type', AuditEventType::BookingPaymentConfirmed->value)->exists()
        )->toBeTrue();
    });

    it('records booking.payment_failed when payment_intent.payment_failed fires', function () {
        $secret = 'whsec_audit_fail_test';
        config(['services.stripe.webhook.secret' => $secret]);

        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->for($coach)->create(['stripe_account_id' => 'acct_audit_fail']);
        $session = SportSession::factory()->published()->for($coach, 'coach')->create([
            'current_participants' => 1,
        ]);
        $booking = Booking::factory()->pendingPayment()->for($session, 'sportSession')->create([
            'stripe_payment_intent_id' => 'pi_audit_fail_001',
        ]);

        $payload = makeWebhookAuditPayload('evt_pi_audit_fail', 'payment_intent.payment_failed', [
            'id' => 'pi_audit_fail_001',
            'metadata' => [
                'session_id' => (string) $session->id,
                'athlete_id' => (string) $booking->athlete_id,
            ],
        ]);

        postWebhookAudit($this, $payload, $secret);

        expect(
            AuditEvent::where('event_type', AuditEventType::BookingPaymentFailed->value)->exists()
        )->toBeTrue();
    });

    it('audit event has actor_type=stripe for webhook events', function () {
        $secret = 'whsec_actor_type_test';
        config(['services.stripe.webhook.secret' => $secret]);

        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->for($coach)->create(['stripe_account_id' => 'acct_actor']);
        $session = SportSession::factory()->published()->for($coach, 'coach')->create([
            'min_participants' => 1,
            'current_participants' => 1,
        ]);
        $booking = Booking::factory()->pendingPayment()->for($session, 'sportSession')->create([
            'stripe_checkout_session_id' => 'cs_actor_type_test',
        ]);

        $payload = makeWebhookAuditPayload('evt_actor_type', 'checkout.session.completed', [
            'id' => 'cs_actor_type_test',
            'payment_intent' => 'pi_actor_type_test',
            'amount_total' => 1500,
            'metadata' => [
                'session_id' => (string) $session->id,
                'athlete_id' => (string) $booking->athlete_id,
            ],
        ]);

        postWebhookAudit($this, $payload, $secret);

        $audit = AuditEvent::where('event_type', AuditEventType::BookingPaymentConfirmed->value)->firstOrFail();

        expect($audit->actor_type->value)->toBe('stripe');
    });
});
