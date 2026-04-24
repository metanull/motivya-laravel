<?php

declare(strict_types=1);

use App\Enums\BookingStatus;
use App\Enums\SessionStatus;
use App\Events\BookingCancelled;
use App\Models\Booking;
use App\Models\SportSession;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

describe('cancel', function () {
    it('dispatches refund eligibility for the cancellation window boundaries', function (SessionStatus $sessionStatus, int $hoursUntilStart, int $minutesUntilStart, bool $refundEligible) {
        Event::fake([BookingCancelled::class]);

        Carbon::setTestNow(Carbon::create(2026, 4, 24, 12, 0, 0));

        $athlete = User::factory()->athlete()->create();
        $startDateTime = now()->addHours($hoursUntilStart)->addMinutes($minutesUntilStart);
        $session = SportSession::factory()
            ->state([
                'status' => $sessionStatus->value,
                'date' => $startDateTime->toDateString(),
                'start_time' => $startDateTime->format('H:i:s'),
                'min_participants' => 2,
                'current_participants' => 2,
            ])
            ->create();

        $booking = Booking::factory()
            ->state([
                'status' => $sessionStatus === SessionStatus::Published
                    ? BookingStatus::PendingPayment->value
                    : BookingStatus::Confirmed->value,
            ])
            ->for($session, 'sportSession')
            ->for($athlete, 'athlete')
            ->create();

        app(BookingService::class)->cancel($booking, $athlete);

        expect($booking->fresh()->status)->toBe(BookingStatus::Cancelled)
            ->and($booking->fresh()->cancelled_at)->not->toBeNull();

        Event::assertDispatched(
            BookingCancelled::class,
            fn (BookingCancelled $event): bool => $event->bookingId === $booking->id
                && $event->reason === 'athlete_cancelled'
                && $event->refundEligible === $refundEligible
        );

        Carbon::setTestNow();
    })->with([
        'confirmed session at 48 hours' => [SessionStatus::Confirmed, 48, 0, true],
        'confirmed session below 48 hours' => [SessionStatus::Confirmed, 47, 59, false],
        'published session at 24 hours' => [SessionStatus::Published, 24, 0, true],
        'published session below 24 hours' => [SessionStatus::Published, 23, 59, false],
    ]);

    it('decrements participants and keeps a confirmed session confirmed after cancellation', function () {
        Event::fake([BookingCancelled::class]);

        Carbon::setTestNow(Carbon::create(2026, 4, 24, 12, 0, 0));

        $athlete = User::factory()->athlete()->create();
        $session = SportSession::factory()->confirmed()->create([
            'date' => now()->addDays(3)->toDateString(),
            'start_time' => '12:00:00',
            'min_participants' => 2,
            'current_participants' => 2,
        ]);

        $booking = Booking::factory()
            ->confirmed()
            ->for($session, 'sportSession')
            ->for($athlete, 'athlete')
            ->create();

        app(BookingService::class)->cancel($booking, $athlete);

        expect($session->fresh()->current_participants)->toBe(1)
            ->and($session->fresh()->status)->toBe(SessionStatus::Confirmed);

        Carbon::setTestNow();
    });
});
