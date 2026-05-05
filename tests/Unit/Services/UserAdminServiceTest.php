<?php

declare(strict_types=1);

use App\Enums\AuditEventType;
use App\Enums\UserRole;
use App\Models\AuditEvent;
use App\Models\User;
use App\Services\UserAdminService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('UserAdminService audit', function () {
    it('records a user.created_by_admin event when creating a user', function () {
        $service = app(UserAdminService::class);

        $service->create(
            name: 'Test Coach',
            email: 'coach@example.com',
            role: UserRole::Coach,
        );

        expect(
            AuditEvent::where('event_type', AuditEventType::UserCreatedByAdmin->value)->exists()
        )->toBeTrue();
    });

    it('records a user.role_changed event when changing role', function () {
        $service = app(UserAdminService::class);
        $user = User::factory()->athlete()->create();

        $service->changeRole($user, UserRole::Coach);

        expect(
            AuditEvent::where('event_type', AuditEventType::UserRoleChanged->value)->exists()
        )->toBeTrue();
    });

    it('records old and new role values in the audit event', function () {
        $service = app(UserAdminService::class);
        $user = User::factory()->athlete()->create();

        $service->changeRole($user, UserRole::Coach);

        $audit = AuditEvent::where('event_type', AuditEventType::UserRoleChanged->value)->firstOrFail();

        expect($audit->old_values['role'])->toBe(UserRole::Athlete->value)
            ->and($audit->new_values['role'])->toBe(UserRole::Coach->value);
    });

    it('records a user.suspended event when suspending a user', function () {
        $service = app(UserAdminService::class);
        $user = User::factory()->athlete()->create();

        $service->suspend($user, 'Violation of terms');

        expect(
            AuditEvent::where('event_type', AuditEventType::UserSuspended->value)->exists()
        )->toBeTrue();
    });

    it('records a user.reactivated event when reactivating a user', function () {
        $service = app(UserAdminService::class);
        $user = User::factory()->athlete()->create(['suspended_at' => now()]);

        $service->reactivate($user);

        expect(
            AuditEvent::where('event_type', AuditEventType::UserReactivated->value)->exists()
        )->toBeTrue();
    });
});
