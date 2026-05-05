<?php

declare(strict_types=1);

use App\Enums\AuditEventType;
use App\Models\AuditEvent;
use App\Models\User;
use App\Policies\AuditEventPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('AuditEventPolicy', function () {

    // ── viewAny ───────────────────────────────────────────────────────────

    it('admin can viewAny audit events', function () {
        $admin = User::factory()->admin()->create();
        $policy = new AuditEventPolicy;

        expect($policy->viewAny($admin))->toBeTrue();
    });

    it('accountant can viewAny audit events', function () {
        $accountant = User::factory()->accountant()->create();
        $policy = new AuditEventPolicy;

        expect($policy->viewAny($accountant))->toBeTrue();
    });

    it('coach cannot viewAny audit events', function () {
        $coach = User::factory()->coach()->create();
        $policy = new AuditEventPolicy;

        expect($policy->viewAny($coach))->toBeFalse();
    });

    it('athlete cannot viewAny audit events', function () {
        $athlete = User::factory()->athlete()->create();
        $policy = new AuditEventPolicy;

        expect($policy->viewAny($athlete))->toBeFalse();
    });

    // ── view: admin sees all ──────────────────────────────────────────────

    it('admin can view any event type', function () {
        $admin = User::factory()->admin()->create();
        $policy = new AuditEventPolicy;

        foreach (AuditEventType::cases() as $type) {
            $event = AuditEvent::factory()->create(['event_type' => $type->value]);
            expect($policy->view($admin, $event))->toBeTrue("Admin should view {$type->value}");
        }
    });

    // ── view: accountant sees only financial types ────────────────────────

    it('accountant can view financial event types', function () {
        $accountant = User::factory()->accountant()->create();
        $policy = new AuditEventPolicy;

        foreach (AuditEventPolicy::financialTypes() as $type) {
            $event = AuditEvent::factory()->create(['event_type' => $type->value]);
            expect($policy->view($accountant, $event))->toBeTrue("Accountant should view {$type->value}");
        }
    });

    it('accountant cannot view user-administration event types', function () {
        $accountant = User::factory()->accountant()->create();
        $policy = new AuditEventPolicy;

        $nonFinancialTypes = [
            AuditEventType::CoachApplicationSubmitted,
            AuditEventType::CoachApproved,
            AuditEventType::CoachRejected,
            AuditEventType::UserCreatedByAdmin,
            AuditEventType::UserRoleChanged,
            AuditEventType::UserSuspended,
            AuditEventType::UserReactivated,
            AuditEventType::SessionCreated,
            AuditEventType::SessionUpdated,
            AuditEventType::SessionPublished,
            AuditEventType::SessionCancelled,
            AuditEventType::SessionCompleted,
            AuditEventType::SessionDeleted,
        ];

        foreach ($nonFinancialTypes as $type) {
            $event = AuditEvent::factory()->create(['event_type' => $type->value]);
            expect($policy->view($accountant, $event))->toBeFalse("Accountant should NOT view {$type->value}");
        }
    });

    it('coach cannot view any audit event', function () {
        $coach = User::factory()->coach()->create();
        $policy = new AuditEventPolicy;
        $event = AuditEvent::factory()->create();

        expect($policy->view($coach, $event))->toBeFalse();
    });

    it('athlete cannot view any audit event', function () {
        $athlete = User::factory()->athlete()->create();
        $policy = new AuditEventPolicy;
        $event = AuditEvent::factory()->create();

        expect($policy->view($athlete, $event))->toBeFalse();
    });

    // ── Mutation abilities: all denied ────────────────────────────────────

    it('admin cannot create audit events via policy', function () {
        $admin = User::factory()->admin()->create();
        $policy = new AuditEventPolicy;

        expect($policy->create($admin))->toBeFalse();
    });

    it('accountant cannot create audit events via policy', function () {
        $accountant = User::factory()->accountant()->create();
        $policy = new AuditEventPolicy;

        expect($policy->create($accountant))->toBeFalse();
    });

    it('admin cannot update audit events via policy', function () {
        $admin = User::factory()->admin()->create();
        $policy = new AuditEventPolicy;
        $event = AuditEvent::factory()->create();

        expect($policy->update($admin, $event))->toBeFalse();
    });

    it('admin cannot delete audit events via policy', function () {
        $admin = User::factory()->admin()->create();
        $policy = new AuditEventPolicy;
        $event = AuditEvent::factory()->create();

        expect($policy->delete($admin, $event))->toBeFalse();
    });

    it('admin cannot force-delete audit events via policy', function () {
        $admin = User::factory()->admin()->create();
        $policy = new AuditEventPolicy;
        $event = AuditEvent::factory()->create();

        expect($policy->forceDelete($admin, $event))->toBeFalse();
    });

    it('admin cannot restore audit events via policy', function () {
        $admin = User::factory()->admin()->create();
        $policy = new AuditEventPolicy;
        $event = AuditEvent::factory()->create();

        expect($policy->restore($admin, $event))->toBeFalse();
    });

    it('accountant cannot update audit events via policy', function () {
        $accountant = User::factory()->accountant()->create();
        $policy = new AuditEventPolicy;
        $event = AuditEvent::factory()->create([
            'event_type' => AuditEventType::InvoiceGenerated->value,
        ]);

        expect($policy->update($accountant, $event))->toBeFalse();
    });

    it('accountant cannot delete audit events via policy', function () {
        $accountant = User::factory()->accountant()->create();
        $policy = new AuditEventPolicy;
        $event = AuditEvent::factory()->create([
            'event_type' => AuditEventType::InvoiceGenerated->value,
        ]);

        expect($policy->delete($accountant, $event))->toBeFalse();
    });

    it('accountant cannot force-delete audit events via policy', function () {
        $accountant = User::factory()->accountant()->create();
        $policy = new AuditEventPolicy;
        $event = AuditEvent::factory()->create([
            'event_type' => AuditEventType::InvoiceGenerated->value,
        ]);

        expect($policy->forceDelete($accountant, $event))->toBeFalse();
    });

    it('accountant cannot restore audit events via policy', function () {
        $accountant = User::factory()->accountant()->create();
        $policy = new AuditEventPolicy;
        $event = AuditEvent::factory()->create([
            'event_type' => AuditEventType::InvoiceGenerated->value,
        ]);

        expect($policy->restore($accountant, $event))->toBeFalse();
    });

});
