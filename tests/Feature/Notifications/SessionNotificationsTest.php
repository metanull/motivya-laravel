<?php

declare(strict_types=1);

use App\Events\SessionCancelled;
use App\Events\SessionConfirmed;
use App\Listeners\SendSessionCancelledNotification;
use App\Listeners\SendSessionConfirmedNotification;
use App\Models\Booking;
use App\Models\SportSession;
use App\Notifications\SessionCancelledNotification;
use App\Notifications\SessionConfirmedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

describe('Session Notifications', function () {

    it('SessionConfirmed event sends notification to coach and all confirmed athletes', function (): void {
        Notification::fake();

        $session = SportSession::factory()->confirmed()->create();

        $confirmedBookingA = Booking::factory()->confirmed()->for($session, 'sportSession')->create();
        $confirmedBookingB = Booking::factory()->confirmed()->for($session, 'sportSession')->create();
        // Pending booking — should NOT receive session confirmed notification
        Booking::factory()->pendingPayment()->for($session, 'sportSession')->create();

        $listener = new SendSessionConfirmedNotification;
        $listener->handle(new SessionConfirmed($session->id));

        Notification::assertSentTo($session->coach, SessionConfirmedNotification::class);
        Notification::assertSentTo($confirmedBookingA->athlete, SessionConfirmedNotification::class);
        Notification::assertSentTo($confirmedBookingB->athlete, SessionConfirmedNotification::class);
    });

    it('SessionCancelled event sends notification to coach and booked athletes', function (): void {
        Notification::fake();

        $session = SportSession::factory()->confirmed()->create();

        $confirmedBooking = Booking::factory()->confirmed()->for($session, 'sportSession')->create();
        $pendingBooking = Booking::factory()->pendingPayment()->for($session, 'sportSession')->create();
        // Refunded/cancelled bookings should NOT receive the notification
        Booking::factory()->cancelled()->for($session, 'sportSession')->create();
        Booking::factory()->refunded()->for($session, 'sportSession')->create();

        $listener = new SendSessionCancelledNotification;
        $listener->handle(new SessionCancelled($session));

        Notification::assertSentTo($session->coach, SessionCancelledNotification::class);
        Notification::assertSentTo($confirmedBooking->athlete, SessionCancelledNotification::class);
        Notification::assertSentTo($pendingBooking->athlete, SessionCancelledNotification::class);
    });

    it('SessionConfirmedNotification uses mail and database channels', function (): void {
        Notification::fake();

        $session = SportSession::factory()->confirmed()->create();

        $listener = new SendSessionConfirmedNotification;
        $listener->handle(new SessionConfirmed($session->id));

        Notification::assertSentTo(
            $session->coach,
            SessionConfirmedNotification::class,
            fn ($notification, $channels) => $channels === ['mail', 'database'],
        );
    });

    it('SessionCancelledNotification uses mail and database channels', function (): void {
        Notification::fake();

        $session = SportSession::factory()->confirmed()->create();

        $listener = new SendSessionCancelledNotification;
        $listener->handle(new SessionCancelled($session));

        Notification::assertSentTo(
            $session->coach,
            SessionCancelledNotification::class,
            fn ($notification, $channels) => $channels === ['mail', 'database'],
        );
    });

    it('SessionConfirmedNotification toArray contains correct type and session_id', function (): void {
        $session = SportSession::factory()->confirmed()->create();

        $notification = new SessionConfirmedNotification($session->id);
        $data = $notification->toArray($session->coach);

        expect($data['type'])->toBe('session_confirmed')
            ->and($data['session_id'])->toBe($session->id);
    });

    it('SessionCancelledNotification toArray contains correct type and session_id', function (): void {
        $session = SportSession::factory()->confirmed()->create();

        $notification = new SessionCancelledNotification($session->id);
        $data = $notification->toArray($session->coach);

        expect($data['type'])->toBe('session_cancelled')
            ->and($data['session_id'])->toBe($session->id);
    });

    it('SessionConfirmedNotification mail subject is correct', function (): void {
        $session = SportSession::factory()->confirmed()->create();

        $notification = new SessionConfirmedNotification($session->id);
        $mail = $notification->toMail($session->coach);

        expect($mail->subject)->toBe(__('notifications.session_confirmed_subject'));
    });

    it('SessionCancelledNotification mail subject is correct', function (): void {
        $session = SportSession::factory()->confirmed()->create();

        $notification = new SessionCancelledNotification($session->id);
        $mail = $notification->toMail($session->coach);

        expect($mail->subject)->toBe(__('notifications.session_cancelled_subject'));
    });

    it('SessionCancelled event does not send to cancelled or refunded bookings', function (): void {
        Notification::fake();

        $session = SportSession::factory()->confirmed()->create();

        $cancelledBooking = Booking::factory()->cancelled()->for($session, 'sportSession')->create();
        $refundedBooking = Booking::factory()->refunded()->for($session, 'sportSession')->create();

        $listener = new SendSessionCancelledNotification;
        $listener->handle(new SessionCancelled($session));

        Notification::assertNotSentTo($cancelledBooking->athlete, SessionCancelledNotification::class);
        Notification::assertNotSentTo($refundedBooking->athlete, SessionCancelledNotification::class);
    });
});
