<?php

declare(strict_types=1);

use App\Enums\AuditEventType;
use App\Exceptions\SessionFullException;
use App\Models\AuditEvent;
use App\Models\Booking;
use App\Models\CoachProfile;
use App\Models\SportSession;
use App\Models\User;
use App\Services\AdminService;
use App\Services\Audit\AuditService;
use App\Services\BookingService;
use App\Services\RefundService;
use App\Services\SessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\Refund as StripeRefund;

uses(RefreshDatabase::class);

describe('Audit — Transaction Regression', function () {

    // ── Booking creation rolls back audit rows when the session is full ───

    it('booking audit event is not created when booking creation fails (session full)', function () {
        $session = SportSession::factory()->published()->create([
            'max_participants' => 1,
            'current_participants' => 1, // already full
        ]);
        $athlete = User::factory()->athlete()->create();

        expect(fn () => app(BookingService::class)->book($session, $athlete))
            ->toThrow(SessionFullException::class);

        expect(
            AuditEvent::where('event_type', AuditEventType::BookingCreated->value)->exists()
        )->toBeFalse();
    });

    it('booking and its audit event share the same transaction', function () {
        $session = SportSession::factory()->published()->create([
            'max_participants' => 5,
            'current_participants' => 0,
        ]);
        $athlete = User::factory()->athlete()->create();

        $auditBefore = AuditEvent::count();
        $bookingBefore = Booking::count();

        app(BookingService::class)->book($session, $athlete);

        expect(AuditEvent::count())->toBe($auditBefore + 1);
        expect(Booking::count())->toBe($bookingBefore + 1);
    });

    // ── Session cancellation is atomic with its audit row ─────────────────

    it('session.cancelled audit event is created when cancellation succeeds', function () {
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->published()->for($coach, 'coach')->create();

        app(SessionService::class)->cancel($session, 'Bad weather');

        expect(
            AuditEvent::where('event_type', AuditEventType::SessionCancelled->value)
                ->where('model_id', $session->id)
                ->exists()
        )->toBeTrue();
    });

    it('session and its audit event are consistent after cancellation', function () {
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->published()->for($coach, 'coach')->create();

        $auditBefore = AuditEvent::count();

        app(SessionService::class)->cancel($session, 'Test reason');

        $session->refresh();
        expect($session->status->value)->toBe('cancelled');
        expect(AuditEvent::count())->toBe($auditBefore + 1);
    });

    // ── Coach approval audit and profile update are atomic ────────────────

    it('coach.approved audit event is created when a coach is approved', function () {
        $coach = User::factory()->coach()->create();
        $profile = CoachProfile::factory()->pending()->for($coach)->create();

        app(AdminService::class)->approveCoach($profile);

        expect(
            AuditEvent::where('event_type', AuditEventType::CoachApproved->value)->exists()
        )->toBeTrue();
    });

    it('coach approval and audit event are consistent after approval', function () {
        $coach = User::factory()->coach()->create();
        $profile = CoachProfile::factory()->pending()->for($coach)->create();

        $auditBefore = AuditEvent::count();

        app(AdminService::class)->approveCoach($profile);

        expect(AuditEvent::count())->toBe($auditBefore + 1);

        $profile->refresh();
        expect($profile->status->value)->toBe('approved');
    });

    // ── Refund state update and audit row are atomic ──────────────────────

    it('refund.requested audit event is created when a refund is requested', function () {
        $athlete = User::factory()->athlete()->create();
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->vatSubject()->for($coach)->create([
            'stripe_account_id' => 'acct_test_regression',
            'stripe_onboarding_complete' => true,
        ]);
        $session = SportSession::factory()->confirmed()->for($coach, 'coach')->create();
        $booking = Booking::factory()
            ->confirmed()
            ->for($session, 'sportSession')
            ->for($athlete, 'athlete')
            ->create([
                'amount_paid' => 2000,
                'stripe_payment_intent_id' => 'pi_test_regression',
            ]);

        // Use the proper StripeRefund mock pattern from the existing RefundService tests
        $refundService = new RefundService(
            auditService: app(AuditService::class),
            createRefundUsing: fn (array $payload): StripeRefund => StripeRefund::constructFrom(['id' => 're_regression_test']),
        );

        $refundService->refund($booking);

        expect(
            AuditEvent::where('event_type', AuditEventType::RefundRequested->value)->exists()
        )->toBeTrue();
    });

});
