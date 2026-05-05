<?php

declare(strict_types=1);

use App\Enums\AuditEventType;
use App\Enums\BookingStatus;
use App\Models\AuditEvent;
use App\Models\Booking;
use App\Models\SportSession;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('BookingService audit', function () {
    it('records a booking.created event when booking a session', function () {
        $session = SportSession::factory()->published()->create(['max_participants' => 10, 'current_participants' => 0]);
        $athlete = User::factory()->athlete()->create();
        $service = app(BookingService::class);

        $service->book($session, $athlete);

        expect(
            AuditEvent::where('event_type', AuditEventType::BookingCreated->value)->exists()
        )->toBeTrue();
    });

    it('records correct new status in booking.created audit', function () {
        $session = SportSession::factory()->published()->create(['max_participants' => 10, 'current_participants' => 0]);
        $athlete = User::factory()->athlete()->create();
        $service = app(BookingService::class);

        $service->book($session, $athlete);

        $audit = AuditEvent::where('event_type', AuditEventType::BookingCreated->value)->firstOrFail();

        expect($audit->new_values['status'])->toBe(BookingStatus::PendingPayment->value);
    });

    it('records a booking.cancelled event when cancelling a booking', function () {
        $session = SportSession::factory()->published()->create(['current_participants' => 1]);
        $athlete = User::factory()->athlete()->create();
        $booking = Booking::factory()
            ->confirmed()
            ->for($session, 'sportSession')
            ->for($athlete, 'athlete')
            ->create(['amount_paid' => 0]);
        $service = app(BookingService::class);

        $service->cancel($booking, $athlete);

        expect(
            AuditEvent::where('event_type', AuditEventType::BookingCancelled->value)->exists()
        )->toBeTrue();
    });
});
