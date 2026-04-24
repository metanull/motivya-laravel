<?php

declare(strict_types=1);

use App\Enums\BookingStatus;
use App\Enums\SessionStatus;
use App\Events\SessionConfirmed;
use App\Exceptions\AlreadyBookedException;
use App\Exceptions\SessionFullException;
use App\Exceptions\SessionNotBookableException;
use App\Models\Booking;
use App\Models\SportSession;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

describe('book', function () {
    it('creates a pending payment booking and increments participants', function () {
        Event::fake([SessionConfirmed::class]);

        $session = SportSession::factory()->published()->create([
            'min_participants' => 2,
            'max_participants' => 5,
            'current_participants' => 0,
        ]);
        $athlete = User::factory()->athlete()->create();

        $booking = app(BookingService::class)->book($session, $athlete);

        expect($booking)
            ->toBeInstanceOf(Booking::class)
            ->and($booking->status)->toBe(BookingStatus::PendingPayment)
            ->and($booking->athlete_id)->toBe($athlete->id)
            ->and($booking->sport_session_id)->toBe($session->id);

        expect($session->fresh()->current_participants)->toBe(1)
            ->and($session->fresh()->status)->toBe(SessionStatus::Published);

        Event::assertNotDispatched(SessionConfirmed::class);
    });

    it('confirms a published session and dispatches the event when the threshold is reached', function () {
        Event::fake([SessionConfirmed::class]);

        $session = SportSession::factory()->published()->create([
            'min_participants' => 2,
            'max_participants' => 5,
            'current_participants' => 1,
        ]);
        $existingAthlete = User::factory()->athlete()->create();
        Booking::factory()->create([
            'sport_session_id' => $session->id,
            'athlete_id' => $existingAthlete->id,
        ]);

        $athlete = User::factory()->athlete()->create();

        $booking = app(BookingService::class)->book($session, $athlete);

        expect($booking->status)->toBe(BookingStatus::PendingPayment);
        expect($session->fresh()->current_participants)->toBe(2)
            ->and($session->fresh()->status)->toBe(SessionStatus::Confirmed);

        Event::assertDispatched(
            SessionConfirmed::class,
            fn (SessionConfirmed $event): bool => $event->sessionId === $session->id
        );
    });

    it('throws SessionFullException when the session is full', function () {
        $session = SportSession::factory()->published()->create([
            'min_participants' => 1,
            'max_participants' => 1,
            'current_participants' => 1,
        ]);
        $athlete = User::factory()->athlete()->create();

        expect(fn () => app(BookingService::class)->book($session, $athlete))
            ->toThrow(SessionFullException::class);
    });

    it('throws SessionNotBookableException for non-bookable session states', function () {
        $session = SportSession::factory()->draft()->create();
        $athlete = User::factory()->athlete()->create();

        expect(fn () => app(BookingService::class)->book($session, $athlete))
            ->toThrow(SessionNotBookableException::class);
    });

    it('throws AlreadyBookedException when the athlete already has a booking', function () {
        $session = SportSession::factory()->published()->create([
            'current_participants' => 1,
        ]);
        $athlete = User::factory()->athlete()->create();
        Booking::factory()->create([
            'sport_session_id' => $session->id,
            'athlete_id' => $athlete->id,
        ]);

        expect(fn () => app(BookingService::class)->book($session, $athlete))
            ->toThrow(AlreadyBookedException::class);
    });

    it('prevents overbooking when two stale session reads compete for the last slot', function () {
        $session = SportSession::factory()->published()->create([
            'min_participants' => 1,
            'max_participants' => 1,
            'current_participants' => 0,
        ]);
        $firstAthlete = User::factory()->athlete()->create();
        $secondAthlete = User::factory()->athlete()->create();

        $staleReadA = SportSession::query()->findOrFail($session->id);
        $staleReadB = SportSession::query()->findOrFail($session->id);

        $service = app(BookingService::class);

        $service->book($staleReadA, $firstAthlete);

        expect(fn () => $service->book($staleReadB, $secondAthlete))
            ->toThrow(SessionFullException::class);

        expect($session->fresh()->current_participants)->toBe(1)
            ->and(Booking::query()->where('sport_session_id', $session->id)->count())->toBe(1);
    });
});
