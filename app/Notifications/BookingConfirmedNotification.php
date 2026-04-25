<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

final class BookingConfirmedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly int $bookingId,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $booking = Booking::with('sportSession')->findOrFail($this->bookingId);
        $session = $booking->sportSession;

        return (new MailMessage)
            ->subject(__('notifications.booking_confirmed_subject'))
            ->line(__('notifications.booking_confirmed_body', [
                'activity' => $session->activity_type->label(),
                'date' => $session->date->translatedFormat('l j F Y'),
                'time' => Carbon::parse($session->start_time)->format('H:i'),
            ]))
            ->action(__('notifications.view_booking'), route('sessions.show', $session->getKey()))
            ->line(__('notifications.thanks'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'booking_confirmed',
            'booking_id' => $this->bookingId,
        ];
    }
}
