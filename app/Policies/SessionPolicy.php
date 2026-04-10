<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\SessionStatus;
use App\Enums\UserRole;
use App\Models\SportSession;
use App\Models\User;

final class SessionPolicy
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
     * Any authenticated user can view the list of sessions.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Any authenticated user can view published/confirmed sessions.
     * Coaches can also view their own drafts.
     */
    public function view(User $user, SportSession $session): bool
    {
        if (in_array($session->status, [SessionStatus::Published, SessionStatus::Confirmed], true)) {
            return true;
        }

        return $user->role === UserRole::Coach && $user->id === $session->coach_id;
    }

    /**
     * Only coaches can create sessions.
     */
    public function create(User $user): bool
    {
        return $user->role === UserRole::Coach;
    }

    /**
     * Own coach can update; not allowed if completed or cancelled.
     */
    public function update(User $user, SportSession $session): bool
    {
        if (in_array($session->status, [SessionStatus::Completed, SessionStatus::Cancelled], true)) {
            return false;
        }

        return $user->role === UserRole::Coach && $user->id === $session->coach_id;
    }

    /**
     * Own coach can delete; only draft sessions.
     */
    public function delete(User $user, SportSession $session): bool
    {
        if ($session->status !== SessionStatus::Draft) {
            return false;
        }

        return $user->role === UserRole::Coach && $user->id === $session->coach_id;
    }

    /**
     * Own coach can cancel; only published or confirmed sessions.
     */
    public function cancel(User $user, SportSession $session): bool
    {
        if (! in_array($session->status, [SessionStatus::Published, SessionStatus::Confirmed], true)) {
            return false;
        }

        return $user->role === UserRole::Coach && $user->id === $session->coach_id;
    }
}
