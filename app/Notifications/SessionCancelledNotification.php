<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\SportSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class SessionCancelledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly int $sessionId,
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
        $session = SportSession::findOrFail($this->sessionId);

        return (new MailMessage)
            ->subject(__('notifications.session_cancelled_subject'))
            ->line(__('notifications.session_cancelled_body', [
                'activity' => $session->activity_type->label(),
                'date' => $session->date->translatedFormat('l j F Y'),
            ]))
            ->line(__('notifications.thanks'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'session_cancelled',
            'session_id' => $this->sessionId,
        ];
    }
}
