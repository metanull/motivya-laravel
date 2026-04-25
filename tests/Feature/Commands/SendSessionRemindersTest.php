<?php

declare(strict_types=1);

use App\Models\Booking;
use App\Models\SportSession;
use App\Notifications\SessionReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

describe('sessions:send-reminders', function () {
    afterEach(function (): void {
        Carbon::setTestNow();
    });

    it('sends reminder to each athlete of confirmed sessions starting in ~24h with reminder_sent_at null', function (): void {
        Carbon::setTestNow('2026-05-01 10:00:00');
        Notification::fake();

        $session = SportSession::factory()->confirmed()->create([
            'date' => '2026-05-02',
            'start_time' => '10:00:00',
        ]);

        $bookingA = Booking::factory()->confirmed()->for($session, 'sportSession')->create();
        $bookingB = Booking::factory()->confirmed()->for($session, 'sportSession')->create();

        $this->artisan('sessions:send-reminders')->assertSuccessful();

        Notification::assertSentTo($bookingA->athlete, SessionReminderNotification::class);
        Notification::assertSentTo($bookingB->athlete, SessionReminderNotification::class);
    });

    it('does not send to sessions that already have reminder_sent_at set', function (): void {
        Carbon::setTestNow('2026-05-01 10:00:00');
        Notification::fake();

        $session = SportSession::factory()->confirmed()->create([
            'date' => '2026-05-02',
            'start_time' => '10:00:00',
        ]);
        $session->reminder_sent_at = now()->subHour()->toDateTimeString();
        $session->save();

        Booking::factory()->confirmed()->for($session, 'sportSession')->create();

        $this->artisan('sessions:send-reminders')->assertSuccessful();

        Notification::assertNothingSent();
    });

    it('does not send to published (unconfirmed) sessions', function (): void {
        Carbon::setTestNow('2026-05-01 10:00:00');
        Notification::fake();

        $session = SportSession::factory()->published()->create([
            'date' => '2026-05-02',
            'start_time' => '10:00:00',
        ]);

        Booking::factory()->confirmed()->for($session, 'sportSession')->create();

        $this->artisan('sessions:send-reminders')->assertSuccessful();

        Notification::assertNothingSent();
    });

    it('marks reminder_sent_at on session after sending', function (): void {
        Carbon::setTestNow('2026-05-01 10:00:00');
        Notification::fake();

        $session = SportSession::factory()->confirmed()->create([
            'date' => '2026-05-02',
            'start_time' => '10:00:00',
        ]);

        Booking::factory()->confirmed()->for($session, 'sportSession')->create();

        $this->artisan('sessions:send-reminders')->assertSuccessful();

        expect($session->fresh()->reminder_sent_at)->not->toBeNull();
    });

    it('does not send to sessions outside the 23h–25h window', function (): void {
        Carbon::setTestNow('2026-05-01 10:00:00');
        Notification::fake();

        // Session starting in only 1 hour — outside the window
        $tooSoon = SportSession::factory()->confirmed()->create([
            'date' => '2026-05-01',
            'start_time' => '11:00:00',
        ]);
        Booking::factory()->confirmed()->for($tooSoon, 'sportSession')->create();

        // Session starting in 48 hours — also outside the window
        $tooLate = SportSession::factory()->confirmed()->create([
            'date' => '2026-05-03',
            'start_time' => '10:00:00',
        ]);
        Booking::factory()->confirmed()->for($tooLate, 'sportSession')->create();

        $this->artisan('sessions:send-reminders')->assertSuccessful();

        Notification::assertNothingSent();
    });

    it('only sends to athletes with confirmed bookings (not pending)', function (): void {
        Carbon::setTestNow('2026-05-01 10:00:00');
        Notification::fake();

        $session = SportSession::factory()->confirmed()->create([
            'date' => '2026-05-02',
            'start_time' => '10:00:00',
        ]);

        $confirmedBooking = Booking::factory()->confirmed()->for($session, 'sportSession')->create();
        $pendingBooking = Booking::factory()->pendingPayment()->for($session, 'sportSession')->create();

        $this->artisan('sessions:send-reminders')->assertSuccessful();

        Notification::assertSentTo($confirmedBooking->athlete, SessionReminderNotification::class);
        Notification::assertNotSentTo($pendingBooking->athlete, SessionReminderNotification::class);
    });
});
