<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class BookingCancelledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly int $bookingId,
        private readonly bool $refundEligible = false,
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

        $mail = (new MailMessage)
            ->subject(__('notifications.booking_cancelled_subject'))
            ->line(__('notifications.booking_cancelled_body', [
                'activity' => $session->activity_type->label(),
                'date' => $session->date->translatedFormat('l j F Y'),
            ]));

        if ($this->refundEligible) {
            $mail->line(__('notifications.booking_cancelled_refund'));
        }

        return $mail->line(__('notifications.thanks'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'booking_cancelled',
            'booking_id' => $this->bookingId,
            'refund_eligible' => $this->refundEligible,
        ];
    }
}
