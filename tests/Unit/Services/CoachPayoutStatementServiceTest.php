<?php

declare(strict_types=1);

use App\Enums\BookingStatus;
use App\Enums\CoachPayoutStatementStatus;
use App\Enums\SessionStatus;
use App\Models\Booking;
use App\Models\CoachPayoutStatement;
use App\Models\CoachProfile;
use App\Models\SportSession;
use App\Models\User;
use App\Services\CoachPayoutStatementService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Helper to create a coach with a profile
function makeCoach(bool $isVatSubject = false): User
{
    $coach = User::factory()->coach()->create();
    CoachProfile::factory()->create([
        'user_id' => $coach->id,
        'is_vat_subject' => $isVatSubject,
    ]);

    return $coach;
}

// Helper to create a completed session with confirmed bookings
function makeCompletedSessionWithBookings(User $coach, int $year, int $month, int $bookingCount = 2, int $amountPerBooking = 5000): SportSession
{
    $session = SportSession::factory()->create([
        'coach_id' => $coach->id,
        'status' => SessionStatus::Completed,
        'date' => sprintf('%04d-%02d-15', $year, $month),
    ]);

    for ($i = 0; $i < $bookingCount; $i++) {
        $athlete = User::factory()->athlete()->create();
        Booking::factory()->create([
            'sport_session_id' => $session->id,
            'athlete_id' => $athlete->id,
            'status' => BookingStatus::Confirmed,
            'amount_paid' => $amountPerBooking,
            'stripe_payment_intent_id' => 'pi_test'.$i,
        ]);
    }

    return $session;
}

describe('generateForCoach', function () {
    it('creates a draft statement with correct totals', function () {
        $coach = makeCoach();
        makeCompletedSessionWithBookings($coach, 2025, 6, bookingCount: 2, amountPerBooking: 5000);

        $service = app(CoachPayoutStatementService::class);
        $statement = $service->generateForCoach($coach, 2025, 6);

        expect($statement)->toBeInstanceOf(CoachPayoutStatement::class)
            ->and($statement->status)->toBe(CoachPayoutStatementStatus::Draft)
            ->and($statement->coach_id)->toBe($coach->id)
            ->and($statement->period_year)->toBe(2025)
            ->and($statement->period_month)->toBe(6)
            ->and($statement->sessions_count)->toBe(1)
            ->and($statement->paid_bookings_count)->toBe(2)
            ->and($statement->revenue_ttc)->toBe(10000);
    });

    it('does not overwrite an already-approved statement', function () {
        $coach = makeCoach();
        makeCompletedSessionWithBookings($coach, 2025, 6, bookingCount: 1, amountPerBooking: 3000);

        $service = app(CoachPayoutStatementService::class);

        // Generate once and manually advance
        $statement = $service->generateForCoach($coach, 2025, 6);
        $statement->update(['status' => CoachPayoutStatementStatus::Approved->value]);

        // Re-generate — should return existing without modification
        $returned = $service->generateForCoach($coach, 2025, 6);

        expect($returned->id)->toBe($statement->id)
            ->and($returned->status)->toBe(CoachPayoutStatementStatus::Approved);
    });

    it('does not overwrite a paid statement', function () {
        $coach = makeCoach();
        $service = app(CoachPayoutStatementService::class);

        $statement = $service->generateForCoach($coach, 2025, 6);
        $statement->update(['status' => CoachPayoutStatementStatus::Paid->value]);

        $returned = $service->generateForCoach($coach, 2025, 6);
        expect($returned->id)->toBe($statement->id)
            ->and($returned->status)->toBe(CoachPayoutStatementStatus::Paid);
    });

    it('updates an existing draft statement on re-generation', function () {
        $coach = makeCoach();
        $service = app(CoachPayoutStatementService::class);

        $first = $service->generateForCoach($coach, 2025, 7);
        expect($first->paid_bookings_count)->toBe(0);

        // Add sessions after initial generation
        makeCompletedSessionWithBookings($coach, 2025, 7, bookingCount: 3, amountPerBooking: 1000);

        $second = $service->generateForCoach($coach, 2025, 7);
        expect($second->id)->toBe($first->id)
            ->and($second->paid_bookings_count)->toBe(3);
    });
});

describe('requestPayout', function () {
    it('transitions draft to ready_for_invoice', function () {
        $coach = makeCoach();
        $service = app(CoachPayoutStatementService::class);

        $statement = $service->generateForCoach($coach, 2025, 6);
        $service->requestPayout($statement);

        expect($statement->fresh()->status)->toBe(CoachPayoutStatementStatus::ReadyForInvoice);
    });

    it('throws InvalidArgumentException when statement is not draft', function () {
        $coach = makeCoach();
        $service = app(CoachPayoutStatementService::class);

        $statement = $service->generateForCoach($coach, 2025, 6);
        $statement->update(['status' => CoachPayoutStatementStatus::ReadyForInvoice->value]);

        expect(fn () => $service->requestPayout($statement->fresh()))
            ->toThrow(InvalidArgumentException::class);
    });
});

describe('markInvoiceSubmitted', function () {
    it('transitions ready_for_invoice to invoice_submitted', function () {
        $coach = makeCoach();
        $service = app(CoachPayoutStatementService::class);

        $statement = $service->generateForCoach($coach, 2025, 6);
        $statement->update(['status' => CoachPayoutStatementStatus::ReadyForInvoice->value]);

        $service->markInvoiceSubmitted($statement->fresh());

        expect($statement->fresh()->status)->toBe(CoachPayoutStatementStatus::InvoiceSubmitted);
    });

    it('throws InvalidArgumentException when statement is not ready_for_invoice', function () {
        $coach = makeCoach();
        $service = app(CoachPayoutStatementService::class);

        $statement = $service->generateForCoach($coach, 2025, 6);
        // still Draft

        expect(fn () => $service->markInvoiceSubmitted($statement))
            ->toThrow(InvalidArgumentException::class);
    });
});

describe('approve', function () {
    it('transitions invoice_submitted to approved', function () {
        $accountant = User::factory()->accountant()->create();
        $coach = makeCoach();
        $service = app(CoachPayoutStatementService::class);

        $statement = CoachPayoutStatement::factory()->invoiceSubmitted()->create(['coach_id' => $coach->id]);

        $service->approve($statement, $accountant);

        expect($statement->fresh()->status)->toBe(CoachPayoutStatementStatus::Approved)
            ->and($statement->fresh()->approved_by)->toBe($accountant->id)
            ->and($statement->fresh()->approved_at)->not->toBeNull();
    });

    it('throws InvalidArgumentException when statement is not invoice_submitted', function () {
        $accountant = User::factory()->accountant()->create();
        $coach = makeCoach();
        $service = app(CoachPayoutStatementService::class);

        $statement = CoachPayoutStatement::factory()->draft()->create(['coach_id' => $coach->id]);

        expect(fn () => $service->approve($statement, $accountant))
            ->toThrow(InvalidArgumentException::class);
    });
});

describe('block', function () {
    it('blocks a statement from any non-terminal status', function () {
        $accountant = User::factory()->accountant()->create();
        $coach = makeCoach();
        $service = app(CoachPayoutStatementService::class);

        foreach ([
            [CoachPayoutStatement::factory()->draft(), 2025, 1],
            [CoachPayoutStatement::factory()->readyForInvoice(), 2025, 2],
            [CoachPayoutStatement::factory()->invoiceSubmitted(), 2025, 3],
        ] as [$factory, $year, $month]) {
            $statement = $factory->forPeriod($year, $month)->create(['coach_id' => $coach->id]);
            $service->block($statement, $accountant, 'Suspicious activity');
            expect($statement->fresh()->status)->toBe(CoachPayoutStatementStatus::Blocked);
        }
    });

    it('throws InvalidArgumentException when block reason is empty', function () {
        $accountant = User::factory()->accountant()->create();
        $coach = makeCoach();
        $service = app(CoachPayoutStatementService::class);

        $statement = CoachPayoutStatement::factory()->draft()->create(['coach_id' => $coach->id]);

        expect(fn () => $service->block($statement, $accountant, ''))
            ->toThrow(InvalidArgumentException::class);
    });
});

describe('markPaid', function () {
    it('transitions approved to paid', function () {
        $accountant = User::factory()->accountant()->create();
        $coach = makeCoach();
        $service = app(CoachPayoutStatementService::class);

        $statement = CoachPayoutStatement::factory()->approved()->create(['coach_id' => $coach->id]);

        $service->markPaid($statement, $accountant);

        expect($statement->fresh()->status)->toBe(CoachPayoutStatementStatus::Paid)
            ->and($statement->fresh()->paid_at)->not->toBeNull();
    });

    it('throws InvalidArgumentException when statement is not approved', function () {
        $accountant = User::factory()->accountant()->create();
        $coach = makeCoach();
        $service = app(CoachPayoutStatementService::class);

        $statement = CoachPayoutStatement::factory()->draft()->create(['coach_id' => $coach->id]);

        expect(fn () => $service->markPaid($statement, $accountant))
            ->toThrow(InvalidArgumentException::class);
    });
});
