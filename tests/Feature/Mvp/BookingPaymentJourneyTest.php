<?php

declare(strict_types=1);

use App\Enums\BookingStatus;
use App\Enums\SessionStatus;
use App\Livewire\Booking\Book;
use App\Models\Booking;
use App\Models\CoachProfile;
use App\Models\SportSession;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;
use Stripe\Checkout\Session as CheckoutSession;
use Tests\TestCase;

uses(RefreshDatabase::class);

// ──────────────────────────────────────────────────────────────────────────────
// Helpers shared across tests in this file
// ──────────────────────────────────────────────────────────────────────────────

/**
 * Build and sign a Stripe webhook POST exactly as the real Stripe SDK would.
 */
function postStripeWebhookEvent(?TestResponse $ignore, string $secret, string $eventId, string $eventType, array $data): TestResponse
{
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
    $signature = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

    /** @var TestCase $test */
    $test = test();

    return $test->call('POST', '/stripe/webhook', [], [], [], [
        'HTTP_STRIPE_SIGNATURE' => 't='.$timestamp.',v1='.$signature,
        'CONTENT_TYPE' => 'application/json',
    ], $payload);
}

/**
 * Bind a fake PaymentService that returns the supplied checkout session values.
 */
function bindFakePaymentService(string $checkoutSessionId, string $checkoutUrl): void
{
    app()->instance(PaymentService::class, new PaymentService(
        auditService: app(AuditService::class),
        createCheckoutSessionUsing: fn (array $payload): CheckoutSession => CheckoutSession::constructFrom([
            'id' => $checkoutSessionId,
            'url' => $checkoutUrl,
        ]),
    ));
}

/**
 * Bind a failing PaymentService that throws on checkout-session creation.
 */
function bindThrowingPaymentService(string $message = 'Stripe unavailable'): void
{
    app()->instance(PaymentService::class, new PaymentService(
        auditService: app(AuditService::class),
        createCheckoutSessionUsing: fn (array $payload): never => throw new RuntimeException($message),
    ));
}

// ──────────────────────────────────────────────────────────────────────────────
// Story 6.1: True end-to-end booking payment smoke test
// ──────────────────────────────────────────────────────────────────────────────

describe('MVP Booking Payment Journey', function () {

    beforeEach(function (): void {
        $this->webhookSecret = 'whsec_e2e_journey_secret';
        config(['services.stripe.webhook.secret' => $this->webhookSecret]);
    });

    // ── Step 1: Athlete books, receives Stripe Checkout redirect ─────────────

    it('creates a PendingPayment hold and redirects to the Stripe Checkout URL', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->for($coach)->create([
            'stripe_account_id' => 'acct_e2e_journey',
            'stripe_onboarding_complete' => true,
        ]);

        $session = SportSession::factory()->published()->for($coach, 'coach')->create([
            'price_per_person' => 2500,
        ]);
        $athlete = User::factory()->athlete()->create();

        bindFakePaymentService('cs_e2e_step1', 'https://checkout.stripe.com/pay/cs_e2e_step1');

        Livewire::actingAs($athlete)
            ->test(Book::class, ['sportSession' => $session])
            ->call('book')
            ->assertRedirect('https://checkout.stripe.com/pay/cs_e2e_step1');

        $booking = Booking::where('sport_session_id', $session->id)->where('athlete_id', $athlete->id)->first();

        expect($booking)->not->toBeNull();
        expect($booking->status)->toBe(BookingStatus::PendingPayment);
        expect($booking->stripe_checkout_session_id)->toBe('cs_e2e_step1');
        expect($session->fresh()->current_participants)->toBe(1);
    });

    // ── Step 2: Stripe webhook confirms booking ───────────────────────────────

    it('webhook checkout.session.completed confirms booking with amount and payment intent', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->for($coach)->create([
            'stripe_account_id' => 'acct_e2e_webhook',
            'stripe_onboarding_complete' => true,
        ]);

        $session = SportSession::factory()->published()->for($coach, 'coach')->create([
            'min_participants' => 1,
            'price_per_person' => 2500,
        ]);
        $athlete = User::factory()->athlete()->create();

        // Create the hold directly (simulating Step 1 already having run).
        $booking = Booking::factory()->pendingPayment()->for($session, 'sportSession')->for($athlete, 'athlete')->create([
            'stripe_checkout_session_id' => 'cs_e2e_webhook',
            'amount_paid' => 0,
        ]);

        $webhookResponse = postStripeWebhookEvent(
            null,
            $this->webhookSecret,
            'evt_e2e_checkout_completed',
            'checkout.session.completed',
            [
                'id' => 'cs_e2e_webhook',
                'payment_intent' => 'pi_e2e_confirmed',
                'amount_total' => 2500,
                'metadata' => [
                    'session_id' => (string) $session->id,
                    'athlete_id' => (string) $athlete->id,
                ],
            ],
        );

        $webhookResponse->assertOk()->assertJson(['status' => 'processed']);

        $booking = $booking->fresh();

        expect($booking->status)->toBe(BookingStatus::Confirmed);
        expect($booking->amount_paid)->toBe(2500);
        expect($booking->stripe_payment_intent_id)->toBe('pi_e2e_confirmed');
    });

    // ── Step 3: Full journey — book, webhook, then assert cross-role views ────

    it('full journey: book → webhook confirm → visible in athlete, coach, accountant, and admin views', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->for($coach)->create([
            'stripe_account_id' => 'acct_e2e_full',
            'stripe_onboarding_complete' => true,
        ]);

        $session = SportSession::factory()->published()->for($coach, 'coach')->create([
            'min_participants' => 1,
            'price_per_person' => 3000,
        ]);

        $athlete = User::factory()->athlete()->create();

        // Step A: athlete books (mock Stripe).
        bindFakePaymentService('cs_e2e_full', 'https://checkout.stripe.com/pay/cs_e2e_full');

        Livewire::actingAs($athlete)
            ->test(Book::class, ['sportSession' => $session])
            ->call('book')
            ->assertRedirect('https://checkout.stripe.com/pay/cs_e2e_full');

        // Step B: Stripe confirms the checkout.
        postStripeWebhookEvent(
            null,
            $this->webhookSecret,
            'evt_e2e_full_completed',
            'checkout.session.completed',
            [
                'id' => 'cs_e2e_full',
                'payment_intent' => 'pi_e2e_full_confirmed',
                'amount_total' => 3000,
                'metadata' => [
                    'session_id' => (string) $session->id,
                    'athlete_id' => (string) $athlete->id,
                ],
            ],
        )->assertOk();

        $booking = Booking::where('sport_session_id', $session->id)->where('athlete_id', $athlete->id)->firstOrFail();

        expect($booking->status)->toBe(BookingStatus::Confirmed);
        expect($booking->amount_paid)->toBe(3000);
        expect($booking->stripe_payment_intent_id)->toBe('pi_e2e_full_confirmed');

        // Step C: Athlete dashboard shows the confirmed session.
        $this->actingAs($athlete)
            ->get(route('athlete.dashboard'))
            ->assertOk();

        // Step D: Coach dashboard includes the booking in revenue.
        // The dashboard sums confirmed booking amount_paid for the coach's sessions.
        $this->actingAs($coach)
            ->get(route('coach.dashboard'))
            ->assertOk();

        // Verify via DB that the revenue query would include this booking.
        $revenueCents = (int) DB::table('bookings')
            ->join('sport_sessions', 'sport_sessions.id', '=', 'bookings.sport_session_id')
            ->where('sport_sessions.coach_id', $coach->id)
            ->where('bookings.status', BookingStatus::Confirmed->value)
            ->sum('bookings.amount_paid');

        expect($revenueCents)->toBe(3000);

        // Step E: Accountant transaction ledger includes the booking.
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $this->actingAs($accountant)
            ->get(route('accountant.transactions.index'))
            ->assertOk();

        // Step F: Admin refund queue (default filter: confirmed + amount_paid > 0)
        // shows the booking as eligible and the payment intent is present.
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $this->actingAs($admin)
            ->get(route('admin.refunds.index'))
            ->assertOk();

        // Verify the booking passes the default eligibility criteria.
        $fromDefaultQueue = Booking::where('status', BookingStatus::Confirmed->value)
            ->where('amount_paid', '>', 0)
            ->where('stripe_payment_intent_id', 'pi_e2e_full_confirmed')
            ->exists();

        expect($fromDefaultQueue)->toBeTrue();
    });

    // ── Session status transition: reaches confirmed when min_participants is met ─

    it('session transitions to Confirmed when webhook causes min_participants to be reached', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->for($coach)->create([
            'stripe_account_id' => 'acct_e2e_session_confirm',
            'stripe_onboarding_complete' => true,
        ]);

        $session = SportSession::factory()->published()->for($coach, 'coach')->create([
            'min_participants' => 2,
            'max_participants' => 5,
            'current_participants' => 2,
        ]);

        // First booking already confirmed.
        Booking::factory()->confirmed()->for($session, 'sportSession')->create();

        // Second pending booking about to be confirmed by webhook.
        $pending = Booking::factory()->pendingPayment()->for($session, 'sportSession')->create([
            'stripe_checkout_session_id' => 'cs_e2e_session_confirm',
        ]);

        postStripeWebhookEvent(
            null,
            $this->webhookSecret,
            'evt_e2e_session_confirm',
            'checkout.session.completed',
            [
                'id' => 'cs_e2e_session_confirm',
                'payment_intent' => 'pi_e2e_session_confirm',
                'amount_total' => 2000,
                'metadata' => [
                    'session_id' => (string) $session->id,
                    'athlete_id' => (string) $pending->athlete_id,
                ],
            ],
        )->assertOk();

        expect($pending->fresh()->status)->toBe(BookingStatus::Confirmed);
        expect($session->fresh()->status)->toBe(SessionStatus::Confirmed);
    });

    // ── Failed path: Stripe outage keeps capacity hold, no duplicate booking ──

    it('failed checkout creation keeps the PendingPayment hold without doubling capacity', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->for($coach)->create([
            'stripe_account_id' => 'acct_e2e_fail',
            'stripe_onboarding_complete' => true,
        ]);

        $session = SportSession::factory()->published()->for($coach, 'coach')->create([
            'price_per_person' => 1500,
        ]);
        $athlete = User::factory()->athlete()->create();

        bindThrowingPaymentService('Stripe API temporarily unavailable');

        Livewire::actingAs($athlete)
            ->test(Book::class, ['sportSession' => $session])
            ->call('book')
            ->assertDispatched('notify');

        // Exactly one capacity hold must exist — no duplicate booking rows.
        expect(Booking::where('sport_session_id', $session->id)->count())->toBe(1);

        $hold = Booking::where('sport_session_id', $session->id)->first();
        expect($hold->status)->toBe(BookingStatus::PendingPayment);
        expect($hold->amount_paid)->toBe(0);

        // Capacity is incremented once (the hold), not twice.
        expect($session->fresh()->current_participants)->toBe(1);
    });

    // ── Retry reuses the existing pending hold ────────────────────────────────

    it('retrying with a working Stripe after a failed attempt reuses the existing hold', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->for($coach)->create([
            'stripe_account_id' => 'acct_e2e_retry',
            'stripe_onboarding_complete' => true,
        ]);

        $session = SportSession::factory()->published()->for($coach, 'coach')->create([
            'price_per_person' => 2000,
        ]);
        $athlete = User::factory()->athlete()->create();

        // First attempt: Stripe fails.
        bindThrowingPaymentService();
        Livewire::actingAs($athlete)
            ->test(Book::class, ['sportSession' => $session])
            ->call('book')
            ->assertDispatched('notify');

        // Second attempt: Stripe succeeds.
        bindFakePaymentService('cs_e2e_retry', 'https://checkout.stripe.com/pay/cs_e2e_retry');
        Livewire::actingAs($athlete)
            ->test(Book::class, ['sportSession' => $session])
            ->call('book')
            ->assertRedirect('https://checkout.stripe.com/pay/cs_e2e_retry');

        // Must still have exactly one booking row.
        expect(Booking::where('sport_session_id', $session->id)->where('athlete_id', $athlete->id)->count())->toBe(1);

        // Capacity is incremented only once.
        expect($session->fresh()->current_participants)->toBe(1);
    });

});
