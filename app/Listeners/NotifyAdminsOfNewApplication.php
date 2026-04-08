<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\UserRole;
use App\Events\NewCoachApplication;
use App\Models\User;
use App\Notifications\NewCoachApplicationNotification;
use Illuminate\Support\Facades\Notification;

class NotifyAdminsOfNewApplication
{
    public function handle(NewCoachApplication $event): void
    {
        $admins = User::where('role', UserRole::Admin)->get();

        Notification::send($admins, new NewCoachApplicationNotification($event->coachProfileId));
    }
}
