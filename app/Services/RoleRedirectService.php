<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;

final class RoleRedirectService
{
    /**
     * Return the intended destination URL for the given user based on their role.
     */
    public function pathFor(User $user): string
    {
        return match ($user->role) {
            UserRole::Admin => route('admin.dashboard'),
            UserRole::Accountant => route('accountant.dashboard'),
            UserRole::Coach => route('coach.dashboard'),
            UserRole::Athlete => route('athlete.dashboard'),
        };
    }
}
