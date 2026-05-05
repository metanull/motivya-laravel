<?php

declare(strict_types=1);

use App\Enums\AuditEventType;
use App\Enums\BookingStatus;
use App\Models\AuditEvent;
use App\Models\Booking;
use App\Models\SportSession;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('ExpireUnpaidBookings command audit', function () {
    it('records a booking.expired audit event for each expired booking', function () {
        $session = SportSession::factory()->published()->create(['current_participants' => 2]);

        Booking::factory()->count(2)->for($session, 'sportSession')->create([
            'status' => BookingStatus::PendingPayment,
            'payment_expires_at' => now()->subMinutes(5),
        ]);

        $this->artisan('bookings:expire-unpaid')->assertSuccessful();

        expect(
            AuditEvent::where('event_type', AuditEventType::BookingExpired->value)->count()
        )->toBe(2);
    });

    it('records the old and new status in the booking.expired audit', function () {
        $session = SportSession::factory()->published()->create(['current_participants' => 1]);

        Booking::factory()->for($session, 'sportSession')->create([
            'status' => BookingStatus::PendingPayment,
            'payment_expires_at' => now()->subMinutes(5),
        ]);

        $this->artisan('bookings:expire-unpaid')->assertSuccessful();

        $audit = AuditEvent::where('event_type', AuditEventType::BookingExpired->value)->firstOrFail();

        expect($audit->old_values['status'])->toBe(BookingStatus::PendingPayment->value)
            ->and($audit->new_values['status'])->toBe(BookingStatus::Cancelled->value);
    });

    it('does not record audit events for bookings that are not yet expired', function () {
        $session = SportSession::factory()->published()->create(['current_participants' => 1]);

        Booking::factory()->for($session, 'sportSession')->create([
            'status' => BookingStatus::PendingPayment,
            'payment_expires_at' => now()->addMinutes(30),
        ]);

        $this->artisan('bookings:expire-unpaid')->assertSuccessful();

        expect(
            AuditEvent::where('event_type', AuditEventType::BookingExpired->value)->exists()
        )->toBeFalse();
    });

    it('does not record audit events for bookings already in a final status', function () {
        $session = SportSession::factory()->published()->create(['current_participants' => 1]);

        Booking::factory()->for($session, 'sportSession')->create([
            'status' => BookingStatus::Confirmed,
            'payment_expires_at' => now()->subMinutes(5),
        ]);

        $this->artisan('bookings:expire-unpaid')->assertSuccessful();

        expect(
            AuditEvent::where('event_type', AuditEventType::BookingExpired->value)->exists()
        )->toBeFalse();
    });

    it('audit actor_type is scheduler', function () {
        $session = SportSession::factory()->published()->create(['current_participants' => 1]);

        Booking::factory()->for($session, 'sportSession')->create([
            'status' => BookingStatus::PendingPayment,
            'payment_expires_at' => now()->subMinutes(5),
        ]);

        $this->artisan('bookings:expire-unpaid')->assertSuccessful();

        $audit = AuditEvent::where('event_type', AuditEventType::BookingExpired->value)->firstOrFail();

        expect($audit->actor_type->value)->toBe('scheduler');
    });
});
