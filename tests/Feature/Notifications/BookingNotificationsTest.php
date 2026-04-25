<?php

declare(strict_types=1);

use App\Events\BookingCancelled;
use App\Events\BookingCreated;
use App\Listeners\SendBookingCancelledNotification;
use App\Listeners\SendBookingConfirmedNotification;
use App\Models\Booking;
use App\Models\SportSession;
use App\Notifications\BookingCancelledNotification;
use App\Notifications\BookingConfirmedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

describe('Booking Notifications', function () {

    it('BookingCreated event triggers SendBookingConfirmedNotification and sends to athlete', function (): void {
        Notification::fake();

        $session = SportSession::factory()->confirmed()->create();
        $booking = Booking::factory()->confirmed()->for($session, 'sportSession')->create();

        $listener = new SendBookingConfirmedNotification;
        $listener->handle(new BookingCreated($booking->id));

        Notification::assertSentTo($booking->athlete, BookingConfirmedNotification::class);
    });

    it('BookingCancelled event triggers SendBookingCancelledNotification and sends to athlete', function (): void {
        Notification::fake();

        $session = SportSession::factory()->confirmed()->create();
        $booking = Booking::factory()->cancelled()->for($session, 'sportSession')->create();

        $listener = new SendBookingCancelledNotification;
        $listener->handle(new BookingCancelled($booking->id, 'athlete_requested', false));

        Notification::assertSentTo($booking->athlete, BookingCancelledNotification::class);
    });

    it('BookingConfirmedNotification uses mail and database channels', function (): void {
        Notification::fake();

        $session = SportSession::factory()->confirmed()->create();
        $booking = Booking::factory()->confirmed()->for($session, 'sportSession')->create();

        $listener = new SendBookingConfirmedNotification;
        $listener->handle(new BookingCreated($booking->id));

        Notification::assertSentTo(
            $booking->athlete,
            BookingConfirmedNotification::class,
            fn ($notification, $channels) => $channels === ['mail', 'database'],
        );
    });

    it('BookingCancelledNotification uses mail and database channels', function (): void {
        Notification::fake();

        $session = SportSession::factory()->confirmed()->create();
        $booking = Booking::factory()->cancelled()->for($session, 'sportSession')->create();

        $listener = new SendBookingCancelledNotification;
        $listener->handle(new BookingCancelled($booking->id, 'session_cancelled', true));

        Notification::assertSentTo(
            $booking->athlete,
            BookingCancelledNotification::class,
            fn ($notification, $channels) => $channels === ['mail', 'database'],
        );
    });

    it('BookingConfirmedNotification toArray contains correct type and booking_id', function (): void {
        $session = SportSession::factory()->confirmed()->create();
        $booking = Booking::factory()->confirmed()->for($session, 'sportSession')->create();

        $notification = new BookingConfirmedNotification($booking->id);
        $data = $notification->toArray($booking->athlete);

        expect($data['type'])->toBe('booking_confirmed')
            ->and($data['booking_id'])->toBe($booking->id);
    });

    it('BookingCancelledNotification toArray contains correct type, booking_id and refund_eligible', function (): void {
        $session = SportSession::factory()->confirmed()->create();
        $booking = Booking::factory()->cancelled()->for($session, 'sportSession')->create();

        $notification = new BookingCancelledNotification($booking->id, true);
        $data = $notification->toArray($booking->athlete);

        expect($data['type'])->toBe('booking_cancelled')
            ->and($data['booking_id'])->toBe($booking->id)
            ->and($data['refund_eligible'])->toBeTrue();
    });

    it('BookingConfirmedNotification mail subject is correct', function (): void {
        $session = SportSession::factory()->confirmed()->create();
        $booking = Booking::factory()->confirmed()->for($session, 'sportSession')->create();

        $notification = new BookingConfirmedNotification($booking->id);
        $mail = $notification->toMail($booking->athlete);

        expect($mail->subject)->toBe(__('notifications.booking_confirmed_subject'));
    });

    it('BookingCancelledNotification mail subject is correct', function (): void {
        $session = SportSession::factory()->confirmed()->create();
        $booking = Booking::factory()->cancelled()->for($session, 'sportSession')->create();

        $notification = new BookingCancelledNotification($booking->id);
        $mail = $notification->toMail($booking->athlete);

        expect($mail->subject)->toBe(__('notifications.booking_cancelled_subject'));
    });

    it('BookingCancelledNotification includes refund line when refundEligible is true', function (): void {
        $session = SportSession::factory()->confirmed()->create();
        $booking = Booking::factory()->cancelled()->for($session, 'sportSession')->create();

        $notification = new BookingCancelledNotification($booking->id, true);
        $mail = $notification->toMail($booking->athlete);

        $allLines = collect($mail->introLines)->implode(' ');

        expect($allLines)->toContain(__('notifications.booking_cancelled_refund'));
    });

    it('BookingCancelledNotification omits refund line when refundEligible is false', function (): void {
        $session = SportSession::factory()->confirmed()->create();
        $booking = Booking::factory()->cancelled()->for($session, 'sportSession')->create();

        $notification = new BookingCancelledNotification($booking->id, false);
        $mail = $notification->toMail($booking->athlete);

        $allLines = collect($mail->introLines)->implode(' ');

        expect($allLines)->not->toContain(__('notifications.booking_cancelled_refund'));
    });
});
