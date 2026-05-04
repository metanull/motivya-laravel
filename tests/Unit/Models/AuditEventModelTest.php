<?php

declare(strict_types=1);

use App\Enums\AuditActorType;
use App\Enums\AuditEventType;
use App\Enums\AuditOperation;
use App\Enums\AuditSource;
use App\Models\AuditEvent;
use App\Models\AuditEventSubject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

describe('AuditEvent model', function () {

    describe('creation and persistence', function () {

        it('can be created and retrieved from the database', function () {
            $event = AuditEvent::factory()->create([
                'event_type' => AuditEventType::SessionCreated->value,
                'operation' => AuditOperation::Create->value,
                'source' => AuditSource::Web->value,
            ]);

            $this->assertDatabaseHas('audit_events', ['id' => $event->id]);
            expect($event->id)->toBeString();
        });

        it('uses a ULID as the primary key', function () {
            $event = AuditEvent::factory()->create();

            // ULIDs are 26-character alphanumeric strings
            expect($event->id)->toBeString()->toHaveLength(26);
            expect($event->getIncrementing())->toBeFalse();
            expect($event->getKeyType())->toBe('string');
        });

    });

    describe('casts', function () {

        it('casts occurred_at to a Carbon datetime', function () {
            $event = AuditEvent::factory()->create(['occurred_at' => now()]);

            expect($event->occurred_at)->toBeInstanceOf(Carbon::class);
        });

        it('casts event_type to AuditEventType enum', function () {
            $event = AuditEvent::factory()->create([
                'event_type' => AuditEventType::BookingCreated->value,
            ]);

            expect($event->event_type)->toBeInstanceOf(AuditEventType::class);
            expect($event->event_type)->toBe(AuditEventType::BookingCreated);
        });

        it('casts operation to AuditOperation enum', function () {
            $event = AuditEvent::factory()->create([
                'operation' => AuditOperation::StateChange->value,
            ]);

            expect($event->operation)->toBeInstanceOf(AuditOperation::class);
            expect($event->operation)->toBe(AuditOperation::StateChange);
        });

        it('casts actor_type to AuditActorType enum', function () {
            $event = AuditEvent::factory()->create([
                'actor_type' => AuditActorType::User->value,
            ]);

            expect($event->actor_type)->toBeInstanceOf(AuditActorType::class);
            expect($event->actor_type)->toBe(AuditActorType::User);
        });

        it('casts source to AuditSource enum', function () {
            $event = AuditEvent::factory()->create([
                'source' => AuditSource::Queue->value,
            ]);

            expect($event->source)->toBeInstanceOf(AuditSource::class);
            expect($event->source)->toBe(AuditSource::Queue);
        });

        it('casts old_values to array', function () {
            $event = AuditEvent::factory()->create([
                'old_values' => ['status' => 'draft'],
            ]);

            expect($event->old_values)->toBeArray();
            expect($event->old_values['status'])->toBe('draft');
        });

        it('casts new_values to array', function () {
            $event = AuditEvent::factory()->create([
                'new_values' => ['status' => 'published'],
            ]);

            expect($event->new_values)->toBeArray();
            expect($event->new_values['status'])->toBe('published');
        });

        it('casts metadata to array', function () {
            $event = AuditEvent::factory()->create([
                'metadata' => ['key' => 'value'],
            ]);

            expect($event->metadata)->toBeArray();
            expect($event->metadata['key'])->toBe('value');
        });

    });

    describe('relationships', function () {

        it('has many subjects', function () {
            $event = AuditEvent::factory()->create();
            AuditEventSubject::factory()->count(2)->create([
                'audit_event_id' => $event->id,
            ]);

            expect($event->subjects)->toHaveCount(2);
            expect($event->subjects->first())->toBeInstanceOf(AuditEventSubject::class);
        });

        it('returns an empty collection when there are no subjects', function () {
            $event = AuditEvent::factory()->create();

            expect($event->subjects)->toBeEmpty();
        });

    });

});

describe('AuditEventSubject model', function () {

    it('can be created and related to an AuditEvent', function () {
        $event = AuditEvent::factory()->create();
        $subject = AuditEventSubject::factory()->create([
            'audit_event_id' => $event->id,
            'subject_type' => 'App\\Models\\SportSession',
            'subject_id' => 42,
            'relation' => 'primary',
        ]);

        $this->assertDatabaseHas('audit_event_subjects', [
            'audit_event_id' => $event->id,
            'subject_type' => 'App\\Models\\SportSession',
            'subject_id' => 42,
            'relation' => 'primary',
        ]);

        expect($subject->auditEvent)->toBeInstanceOf(AuditEvent::class);
        expect($subject->auditEvent->id)->toBe($event->id);
    });

    it('belongs to an AuditEvent', function () {
        $subject = AuditEventSubject::factory()->create();

        expect($subject->auditEvent)->toBeInstanceOf(AuditEvent::class);
    });

});
