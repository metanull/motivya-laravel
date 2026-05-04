<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

final class UserPolicy
{
    /**
     * Admin bypass — grants all abilities.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->role === UserRole::Admin) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view the list of users.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view another user's profile.
     */
    public function view(User $user, User $model): bool
    {
        return $user->id === $model->id;
    }

    /**
     * Determine whether the user can update another user.
     */
    public function update(User $user, User $model): bool
    {
        return $user->id === $model->id;
    }

    /**
     * Determine whether the user can delete a user.
     */
    public function delete(User $user, User $model): bool
    {
        return false;
    }

    /**
     * Determine whether the user can change another user's role.
     */
    public function promote(User $user, User $model): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create a privileged back-office user (accountant or admin).
     */
    public function createPrivileged(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can suspend another user.
     */
    public function suspend(User $user, User $model): bool
    {
        return false;
    }

    /**
     * Determine whether the user can reactivate a suspended user.
     */
    public function reactivate(User $user, User $model): bool
    {
        return false;
    }

    /**
     * Determine whether the user can change another user's role.
     * (non-privileged role changes — admin bypass handles admin access)
     */
    public function changeRole(User $user, User $model): bool
    {
        return false;
    }
}
