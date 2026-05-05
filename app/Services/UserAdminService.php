<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AuditEventType;
use App\Enums\AuditOperation;
use App\Enums\UserRole;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\Audit\AuditSubject;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class UserAdminService
{
    public function __construct(private readonly AuditService $auditService) {}

    /**
     * Create an admin or accountant user and record the audit event.
     */
    public function create(string $name, string $email, UserRole $role): User
    {
        return DB::transaction(function () use ($name, $email, $role): User {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => bcrypt(Str::random(32)),
                'role' => $role,
            ]);

            $user->forceFill(['email_verified_at' => now()])->save();

            $this->auditService->record(
                AuditEventType::UserCreatedByAdmin,
                AuditOperation::Create,
                $user,
                subjects: [AuditSubject::primary($user)],
                newValues: [
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role->value,
                ],
            );

            return $user;
        });
    }

    /**
     * Change a user's role and record the audit event.
     */
    public function changeRole(User $user, UserRole $newRole): void
    {
        DB::transaction(function () use ($user, $newRole): void {
            $oldRole = $user->role;

            $user->update(['role' => $newRole]);

            $this->auditService->record(
                AuditEventType::UserRoleChanged,
                AuditOperation::StateChange,
                $user,
                subjects: [AuditSubject::primary($user)],
                oldValues: ['role' => $oldRole->value],
                newValues: ['role' => $newRole->value],
            );
        });
    }

    /**
     * Suspend a user and record the audit event.
     */
    public function suspend(User $user, string $reason): void
    {
        DB::transaction(function () use ($user, $reason): void {
            $user->update([
                'suspended_at' => now(),
                'suspension_reason' => $reason,
            ]);

            $this->auditService->record(
                AuditEventType::UserSuspended,
                AuditOperation::StateChange,
                $user,
                subjects: [AuditSubject::primary($user)],
                newValues: ['suspended_at' => now()->toISOString()],
                metadata: ['reason' => $reason],
            );
        });
    }

    /**
     * Reactivate a suspended user and record the audit event.
     */
    public function reactivate(User $user): void
    {
        $previousSuspendedAt = $user->suspended_at?->toISOString();

        DB::transaction(function () use ($user, $previousSuspendedAt): void {
            $user->update([
                'suspended_at' => null,
                'suspension_reason' => null,
            ]);

            $this->auditService->record(
                AuditEventType::UserReactivated,
                AuditOperation::StateChange,
                $user,
                subjects: [AuditSubject::primary($user)],
                oldValues: ['suspended_at' => $previousSuspendedAt],
                newValues: ['suspended_at' => null],
            );
        });
    }
}
