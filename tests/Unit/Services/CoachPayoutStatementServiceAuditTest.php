<?php

declare(strict_types=1);

use App\Enums\AuditEventType;
use App\Enums\CoachPayoutStatementStatus;
use App\Enums\UserRole;
use App\Models\AuditEvent;
use App\Models\CoachPayoutStatement;
use App\Models\CoachProfile;
use App\Models\User;
use App\Services\CoachPayoutStatementService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('CoachPayoutStatementService audit', function () {
    it('records a payout_statement.generated event when generating a statement', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->for($coach)->create(['is_vat_subject' => false]);
        $service = app(CoachPayoutStatementService::class);

        $service->generateForCoach($coach, 2024, 1);

        expect(
            AuditEvent::where('event_type', AuditEventType::PayoutStatementGenerated->value)->exists()
        )->toBeTrue();
    });

    it('records a payout_statement.submitted event when marking invoice submitted', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->for($coach)->create(['is_vat_subject' => false]);
        $statement = CoachPayoutStatement::factory()->for($coach, 'coach')->create([
            'status' => CoachPayoutStatementStatus::ReadyForInvoice,
        ]);
        $service = app(CoachPayoutStatementService::class);

        $service->markInvoiceSubmitted($statement);

        expect(
            AuditEvent::where('event_type', AuditEventType::PayoutStatementSubmitted->value)->exists()
        )->toBeTrue();
    });

    it('records a payout_statement.approved event when approving a statement', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->for($coach)->create(['is_vat_subject' => false]);
        $accountant = User::factory()->create(['role' => UserRole::Accountant]);
        $statement = CoachPayoutStatement::factory()->for($coach, 'coach')->create([
            'status' => CoachPayoutStatementStatus::InvoiceSubmitted,
        ]);
        $service = app(CoachPayoutStatementService::class);

        $service->approve($statement, $accountant);

        expect(
            AuditEvent::where('event_type', AuditEventType::PayoutStatementApproved->value)->exists()
        )->toBeTrue();
    });

    it('records a payout_statement.blocked event with reason metadata', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->for($coach)->create(['is_vat_subject' => false]);
        $accountant = User::factory()->create(['role' => UserRole::Accountant]);
        $statement = CoachPayoutStatement::factory()->for($coach, 'coach')->create([
            'status' => CoachPayoutStatementStatus::InvoiceSubmitted,
        ]);
        $service = app(CoachPayoutStatementService::class);

        $service->block($statement, $accountant, 'Missing invoice document');

        $audit = AuditEvent::where('event_type', AuditEventType::PayoutStatementBlocked->value)->firstOrFail();

        expect($audit->metadata['reason'])->toBe('Missing invoice document');
    });

    it('records a payout_statement.paid event when marking as paid', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->for($coach)->create(['is_vat_subject' => false]);
        $accountant = User::factory()->create(['role' => UserRole::Accountant]);
        $statement = CoachPayoutStatement::factory()->for($coach, 'coach')->create([
            'status' => CoachPayoutStatementStatus::Approved,
        ]);
        $service = app(CoachPayoutStatementService::class);

        $service->markPaid($statement, $accountant);

        expect(
            AuditEvent::where('event_type', AuditEventType::PayoutStatementPaid->value)->exists()
        )->toBeTrue();
    });
});
