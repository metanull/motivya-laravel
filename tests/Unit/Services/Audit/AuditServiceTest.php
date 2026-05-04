<?php

declare(strict_types=1);

use App\Enums\AuditActorType;
use App\Enums\AuditEventType;
use App\Enums\AuditOperation;
use App\Enums\AuditSource;
use App\Enums\UserRole;
use App\Models\AuditEvent;
use App\Models\SportSession;
use App\Models\User;
use App\Services\Audit\AuditContext;
use App\Services\Audit\AuditContextResolver;
use App\Services\Audit\AuditService;
use App\Services\Audit\AuditSubject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

uses(RefreshDatabase::class);

/**
 * Build a deterministic AuditContext for tests so we don't depend on the
 * singleton resolver state.
 */
function testAuditContext(): AuditContext
{
    return new AuditContext(
        requestId: 'test-request-id',
        source: AuditSource::Test,
        actorType: AuditActorType::User,
        actorId: 1,
        actorRole: UserRole::Coach,
        ipAddress: '127.0.0.1',
        userAgent: 'PHPUnit',
        routeName: null,
        jobUuid: null,
        metadata: [],
    );
}

describe('AuditService::record()', function () {

    beforeEach(function () {
        $this->service = app(AuditService::class);
        $this->model = User::factory()->coach()->create();
    });

    describe('database row creation', function () {

        it('creates one audit_events row', function () {
            $this->service->record(
                eventType: AuditEventType::SessionCreated,
                operation: AuditOperation::Create,
                model: $this->model,
                context: testAuditContext(),
            );

            $this->assertDatabaseCount('audit_events', 1);
        });

        it('persists the correct event_type, operation, and source', function () {
            $this->service->record(
                eventType: AuditEventType::BookingCancelled,
                operation: AuditOperation::StateChange,
                model: $this->model,
                context: testAuditContext(),
            );

            $this->assertDatabaseHas('audit_events', [
                'event_type' => AuditEventType::BookingCancelled->value,
                'operation' => AuditOperation::StateChange->value,
                'source' => AuditSource::Test->value,
            ]);
        });

        it('persists actor data from the context', function () {
            $this->service->record(
                eventType: AuditEventType::SessionCreated,
                operation: AuditOperation::Create,
                model: $this->model,
                context: testAuditContext(),
            );

            $this->assertDatabaseHas('audit_events', [
                'actor_type' => AuditActorType::User->value,
                'actor_id' => 1,
                'actor_role' => UserRole::Coach->value,
                'request_id' => 'test-request-id',
            ]);
        });

        it('persists the model type and id', function () {
            $this->service->record(
                eventType: AuditEventType::UserRoleChanged,
                operation: AuditOperation::Update,
                model: $this->model,
                context: testAuditContext(),
            );

            $this->assertDatabaseHas('audit_events', [
                'model_type' => $this->model->getMorphClass(),
                'model_id' => $this->model->getKey(),
            ]);
        });

        it('persists old and new values', function () {
            $this->service->record(
                eventType: AuditEventType::SessionUpdated,
                operation: AuditOperation::Update,
                model: $this->model,
                oldValues: ['status' => 'draft'],
                newValues: ['status' => 'published'],
                context: testAuditContext(),
            );

            $row = AuditEvent::first();
            expect($row->old_values)->toBe(['status' => 'draft']);
            expect($row->new_values)->toBe(['status' => 'published']);
        });

        it('returns the created AuditEvent model', function () {
            $result = $this->service->record(
                eventType: AuditEventType::SessionCreated,
                operation: AuditOperation::Create,
                model: $this->model,
                context: testAuditContext(),
            );

            expect($result)->toBeInstanceOf(AuditEvent::class);
            expect($result->id)->toBeString()->toHaveLength(26);
        });

    });

    describe('subject row creation', function () {

        it('creates audit_event_subjects rows for each given subject', function () {
            $session = SportSession::factory()->create();

            $this->service->record(
                eventType: AuditEventType::SessionCreated,
                operation: AuditOperation::Create,
                model: $session,
                subjects: [
                    AuditSubject::primary($session),
                    AuditSubject::related($this->model, 'coach'),
                ],
                context: testAuditContext(),
            );

            $this->assertDatabaseCount('audit_event_subjects', 2);
        });

        it('creates a primary subject with the correct relation', function () {
            $session = SportSession::factory()->create();

            $this->service->record(
                eventType: AuditEventType::SessionCreated,
                operation: AuditOperation::Create,
                model: $session,
                subjects: [AuditSubject::primary($session)],
                context: testAuditContext(),
            );

            $this->assertDatabaseHas('audit_event_subjects', [
                'subject_type' => $session->getMorphClass(),
                'subject_id' => $session->getKey(),
                'relation' => 'primary',
            ]);
        });

        it('creates no subject rows when subjects list is empty', function () {
            $this->service->record(
                eventType: AuditEventType::SessionCreated,
                operation: AuditOperation::Create,
                model: $this->model,
                subjects: [],
                context: testAuditContext(),
            );

            $this->assertDatabaseCount('audit_event_subjects', 0);
        });

        it('links subject rows to the correct audit_event_id', function () {
            $session = SportSession::factory()->create();

            $auditEvent = $this->service->record(
                eventType: AuditEventType::SessionCreated,
                operation: AuditOperation::Create,
                model: $session,
                subjects: [AuditSubject::primary($session)],
                context: testAuditContext(),
            );

            $this->assertDatabaseHas('audit_event_subjects', [
                'audit_event_id' => $auditEvent->id,
            ]);
        });

    });

    describe('structured log entry', function () {

        it('writes a domain_audit_event log entry to the audit channel', function () {
            $mockLogger = Mockery::mock(LoggerInterface::class);
            $mockLogger->shouldReceive('info')
                ->with('domain_audit_event', Mockery::type('array'))
                ->once();

            Log::shouldReceive('channel')
                ->with('audit')
                ->once()
                ->andReturn($mockLogger);

            $this->service->record(
                eventType: AuditEventType::SessionCreated,
                operation: AuditOperation::Create,
                model: $this->model,
                context: testAuditContext(),
            );
        });

        it('includes the audit_event_id in the log payload', function () {
            $captured = [];

            Log::shouldReceive('channel')
                ->with('audit')
                ->andReturnUsing(function () use (&$captured) {
                    return new class($captured)
                    {
                        public function __construct(private array &$captured) {}

                        public function info(string $message, array $context = []): void
                        {
                            $this->captured = $context;
                        }
                    };
                });

            $this->service->record(
                eventType: AuditEventType::SessionCreated,
                operation: AuditOperation::Create,
                model: $this->model,
                context: testAuditContext(),
            );

            expect(array_key_exists('audit_event_id', $captured))->toBeTrue();
            expect($captured['audit_event_id'])->toBeString();
        });

    });

    describe('transaction participation', function () {

        it('rolls back audit rows when the outer transaction is rolled back', function () {
            try {
                DB::transaction(function () {
                    $this->service->record(
                        eventType: AuditEventType::SessionCreated,
                        operation: AuditOperation::Create,
                        model: $this->model,
                        subjects: [AuditSubject::primary($this->model)],
                        context: testAuditContext(),
                    );

                    throw new RuntimeException('Simulated failure — roll back everything');
                });
            } catch (RuntimeException) {
                // expected
            }

            $this->assertDatabaseCount('audit_events', 0);
            $this->assertDatabaseCount('audit_event_subjects', 0);
        });

        it('commits audit rows when the outer transaction commits', function () {
            DB::transaction(function () {
                $this->service->record(
                    eventType: AuditEventType::SessionCreated,
                    operation: AuditOperation::Create,
                    model: $this->model,
                    context: testAuditContext(),
                );
            });

            $this->assertDatabaseCount('audit_events', 1);
        });

    });

    describe('sensitive field redaction', function () {

        it('redacts password from old_values before persisting', function () {
            $this->service->record(
                eventType: AuditEventType::UserRoleChanged,
                operation: AuditOperation::Update,
                model: $this->model,
                oldValues: ['name' => 'Alice', 'password' => 'secret123'],
                context: testAuditContext(),
            );

            $row = AuditEvent::first();
            expect($row->old_values['password'])->toBe('[REDACTED]');
            expect($row->old_values['name'])->toBe('Alice');
        });

        it('redacts _token fields from new_values before persisting', function () {
            $this->service->record(
                eventType: AuditEventType::UserRoleChanged,
                operation: AuditOperation::Update,
                model: $this->model,
                newValues: ['role' => 'coach', 'remember_token' => 'tok_abc'],
                context: testAuditContext(),
            );

            $row = AuditEvent::first();
            expect($row->new_values['remember_token'])->toBe('[REDACTED]');
            expect($row->new_values['role'])->toBe('coach');
        });

        it('redacts two_factor_secret from new_values', function () {
            $this->service->record(
                eventType: AuditEventType::UserRoleChanged,
                operation: AuditOperation::Update,
                model: $this->model,
                newValues: ['two_factor_secret' => 'JBSWY3DPEHPK3PXP'],
                context: testAuditContext(),
            );

            $row = AuditEvent::first();
            expect($row->new_values['two_factor_secret'])->toBe('[REDACTED]');
        });

        it('redacts oauth_token from old_values', function () {
            $this->service->record(
                eventType: AuditEventType::UserRoleChanged,
                operation: AuditOperation::Update,
                model: $this->model,
                oldValues: ['oauth_token' => 'ya29.abcdef'],
                context: testAuditContext(),
            );

            $row = AuditEvent::first();
            expect($row->old_values['oauth_token'])->toBe('[REDACTED]');
        });

    });

    describe('default context from resolver', function () {

        it('uses the resolver context when no explicit context is passed', function () {
            $resolver = app(AuditContextResolver::class);
            $consoleCtx = $resolver->forConsole('some:command');

            // Inject the context manually into the resolver singleton
            $reflection = new ReflectionProperty(AuditContextResolver::class, 'context');
            $reflection->setValue($resolver, $consoleCtx);

            $this->service->record(
                eventType: AuditEventType::SessionCreated,
                operation: AuditOperation::Create,
                model: $this->model,
            );

            $this->assertDatabaseHas('audit_events', [
                'source' => AuditSource::Console->value,
                'actor_type' => AuditActorType::Console->value,
            ]);

            $resolver->reset();
        });

    });

});
