<?php

declare(strict_types=1);

use App\Models\Booking;
use App\Models\SportSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('create', function () {

    it('allows an athlete to book a session', function () {
        $athlete = User::factory()->athlete()->create();
        $session = SportSession::factory()->create();

        expect($athlete->can('create', [Booking::class, $session]))->toBeTrue();
    });

    it('denies an athlete from booking their own session', function () {
        $coachAthlete = User::factory()->athlete()->create();
        $session = SportSession::factory()->create(['coach_id' => $coachAthlete->id]);

        expect($coachAthlete->can('create', [Booking::class, $session]))->toBeFalse();
    });

    it('denies a coach from creating bookings', function () {
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->create();

        expect($coach->can('create', [Booking::class, $session]))->toBeFalse();
    });

    it('denies an accountant from creating bookings', function () {
        $accountant = User::factory()->accountant()->create();
        $session = SportSession::factory()->create();

        expect($accountant->can('create', [Booking::class, $session]))->toBeFalse();
    });

    it('allows admin to create bookings (before bypass)', function () {
        $admin = User::factory()->admin()->create();
        $session = SportSession::factory()->create();

        expect($admin->can('create', [Booking::class, $session]))->toBeTrue();
    });

});

describe('view', function () {

    it('allows the athlete to view their own booking', function () {
        $athlete = User::factory()->athlete()->create();
        $booking = Booking::factory()->create(['athlete_id' => $athlete->id]);

        expect($athlete->can('view', $booking))->toBeTrue();
    });

    it('denies a different athlete from viewing the booking', function () {
        $otherAthlete = User::factory()->athlete()->create();
        $booking = Booking::factory()->create();

        expect($otherAthlete->can('view', $booking))->toBeFalse();
    });

    it('allows the session coach to view the booking', function () {
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->create(['coach_id' => $coach->id]);
        $booking = Booking::factory()->create(['sport_session_id' => $session->id]);

        expect($coach->can('view', $booking))->toBeTrue();
    });

    it('denies a different coach from viewing the booking', function () {
        $otherCoach = User::factory()->coach()->create();
        $booking = Booking::factory()->create();

        expect($otherCoach->can('view', $booking))->toBeFalse();
    });

    it('denies an accountant from viewing bookings', function () {
        $accountant = User::factory()->accountant()->create();
        $booking = Booking::factory()->create();

        expect($accountant->can('view', $booking))->toBeFalse();
    });

    it('allows admin to view any booking (before bypass)', function () {
        $admin = User::factory()->admin()->create();
        $booking = Booking::factory()->create();

        expect($admin->can('view', $booking))->toBeTrue();
    });

});

describe('cancel', function () {

    it('allows the athlete to cancel their own booking', function () {
        $athlete = User::factory()->athlete()->create();
        $booking = Booking::factory()->create(['athlete_id' => $athlete->id]);

        expect($athlete->can('cancel', $booking))->toBeTrue();
    });

    it('denies a different athlete from cancelling the booking', function () {
        $otherAthlete = User::factory()->athlete()->create();
        $booking = Booking::factory()->create();

        expect($otherAthlete->can('cancel', $booking))->toBeFalse();
    });

    it('denies a coach from cancelling a booking', function () {
        $coach = User::factory()->coach()->create();
        $booking = Booking::factory()->create();

        expect($coach->can('cancel', $booking))->toBeFalse();
    });

    it('allows admin to cancel any booking (before bypass)', function () {
        $admin = User::factory()->admin()->create();
        $booking = Booking::factory()->create();

        expect($admin->can('cancel', $booking))->toBeTrue();
    });

});

describe('refund', function () {

    it('denies an athlete from refunding', function () {
        $athlete = User::factory()->athlete()->create();
        $booking = Booking::factory()->create(['athlete_id' => $athlete->id]);

        expect($athlete->can('refund', $booking))->toBeFalse();
    });

    it('denies a coach from refunding', function () {
        $coach = User::factory()->coach()->create();
        $booking = Booking::factory()->create();

        expect($coach->can('refund', $booking))->toBeFalse();
    });

    it('denies an accountant from refunding', function () {
        $accountant = User::factory()->accountant()->create();
        $booking = Booking::factory()->create();

        expect($accountant->can('refund', $booking))->toBeFalse();
    });

    it('allows admin to refund (before bypass)', function () {
        $admin = User::factory()->admin()->create();
        $booking = Booking::factory()->create();

        expect($admin->can('refund', $booking))->toBeTrue();
    });

});
