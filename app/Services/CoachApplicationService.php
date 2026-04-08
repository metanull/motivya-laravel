<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CoachProfileStatus;
use App\Events\NewCoachApplication;
use App\Models\CoachProfile;
use App\Models\User;

final class CoachApplicationService
{
    /**
     * Create a new coach profile application.
     *
     * @param  array<string, mixed>  $data
     */
    public function apply(User $user, array $data): CoachProfile
    {
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

        NewCoachApplication::dispatch($coachProfile->id);

        return $coachProfile;
    }
}
