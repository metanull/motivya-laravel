<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\CoachProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CoachApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly int $coachProfileId,
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
        $profile = CoachProfile::findOrFail($this->coachProfileId);

        return (new MailMessage)
            ->subject(__('notifications.coach_approved_subject'))
            ->greeting(__('notifications.greeting', ['name' => $notifiable->name]))
            ->line(__('notifications.coach_approved_body'))
            ->line(__('notifications.coach_approved_specialties', [
                'specialties' => implode(', ', $profile->specialties ?? []),
            ]))
            ->action(__('notifications.coach_approved_action'), url('/'))
            ->line(__('notifications.thanks'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'coach_approved',
            'coach_profile_id' => $this->coachProfileId,
            'message' => __('notifications.coach_approved_body'),
        ];
    }
}
