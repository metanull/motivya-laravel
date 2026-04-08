<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\CoachProfile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewCoachApplicationNotification extends Notification implements ShouldQueue
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
        $profile = CoachProfile::with('user')->findOrFail($this->coachProfileId);

        return (new MailMessage)
            ->subject(__('notifications.new_coach_application_subject'))
            ->greeting(__('notifications.greeting', ['name' => $notifiable->name]))
            ->line(__('notifications.new_coach_application_body', [
                'name' => $profile->user->name,
                'email' => $profile->user->email,
            ]))
            ->line(__('notifications.new_coach_application_specialties', [
                'specialties' => implode(', ', $profile->specialties ?? []),
            ]))
            ->action(__('notifications.new_coach_application_action'), url(route('admin.coach-approval')))
            ->line(__('notifications.thanks'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_coach_application',
            'coach_profile_id' => $this->coachProfileId,
            'message' => __('notifications.new_coach_application_body_short'),
        ];
    }
}
