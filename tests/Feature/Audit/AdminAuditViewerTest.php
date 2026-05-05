<?php

declare(strict_types=1);

use App\Enums\AuditActorType;
use App\Enums\AuditEventType;
use App\Enums\AuditOperation;
use App\Enums\AuditSource;
use App\Livewire\Admin\AuditEvents\Index;
use App\Livewire\Admin\AuditEvents\Show;
use App\Models\AuditEvent;
use App\Models\AuditEventSubject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('Admin — Audit Viewer', function () {

    // ── Access control ────────────────────────────────────────────────────

    it('renders index for admin users', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->assertOk()
            ->assertSee(__('admin.audit_events_heading'));
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

    it('index is accessible via route admin.audit-events.index', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        $this->actingAs($admin)
            ->get(route('admin.audit-events.index'))
            ->assertOk();
    });

    // ── Mutation safety: no create/edit/delete actions rendered ──────────

    it('does not render a create button on index', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->assertDontSee('Create')
            ->assertDontSee('New audit');
    });

    it('does not expose any write methods on index component', function () {
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

    it('does not expose any write methods on show component', function () {
        $methods = get_class_methods(Show::class);

        expect($methods)->not->toContain('create')
            ->not->toContain('store')
            ->not->toContain('update')
            ->not->toContain('delete')
            ->not->toContain('destroy')
            ->not->toContain('forceDelete')
            ->not->toContain('restore');
    });

    // ── Filter tests ──────────────────────────────────────────────────────

    it('filters by event type', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        AuditEvent::factory()->create([
            'event_type' => AuditEventType::SessionCreated->value,
            'request_id' => 'SESS0001-session-event-unique-id',
        ]);
        AuditEvent::factory()->create([
            'event_type' => AuditEventType::BookingCreated->value,
            'request_id' => 'BOOK0002-booking-event-unique-id',
        ]);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->set('eventType', AuditEventType::SessionCreated->value)
            ->assertSee('SESS0001')        // request_id prefix in table row
            ->assertDontSee('BOOK0002');   // excluded booking row must not appear
    });

    it('filters by actor type', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        AuditEvent::factory()->create([
            'actor_type' => AuditActorType::User->value,
            'request_id' => 'USER0001-user-actor-unique-id-xx',
        ]);
        AuditEvent::factory()->create([
            'actor_type' => AuditActorType::Stripe->value,
            'request_id' => 'STRP0002-stripe-actor-unique-idz',
        ]);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->set('actorType', AuditActorType::Stripe->value)
            ->assertSee('STRP0002')
            ->assertDontSee('USER0001');
    });

    it('filters by source', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        AuditEvent::factory()->create([
            'source' => AuditSource::Web->value,
            'request_id' => 'WEBX0001-web-source-event-uniq-x',
        ]);
        AuditEvent::factory()->create([
            'source' => AuditSource::Webhook->value,
            'request_id' => 'WBHK0002-webhook-source-event-x',
        ]);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->set('source', AuditSource::Webhook->value)
            ->assertSee('WBHK0002')
            ->assertDontSee('WEBX0001');
    });

    it('filters by request id', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $targetUuid = 'aaaabbbb-cccc-dddd-eeee-111122223333';

        AuditEvent::factory()->create([
            'request_id' => $targetUuid,
        ]);
        AuditEvent::factory()->create([
            'request_id' => 'zzzzffff-0000-1111-2222-999988887777',
        ]);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->set('requestId', $targetUuid)
            ->assertSee('aaaabbbb')    // first 8 chars visible in table
            ->assertDontSee('zzzzffff');
    });

    it('filters by primary model type', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        AuditEvent::factory()->create([
            'model_type' => 'App\\Models\\SportSession',
            'request_id' => 'MODL0001-sport-session-model-xxx',
        ]);
        AuditEvent::factory()->create([
            'model_type' => 'App\\Models\\Booking',
            'request_id' => 'MODL0002-booking-model-type-xxxx',
        ]);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->set('modelType', 'SportSession')
            ->assertSee('MODL0001')
            ->assertDontSee('MODL0002');
    });

    it('filters by subject type via subjects relation', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        $eventWithSubject = AuditEvent::factory()->create([
            'request_id' => 'SUBJ0001-with-subject-type-xxxxx',
        ]);
        AuditEventSubject::factory()->create([
            'audit_event_id' => $eventWithSubject->id,
            'subject_type' => 'App\\Models\\Booking',
            'subject_id' => '42',
            'relation' => 'booking',
        ]);

        AuditEvent::factory()->create([
            'request_id' => 'SUBJ0002-without-subject-xxxxxxx',
        ]);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->set('subjectType', 'Booking')
            ->assertSee('SUBJ0001')
            ->assertDontSee('SUBJ0002');
    });

    it('resets filters', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        AuditEvent::factory()->create(['event_type' => AuditEventType::SessionCreated->value]);

        Livewire::actingAs($admin)
            ->test(Index::class)
            ->set('eventType', AuditEventType::BookingCreated->value)
            ->call('resetFilters')
            ->assertSet('eventType', '');
    });

    // ── Detail page ───────────────────────────────────────────────────────

    it('renders show page for admin with all scalar fields', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        $event = AuditEvent::factory()->create([
            'event_type' => AuditEventType::SessionCreated->value,
            'operation' => AuditOperation::Create->value,
            'actor_type' => AuditActorType::User->value,
            'source' => AuditSource::Web->value,
            'old_values' => ['status' => 'draft'],
            'new_values' => ['status' => 'published'],
            'metadata' => ['reason' => 'test'],
        ]);

        Livewire::actingAs($admin)
            ->test(Show::class, ['auditEvent' => $event])
            ->assertOk()
            ->assertSee($event->event_type->value)
            ->assertSee($event->operation->value)
            ->assertSee($event->actor_type->value)
            ->assertSee($event->source->value);
    });

    it('show page renders old_values and new_values as JSON', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        $event = AuditEvent::factory()->create([
            'old_values' => ['status' => 'draft'],
            'new_values' => ['status' => 'published'],
        ]);

        Livewire::actingAs($admin)
            ->test(Show::class, ['auditEvent' => $event])
            ->assertSee('"draft"')
            ->assertSee('"published"');
    });

    it('show page renders metadata as JSON', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        $event = AuditEvent::factory()->create([
            'metadata' => ['reason' => 'admin-test-reason'],
        ]);

        Livewire::actingAs($admin)
            ->test(Show::class, ['auditEvent' => $event])
            ->assertSee('admin-test-reason');
    });

    it('show page renders subjects grouped by relation', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        $event = AuditEvent::factory()->create(['event_type' => AuditEventType::BookingCreated->value]);
        AuditEventSubject::factory()->create([
            'audit_event_id' => $event->id,
            'subject_type' => 'App\\Models\\Booking',
            'subject_id' => '99',
            'relation' => 'booking',
        ]);
        AuditEventSubject::factory()->create([
            'audit_event_id' => $event->id,
            'subject_type' => 'App\\Models\\SportSession',
            'subject_id' => '10',
            'relation' => 'session',
        ]);

        Livewire::actingAs($admin)
            ->test(Show::class, ['auditEvent' => $event])
            ->assertSee('booking')
            ->assertSee('session')
            ->assertSee('App\\Models\\Booking')
            ->assertSee('App\\Models\\SportSession');
    });

    it('show is forbidden for athletes', function () {
        $athlete = User::factory()->athlete()->create();
        $event = AuditEvent::factory()->create();

        Livewire::actingAs($athlete)
            ->test(Show::class, ['auditEvent' => $event])
            ->assertForbidden();
    });

    it('show is accessible via route admin.audit-events.show', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $event = AuditEvent::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.audit-events.show', $event))
            ->assertOk();
    });

    // ── Focused subject query ─────────────────────────────────────────────

    it('supports focused subject lookup via query params subject_type and subject_id', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        $targetEvent = AuditEvent::factory()->create([
            'request_id' => 'FOCS0001-subject-lookup-unique-x',
        ]);
        AuditEventSubject::factory()->create([
            'audit_event_id' => $targetEvent->id,
            'subject_type' => 'App\\Models\\Booking',
            'subject_id' => '55',
            'relation' => 'booking',
        ]);

        AuditEvent::factory()->create([
            'request_id' => 'FOCS0002-no-subject-unrelated-xx',
        ]);

        Livewire::actingAs($admin)
            ->test(Index::class, ['subjectType' => 'Booking', 'subjectId' => '55'])
            ->assertSee('FOCS0001')
            ->assertDontSee('FOCS0002');
    });

});
