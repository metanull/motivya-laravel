<?php

declare(strict_types=1);

use App\Enums\AuditEventType;
use App\Enums\CoachProfileStatus;
use App\Models\AuditEvent;
use App\Models\CoachProfile;
use App\Models\User;
use App\Services\AdminService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('AdminService audit', function () {
    it('records a coach.approved event when approving a coach', function () {
        $coach = User::factory()->athlete()->create();
        $profile = CoachProfile::factory()->pending()->for($coach)->create();
        $service = app(AdminService::class);

        $service->approveCoach($profile);

        expect(
            AuditEvent::where('event_type', AuditEventType::CoachApproved->value)->exists()
        )->toBeTrue();
    });

    it('records old and new status values in the coach.approved audit', function () {
        $coach = User::factory()->athlete()->create();
        $profile = CoachProfile::factory()->pending()->for($coach)->create();
        $service = app(AdminService::class);

        $service->approveCoach($profile);

        $audit = AuditEvent::where('event_type', AuditEventType::CoachApproved->value)->firstOrFail();

        expect($audit->old_values['status'])->toBe(CoachProfileStatus::Pending->value)
            ->and($audit->new_values['status'])->toBe(CoachProfileStatus::Approved->value);
    });

    it('records a coach.rejected event when rejecting a coach', function () {
        $coach = User::factory()->athlete()->create();
        $profile = CoachProfile::factory()->pending()->for($coach)->create();
        $service = app(AdminService::class);

        $service->rejectCoach($profile, 'Insufficient credentials');

        expect(
            AuditEvent::where('event_type', AuditEventType::CoachRejected->value)->exists()
        )->toBeTrue();
    });

    it('includes the rejection reason in the audit metadata', function () {
        $coach = User::factory()->athlete()->create();
        $profile = CoachProfile::factory()->pending()->for($coach)->create();
        $service = app(AdminService::class);

        $service->rejectCoach($profile, 'Insufficient credentials');

        $audit = AuditEvent::where('event_type', AuditEventType::CoachRejected->value)->firstOrFail();

        expect($audit->metadata['reason'])->toBe('Insufficient credentials');
    });

    it('wraps rejectCoach in a transaction — audit and status change are atomic', function () {
        $coach = User::factory()->athlete()->create();
        $profile = CoachProfile::factory()->pending()->for($coach)->create();
        $service = app(AdminService::class);

        $service->rejectCoach($profile, 'Test atomicity');

        expect($profile->fresh()->status)->toBe(CoachProfileStatus::Rejected)
            ->and(AuditEvent::where('event_type', AuditEventType::CoachRejected->value)->exists())->toBeTrue();
    });
});
