<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CoachProfileStatus;
use App\Enums\UserRole;
use App\Events\CoachApproved;
use App\Events\CoachRejected;
use App\Models\CoachProfile;
use Illuminate\Support\Facades\DB;

final class AdminService
{
    /**
     * Approve a coach application.
     *
     * Changes the coach profile status to approved, promotes the user to coach role,
     * and sets the verified_at timestamp.
     */
    public function approveCoach(CoachProfile $coachProfile): void
    {
        DB::transaction(function () use ($coachProfile): void {
            $coachProfile->update([
                'status' => CoachProfileStatus::Approved,
                'verified_at' => now(),
            ]);

            $coachProfile->user->update([
                'role' => UserRole::Coach,
            ]);
        });

        CoachApproved::dispatch($coachProfile->id);
    }

    /**
     * Reject a coach application.
     */
    public function rejectCoach(CoachProfile $coachProfile, string $reason): void
    {
        $coachProfile->update([
            'status' => CoachProfileStatus::Rejected,
        ]);

        CoachRejected::dispatch($coachProfile->id, $reason);
    }
}
