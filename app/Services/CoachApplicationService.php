<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AuditEventType;
use App\Enums\AuditOperation;
use App\Enums\CoachProfileStatus;
use App\Events\NewCoachApplication;
use App\Models\CoachProfile;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\Audit\AuditSubject;
use Illuminate\Support\Facades\DB;

final class CoachApplicationService
{
    public function __construct(private readonly AuditService $auditService) {}

    /**
     * Create a new coach profile application.
     *
     * @param  array<string, mixed>  $data
     */
    public function apply(User $user, array $data): CoachProfile
    {
        $coachProfile = DB::transaction(function () use ($user, $data): CoachProfile {
            $coachProfile = CoachProfile::create([
                'user_id' => $user->id,
                'status' => CoachProfileStatus::Pending,
                'specialties' => $data['specialties'],
                'bio' => $data['bio'] !== '' ? $data['bio'] : null,
                'experience_level' => $data['experience_level'] !== '' ? $data['experience_level'] : null,
                'postal_code' => $data['postal_code'],
                'country' => $data['country'],
                'enterprise_number' => $data['enterprise_number'],
            ]);

            $this->auditService->record(
                AuditEventType::CoachApplicationSubmitted,
                AuditOperation::Create,
                $coachProfile,
                subjects: [
                    AuditSubject::primary($coachProfile),
                    AuditSubject::related($user, 'coach'),
                ],
                newValues: ['status' => CoachProfileStatus::Pending->value],
            );

            return $coachProfile;
        });

        NewCoachApplication::dispatch($coachProfile->id);

        return $coachProfile;
    }
}
