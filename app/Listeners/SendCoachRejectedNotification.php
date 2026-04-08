<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\CoachRejected;
use App\Models\CoachProfile;
use App\Notifications\CoachRejectedNotification;

class SendCoachRejectedNotification
{
    public function handle(CoachRejected $event): void
    {
        $coachProfile = CoachProfile::with('user')->findOrFail($event->coachProfileId);

        $coachProfile->user->notify(new CoachRejectedNotification($event->coachProfileId, $event->reason));
    }
}
