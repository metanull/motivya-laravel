<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\SportSession;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

final class SessionReminderNotification extends Notification implements ShouldQueue
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
            ->subject(__('notifications.session_reminder_subject'))
            ->line(__('notifications.session_reminder_body', [
                'activity' => $session->activity_type->label(),
                'time' => Carbon::parse($session->start_time)->format('H:i'),
            ]))
            ->action(__('notifications.view_session'), route('sessions.show', $this->sessionId))
            ->line(__('notifications.thanks'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'session_reminder',
            'session_id' => $this->sessionId,
        ];
    }
}
