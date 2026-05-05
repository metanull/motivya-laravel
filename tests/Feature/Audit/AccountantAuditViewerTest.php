<?php

declare(strict_types=1);

use App\Enums\AuditEventType;
use App\Livewire\Accountant\AuditEvents\Index;
use App\Livewire\Accountant\AuditEvents\Show;
use App\Models\AuditEvent;
use App\Models\AuditEventSubject;
use App\Models\User;
use App\Policies\AuditEventPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('Accountant — Audit Viewer', function () {

    // ── Access control ────────────────────────────────────────────────────

    it('renders index for accountant users', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();

        Livewire::actingAs($accountant)
            ->test(Index::class)
            ->assertOk()
            ->assertSee(__('accountant.audit_events_heading'));
    });

    it('index is accessible for admin users (admin has accountant panel access)', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->assertOk();
    });

    it('index is forbidden for athletes', function () {
        $athlete = User::factory()->athlete()->create();

        Livewire::actingAs($athlete)
            ->test(Index::class)
            ->assertForbidden();
    });

    it('index is forbidden for coaches', function () {
        $coach = User::factory()->coach()->create();

        Livewire::actingAs($coach)
            ->test(Index::class)
            ->assertForbidden();
    });

    it('index is accessible via route accountant.audit-events.index', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();

        $this->actingAs($accountant)
            ->get(route('accountant.audit-events.index'))
            ->assertOk();
    });

    // ── Financial-only filter: accountant cannot see user-admin events ────

    it('shows only financial event types to accountant', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();

        // Financial events: should appear in results
        AuditEvent::factory()->create([
            'event_type' => AuditEventType::BookingCreated->value,
            'request_id' => 'ACCT0001-financial-booking-xxxxx',
        ]);
        AuditEvent::factory()->create([
            'event_type' => AuditEventType::InvoiceGenerated->value,
            'request_id' => 'ACCT0002-financial-invoice-xxxxx',
        ]);

        // Non-financial events: should NOT appear in results (not even in row data)
        AuditEvent::factory()->create([
            'event_type' => AuditEventType::UserSuspended->value,
            'request_id' => 'ACCT0003-non-financial-suspended',
        ]);
        AuditEvent::factory()->create([
            'event_type' => AuditEventType::CoachApproved->value,
            'request_id' => 'ACCT0004-non-financial-approved-',
        ]);
        AuditEvent::factory()->create([
            'event_type' => AuditEventType::UserRoleChanged->value,
            'request_id' => 'ACCT0005-non-financial-role-chng',
        ]);

        Livewire::actingAs($accountant)
            ->test(Index::class)
            ->assertSee('ACCT0001')
            ->assertSee('ACCT0002')
            ->assertDontSee('ACCT0003')
            ->assertDontSee('ACCT0004')
            ->assertDontSee('ACCT0005');
    });

    it('event type filter only exposes financial event types to accountant', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();

        $component = Livewire::actingAs($accountant)->test(Index::class);
        $options = $component->instance()->eventTypeOptions();
        $financialTypes = AuditEventPolicy::financialTypes();

        $optionValues = array_map(fn ($t) => $t->value, $options);
        $financialValues = array_map(fn ($t) => $t->value, $financialTypes);

        foreach ($financialValues as $value) {
            expect($optionValues)->toContain($value);
        }

        // Non-financial types must not be available
        $nonFinancialTypes = [
            AuditEventType::UserSuspended->value,
            AuditEventType::CoachApproved->value,
            AuditEventType::UserRoleChanged->value,
            AuditEventType::CoachApplicationSubmitted->value,
            AuditEventType::UserCreatedByAdmin->value,
            AuditEventType::SessionCreated->value,
        ];

        foreach ($nonFinancialTypes as $value) {
            expect($optionValues)->not->toContain($value);
        }
    });

    // ── Mutation safety: no create/edit/delete actions ────────────────────

    it('does not render a create button on index', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();

        Livewire::actingAs($accountant)
            ->test(Index::class)
            ->assertDontSee('Create')
            ->assertDontSee('New audit');
    });

    it('does not expose write methods on index component', function () {
        $methods = get_class_methods(Index::class);

        expect($methods)->not->toContain('create')
            ->not->toContain('store')
            ->not->toContain('update')
            ->not->toContain('delete')
            ->not->toContain('destroy')
            ->not->toContain('forceDelete')
            ->not->toContain('restore')
            ->not->toContain('bulkDelete');
    });

    // ── Show (detail) page ────────────────────────────────────────────────

    it('accountant can view a financial audit event detail', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();

        $event = AuditEvent::factory()->create([
            'event_type' => AuditEventType::InvoiceGenerated->value,
            'old_values' => null,
            'new_values' => ['invoice_number' => 'INV-001'],
            'metadata' => null,
        ]);

        Livewire::actingAs($accountant)
            ->test(Show::class, ['auditEvent' => $event])
            ->assertOk()
            ->assertSee($event->event_type->value);
    });

    it('accountant cannot view a non-financial audit event detail', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();

        $event = AuditEvent::factory()->create([
            'event_type' => AuditEventType::UserSuspended->value,
        ]);

        Livewire::actingAs($accountant)
            ->test(Show::class, ['auditEvent' => $event])
            ->assertForbidden();
    });

    it('show renders subjects grouped by relation', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();

        $event = AuditEvent::factory()->create([
            'event_type' => AuditEventType::InvoiceGenerated->value,
        ]);
        AuditEventSubject::factory()->create([
            'audit_event_id' => $event->id,
            'subject_type' => 'App\\Models\\Invoice',
            'subject_id' => '7',
            'relation' => 'invoice',
        ]);

        Livewire::actingAs($accountant)
            ->test(Show::class, ['auditEvent' => $event])
            ->assertSee('invoice')
            ->assertSee('App\\Models\\Invoice');
    });

    it('show is forbidden for athletes', function () {
        $athlete = User::factory()->athlete()->create();
        $event = AuditEvent::factory()->create([
            'event_type' => AuditEventType::InvoiceGenerated->value,
        ]);

        Livewire::actingAs($athlete)
            ->test(Show::class, ['auditEvent' => $event])
            ->assertForbidden();
    });

    it('show is accessible via route accountant.audit-events.show for financial event', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $event = AuditEvent::factory()->create([
            'event_type' => AuditEventType::BookingCreated->value,
        ]);

        $this->actingAs($accountant)
            ->get(route('accountant.audit-events.show', $event))
            ->assertOk();
    });

});
