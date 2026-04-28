<?php

declare(strict_types=1);

use App\Enums\BookingStatus;
use App\Events\BookingCancelled;
use App\Models\Booking;
use App\Models\SportSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

describe('bookings:expire-unpaid', function () {
    afterEach(function (): void {
        Carbon::setTestNow();
    });

    it('cancels expired pending-payment bookings and releases capacity', function (): void {
        Carbon::setTestNow('2026-05-10 12:00:00');
        Event::fake([BookingCancelled::class]);

        $session = SportSession::factory()->published()->create([
            'current_participants' => 1,
        ]);
        $booking = Booking::factory()->for($session, 'sportSession')->pendingPayment()->create([
            'payment_expires_at' => now()->subMinutes(5),
        ]);

        $this->artisan('bookings:expire-unpaid')->assertSuccessful();

        expect($booking->fresh()->status)->toBe(BookingStatus::Cancelled);
        expect($booking->fresh()->cancelled_at)->not->toBeNull();
        expect($session->fresh()->current_participants)->toBe(0);

        Event::assertDispatched(
            BookingCancelled::class,
            fn (BookingCancelled $event): bool => $event->bookingId === $booking->id
                && $event->reason === 'payment_expired'
                && $event->refundEligible === false
        );
    });

    it('does not cancel pending-payment bookings whose expiry has not passed', function (): void {
        Carbon::setTestNow('2026-05-10 12:00:00');
        Event::fake([BookingCancelled::class]);

        $session = SportSession::factory()->published()->create([
            'current_participants' => 1,
        ]);
        $booking = Booking::factory()->for($session, 'sportSession')->pendingPayment()->create([
            'payment_expires_at' => now()->addMinutes(10),
        ]);

        $this->artisan('bookings:expire-unpaid')->assertSuccessful();

        expect($booking->fresh()->status)->toBe(BookingStatus::PendingPayment);
        expect($session->fresh()->current_participants)->toBe(1);

        Event::assertNotDispatched(BookingCancelled::class);
    });

    it('does not cancel confirmed bookings', function (): void {
        Carbon::setTestNow('2026-05-10 12:00:00');
        Event::fake([BookingCancelled::class]);

        $session = SportSession::factory()->published()->create([
            'current_participants' => 1,
        ]);
        $booking = Booking::factory()->for($session, 'sportSession')->confirmed()->create([
            'payment_expires_at' => now()->subMinutes(5),
        ]);

        $this->artisan('bookings:expire-unpaid')->assertSuccessful();

        expect($booking->fresh()->status)->toBe(BookingStatus::Confirmed);
        expect($session->fresh()->current_participants)->toBe(1);

        Event::assertNotDispatched(BookingCancelled::class);
    });

    it('does not cancel bookings with no expiry set', function (): void {
        Carbon::setTestNow('2026-05-10 12:00:00');
        Event::fake([BookingCancelled::class]);

        $session = SportSession::factory()->published()->create([
            'current_participants' => 1,
        ]);
        $booking = Booking::factory()->for($session, 'sportSession')->pendingPayment()->create([
            'payment_expires_at' => null,
        ]);

        $this->artisan('bookings:expire-unpaid')->assertSuccessful();

        expect($booking->fresh()->status)->toBe(BookingStatus::PendingPayment);
        expect($session->fresh()->current_participants)->toBe(1);

        Event::assertNotDispatched(BookingCancelled::class);
    });

    it('is idempotent when run multiple times', function (): void {
        Carbon::setTestNow('2026-05-10 12:00:00');
        Event::fake([BookingCancelled::class]);

        $session = SportSession::factory()->published()->create([
            'current_participants' => 1,
        ]);
        $booking = Booking::factory()->for($session, 'sportSession')->pendingPayment()->create([
            'payment_expires_at' => now()->subMinutes(5),
        ]);

        $this->artisan('bookings:expire-unpaid')->assertSuccessful();
        $this->artisan('bookings:expire-unpaid')->assertSuccessful();

        expect($booking->fresh()->status)->toBe(BookingStatus::Cancelled);
        expect($session->fresh()->current_participants)->toBe(0);

        Event::assertDispatchedTimes(BookingCancelled::class, 1);
    });
});
