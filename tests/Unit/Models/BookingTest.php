<?php

declare(strict_types=1);

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\SportSession;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

describe('Booking model', function () {

    it('creates a booking with factory defaults', function () {
        $booking = Booking::factory()->create();

        expect($booking)->toBeInstanceOf(Booking::class);
        expect($booking->status)->toBe(BookingStatus::PendingPayment);
        expect($booking->amount_paid)->toBeInt();
    });

    it('casts status to BookingStatus enum', function () {
        $booking = Booking::factory()->create(['status' => 'confirmed']);

        expect($booking->status)->toBe(BookingStatus::Confirmed);
    });

    it('casts amount_paid to integer', function () {
        $booking = Booking::factory()->create(['amount_paid' => 1250]);

        expect($booking->amount_paid)->toBe(1250)->toBeInt();
    });

    it('casts cancelled_at to datetime', function () {
        $booking = Booking::factory()->cancelled()->create();

        expect($booking->cancelled_at)->toBeInstanceOf(Carbon::class);
    });

    it('casts refunded_at to datetime', function () {
        $booking = Booking::factory()->refunded()->create();

        expect($booking->refunded_at)->toBeInstanceOf(Carbon::class);
    });

    it('belongs to a sport session', function () {
        $session = SportSession::factory()->create();
        $booking = Booking::factory()->create(['sport_session_id' => $session->id]);

        expect($booking->sportSession)->toBeInstanceOf(SportSession::class);
        expect($booking->sportSession->id)->toBe($session->id);
    });

    it('belongs to an athlete (user)', function () {
        $athlete = User::factory()->athlete()->create();
        $booking = Booking::factory()->create(['athlete_id' => $athlete->id]);

        expect($booking->athlete)->toBeInstanceOf(User::class);
        expect($booking->athlete->id)->toBe($athlete->id);
    });

    it('has a unique constraint on sport_session_id and athlete_id', function () {
        $session = SportSession::factory()->create();
        $athlete = User::factory()->athlete()->create();

        Booking::factory()->create([
            'sport_session_id' => $session->id,
            'athlete_id' => $athlete->id,
        ]);

        expect(fn () => Booking::factory()->create([
            'sport_session_id' => $session->id,
            'athlete_id' => $athlete->id,
        ]))->toThrow(UniqueConstraintViolationException::class);
    });

});

describe('SportSession bookings relationship', function () {

    it('has many bookings', function () {
        $session = SportSession::factory()->create();
        Booking::factory()->count(3)->create(['sport_session_id' => $session->id]);

        expect($session->bookings)->toHaveCount(3);
        expect($session->bookings->first())->toBeInstanceOf(Booking::class);
    });

});

describe('User bookings relationship', function () {

    it('has many bookings as athlete', function () {
        $athlete = User::factory()->athlete()->create();
        Booking::factory()->count(2)->create(['athlete_id' => $athlete->id]);

        expect($athlete->bookings)->toHaveCount(2);
        expect($athlete->bookings->first())->toBeInstanceOf(Booking::class);
    });

});
