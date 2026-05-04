<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminUserOnboardingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $resetUrl,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('notifications.admin_onboarding_subject'))
            ->greeting(__('notifications.greeting', ['name' => $notifiable->name]))
            ->line(__('notifications.admin_onboarding_body'))
            ->action(__('notifications.admin_onboarding_action'), $this->resetUrl)
            ->line(__('notifications.admin_onboarding_hint'))
            ->line(__('notifications.thanks'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'admin_onboarding',
        ];
    }
}
