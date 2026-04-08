<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\CoachApproved;
use App\Models\CoachProfile;
use App\Notifications\CoachApprovedNotification;

class SendCoachApprovedNotification
{
    public function handle(CoachApproved $event): void
    {
        $coachProfile = CoachProfile::with('user')->findOrFail($event->coachProfileId);

        $coachProfile->user->notify(new CoachApprovedNotification($event->coachProfileId));
    }
}
