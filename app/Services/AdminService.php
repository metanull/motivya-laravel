<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AuditEventType;
use App\Enums\AuditOperation;
use App\Enums\CoachProfileStatus;
use App\Enums\UserRole;
use App\Events\CoachApproved;
use App\Events\CoachRejected;
use App\Models\CoachProfile;
use App\Services\Audit\AuditService;
use App\Services\Audit\AuditSubject;
use Illuminate\Support\Facades\DB;

final class AdminService
{
    public function __construct(private readonly AuditService $auditService) {}

    /**
     * Approve a coach application.
     *
     * Changes the coach profile status to approved, promotes the user to coach role,
     * and sets the verified_at timestamp.
     */
    public function approveCoach(CoachProfile $coachProfile): void
    {
        DB::transaction(function () use ($coachProfile): void {
            $oldStatus = $coachProfile->status;

            $coachProfile->update([
                'status' => CoachProfileStatus::Approved,
                'verified_at' => now(),
            ]);

            $coachProfile->user->update([
                'role' => UserRole::Coach,
            ]);

            $this->auditService->record(
                AuditEventType::CoachApproved,
                AuditOperation::StateChange,
                $coachProfile,
                subjects: [
                    AuditSubject::primary($coachProfile),
                    AuditSubject::related($coachProfile->user, 'coach'),
                ],
                oldValues: ['status' => $oldStatus->value],
                newValues: ['status' => CoachProfileStatus::Approved->value],
            );
        });

        CoachApproved::dispatch($coachProfile->id);
    }

    /**
     * Reject a coach application.
     */
    public function rejectCoach(CoachProfile $coachProfile, string $reason): void
    {
        DB::transaction(function () use ($coachProfile, $reason): void {
            $oldStatus = $coachProfile->status;

            $coachProfile->update([
                'status' => CoachProfileStatus::Rejected,
            ]);

            $this->auditService->record(
                AuditEventType::CoachRejected,
                AuditOperation::StateChange,
                $coachProfile,
                subjects: [AuditSubject::primary($coachProfile)],
                oldValues: ['status' => $oldStatus->value],
                newValues: ['status' => CoachProfileStatus::Rejected->value],
                metadata: ['reason' => $reason],
            );
        });

        CoachRejected::dispatch($coachProfile->id, $reason);
    }
}
