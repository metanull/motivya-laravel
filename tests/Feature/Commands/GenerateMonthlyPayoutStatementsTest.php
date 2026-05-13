<?php

declare(strict_types=1);

use App\Enums\BookingStatus;
use App\Enums\CoachPayoutStatementStatus;
use App\Enums\SessionStatus;
use App\Models\Booking;
use App\Models\CoachPayoutStatement;
use App\Models\CoachProfile;
use App\Models\SchedulerHeartbeat;
use App\Models\SportSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

/**
 * Create an approved coach with a completed session and confirmed bookings in the given period.
 */
function makeCoachWithCompletedSession(int $year, int $month, int $bookingCount = 2): User
{
    $coach = User::factory()->coach()->create();
    CoachProfile::factory()->approved()->create([
        'user_id' => $coach->id,
        'is_vat_subject' => false,
    ]);

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
            'amount_paid' => 3000,
            'stripe_payment_intent_id' => 'pi_payout_test_'.$i,
        ]);
    }

    return $coach;
}

describe('payout-statements:generate-monthly', function () {

    it('defaults to the previous calendar month when no year or month is provided', function () {
        Carbon::setTestNow('2026-06-01 03:00:00');

        $coach = makeCoachWithCompletedSession(2026, 5);

        $this->artisan('payout-statements:generate-monthly')
            ->assertExitCode(0);

        $statement = CoachPayoutStatement::where('coach_id', $coach->id)
            ->where('period_year', 2026)
            ->where('period_month', 5)
            ->first();

        expect($statement)->not->toBeNull();
        expect($statement->status)->toBe(CoachPayoutStatementStatus::Draft);

        Carbon::setTestNow();
    });

    it('uses explicit --year and --month when provided', function () {
        $coach = makeCoachWithCompletedSession(2026, 3);

        $this->artisan('payout-statements:generate-monthly', ['--year' => 2026, '--month' => 3])
            ->assertExitCode(0);

        $statement = CoachPayoutStatement::where('coach_id', $coach->id)
            ->where('period_year', 2026)
            ->where('period_month', 3)
            ->first();

        expect($statement)->not->toBeNull();
    });

    it('limits generation to the specified coach when --coach is given', function () {
        $coachA = makeCoachWithCompletedSession(2026, 4);
        $coachB = makeCoachWithCompletedSession(2026, 4);

        $this->artisan('payout-statements:generate-monthly', [
            '--year' => 2026,
            '--month' => 4,
            '--coach' => $coachA->id,
        ])->assertExitCode(0);

        expect(CoachPayoutStatement::where('coach_id', $coachA->id)->exists())->toBeTrue();
        expect(CoachPayoutStatement::where('coach_id', $coachB->id)->exists())->toBeFalse();
    });

    it('refreshes an existing draft statement without creating a duplicate', function () {
        $coach = makeCoachWithCompletedSession(2026, 2);

        $this->artisan('payout-statements:generate-monthly', ['--year' => 2026, '--month' => 2])
            ->assertExitCode(0);

        // Run again — must refresh, not duplicate.
        $this->artisan('payout-statements:generate-monthly', ['--year' => 2026, '--month' => 2])
            ->assertExitCode(0);

        expect(
            CoachPayoutStatement::where('coach_id', $coach->id)
                ->where('period_year', 2026)
                ->where('period_month', 2)
                ->count()
        )->toBe(1);
    });

    it('does not overwrite a non-draft statement', function () {
        $coach = makeCoachWithCompletedSession(2026, 1);

        // Create a submitted statement manually.
        $existing = CoachPayoutStatement::factory()->forPeriod(2026, 1)->create([
            'coach_id' => $coach->id,
            'status' => CoachPayoutStatementStatus::InvoiceSubmitted,
        ]);

        $this->artisan('payout-statements:generate-monthly', ['--year' => 2026, '--month' => 1])
            ->assertExitCode(0);

        $existing->refresh();
        expect($existing->status)->toBe(CoachPayoutStatementStatus::InvoiceSubmitted);
    });

    it('records a SchedulerHeartbeat after running', function () {
        $this->artisan('payout-statements:generate-monthly', ['--year' => 2025, '--month' => 12])
            ->assertExitCode(0);

        expect(
            SchedulerHeartbeat::where('command', 'payout-statements:generate-monthly')->exists(),
        )->toBeTrue();
    });

    it('fails and returns an error when the year is invalid', function () {
        $this->artisan('payout-statements:generate-monthly', ['--year' => 1900, '--month' => 1])
            ->assertExitCode(1);
    });

    it('fails and returns an error when the month is invalid', function () {
        $this->artisan('payout-statements:generate-monthly', ['--year' => 2026, '--month' => 13])
            ->assertExitCode(1);
    });
});
