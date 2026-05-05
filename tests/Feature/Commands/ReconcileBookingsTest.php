<?php

declare(strict_types=1);

use App\Console\Commands\ReconcileBookings;
use App\Enums\AuditEventType;
use App\Models\AuditEvent;
use App\Models\Booking;
use App\Models\SportSession;
use App\Models\User;
use App\Services\Audit\AuditContextResolver;
use App\Services\Audit\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\Checkout\Session as StripeCheckoutSession;

uses(RefreshDatabase::class);

/**
 * Build a testable ReconcileBookings command with a mock Stripe retrieval closure.
 */
function makeReconcileCommand(?Closure $retrieveUsing = null): ReconcileBookings
{
    return new ReconcileBookings(
        auditService: app(AuditService::class),
        contextResolver: app(AuditContextResolver::class),
        retrieveCheckoutSessionUsing: $retrieveUsing,
    );
}

describe('payments:reconcile-bookings', function () {

    it('exits with success and reports nothing when no bookings need reconciliation', function () {
        $this->artisan('payments:reconcile-bookings')
            ->expectsOutput('No bookings require reconciliation.')
            ->assertExitCode(0);
    });

    it('lists unreconciled bookings in dry-run and makes no changes', function () {
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->confirmed()->for($coach, 'coach')->create();
        $booking = Booking::factory()->confirmed()->for($session, 'sportSession')->create([
            'amount_paid' => 2500,
            'stripe_payment_intent_id' => null,
            'stripe_checkout_session_id' => 'cs_dry_run',
        ]);

        $this->artisan('payments:reconcile-bookings')
            ->assertExitCode(0);

        // Dry-run must not modify the booking.
        $booking->refresh();
        expect($booking->stripe_payment_intent_id)->toBeNull();
    });

    it('repairs a booking when --repair and payment intent is found in Stripe', function () {
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->confirmed()->for($coach, 'coach')->create();
        $booking = Booking::factory()->confirmed()->for($session, 'sportSession')->create([
            'amount_paid' => 2500,
            'stripe_payment_intent_id' => null,
            'stripe_checkout_session_id' => 'cs_repair_test',
        ]);

        $command = makeReconcileCommand(
            fn (string $csId): StripeCheckoutSession => StripeCheckoutSession::constructFrom([
                'id' => $csId,
                'payment_intent' => 'pi_reconciled_001',
            ]),
        );

        app()->instance(ReconcileBookings::class, $command);

        $this->artisan('payments:reconcile-bookings', ['--repair' => true])
            ->assertExitCode(0);

        $booking->refresh();
        expect($booking->stripe_payment_intent_id)->toBe('pi_reconciled_001');
    });

    it('creates an audit event on successful repair', function () {
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->confirmed()->for($coach, 'coach')->create();
        $booking = Booking::factory()->confirmed()->for($session, 'sportSession')->create([
            'amount_paid' => 1500,
            'stripe_payment_intent_id' => null,
            'stripe_checkout_session_id' => 'cs_audit_test',
        ]);

        $command = makeReconcileCommand(
            fn (string $csId): StripeCheckoutSession => StripeCheckoutSession::constructFrom([
                'id' => $csId,
                'payment_intent' => 'pi_audit_001',
            ]),
        );

        app()->instance(ReconcileBookings::class, $command);

        $this->artisan('payments:reconcile-bookings', ['--repair' => true])
            ->assertExitCode(0);

        expect(
            AuditEvent::where('event_type', AuditEventType::BookingPaymentReconciled->value)->exists(),
        )->toBeTrue();
    });

    it('skips bookings without a checkout session ID', function () {
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->confirmed()->for($coach, 'coach')->create();
        $booking = Booking::factory()->confirmed()->for($session, 'sportSession')->create([
            'amount_paid' => 1000,
            'stripe_payment_intent_id' => null,
            'stripe_checkout_session_id' => null,
        ]);

        $this->artisan('payments:reconcile-bookings', ['--repair' => true])
            ->assertExitCode(0);

        $booking->refresh();
        expect($booking->stripe_payment_intent_id)->toBeNull();
    });

    it('refuses to repair if the same payment intent belongs to another booking', function () {
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->confirmed()->for($coach, 'coach')->create();

        Booking::factory()->confirmed()->for($session, 'sportSession')->create([
            'amount_paid' => 2000,
            'stripe_payment_intent_id' => 'pi_collision',
            'stripe_checkout_session_id' => null,
        ]);

        $target = Booking::factory()->confirmed()->for($session, 'sportSession')->create([
            'amount_paid' => 2000,
            'stripe_payment_intent_id' => null,
            'stripe_checkout_session_id' => 'cs_collision_test',
        ]);

        $command = makeReconcileCommand(
            fn (string $csId): StripeCheckoutSession => StripeCheckoutSession::constructFrom([
                'id' => $csId,
                'payment_intent' => 'pi_collision',
            ]),
        );

        app()->instance(ReconcileBookings::class, $command);

        $this->artisan('payments:reconcile-bookings', ['--repair' => true])
            ->assertExitCode(0);

        $target->refresh();
        expect($target->stripe_payment_intent_id)->toBeNull();
    });

    it('skips bookings when Stripe returns no payment intent', function () {
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->confirmed()->for($coach, 'coach')->create();
        $booking = Booking::factory()->confirmed()->for($session, 'sportSession')->create([
            'amount_paid' => 3000,
            'stripe_payment_intent_id' => null,
            'stripe_checkout_session_id' => 'cs_no_pi',
        ]);

        $command = makeReconcileCommand(
            fn (string $csId): StripeCheckoutSession => StripeCheckoutSession::constructFrom([
                'id' => $csId,
                'payment_intent' => null,
            ]),
        );

        app()->instance(ReconcileBookings::class, $command);

        $this->artisan('payments:reconcile-bookings', ['--repair' => true])
            ->assertExitCode(0);

        $booking->refresh();
        expect($booking->stripe_payment_intent_id)->toBeNull();
    });
});
