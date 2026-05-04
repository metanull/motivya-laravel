<?php

declare(strict_types=1);

namespace App\Services\Audit;

use App\Enums\AuditEventType;
use App\Enums\AuditOperation;
use App\Models\AuditEvent;
use App\Models\AuditEventSubject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Central application-level audit service.
 *
 * A single call to record() writes one AuditEvent row, all AuditEventSubject
 * rows, and one structured log entry to the dedicated audit channel. The
 * service participates in the caller's transaction but never opens its own
 * top-level transaction — callers are responsible for wrapping their business
 * write and the audit record call in the same DB::transaction().
 */
final class AuditService
{
    public function __construct(
        private readonly AuditContextResolver $contextResolver,
        private readonly FieldRedactor $redactor,
    ) {}

    /**
     * Record one domain audit event.
     *
     * @param  list<AuditSubject>  $subjects
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     * @param  array<string, mixed>  $metadata
     *
     * @throws RuntimeException when the DB write or log write fails
     */
    public function record(
        AuditEventType $eventType,
        AuditOperation $operation,
        Model $model,
        array $subjects = [],
        array $oldValues = [],
        array $newValues = [],
        array $metadata = [],
        ?AuditContext $context = null,
    ): AuditEvent {
        $context ??= $this->contextResolver->current();

        $safeOldValues = $this->redactor->redact($oldValues);
        $safeNewValues = $this->redactor->redact($newValues);
        $safeMetadata = $this->redactor->redact($metadata);

        $auditEvent = AuditEvent::create([
            'occurred_at' => now(),
            'event_type' => $eventType->value,
            'operation' => $operation->value,
            'actor_type' => $context->actorType->value,
            'actor_id' => $context->actorId,
            'actor_role' => $context->actorRole?->value,
            'source' => $context->source->value,
            'request_id' => $context->requestId,
            'ip_address' => $context->ipAddress,
            'user_agent' => $context->userAgent,
            'route_name' => $context->routeName,
            'job_uuid' => $context->jobUuid,
            'model_type' => $model->getMorphClass(),
            'model_id' => $model->getKey(),
            'old_values' => $safeOldValues === [] ? null : $safeOldValues,
            'new_values' => $safeNewValues === [] ? null : $safeNewValues,
            'metadata' => array_merge($safeMetadata, $context->metadata) === []
                ? null
                : array_merge($safeMetadata, $context->metadata),
        ]);

        foreach ($subjects as $subject) {
            AuditEventSubject::create([
                'audit_event_id' => $auditEvent->id,
                'subject_type' => $subject->subjectType,
                'subject_id' => $subject->subjectId,
                'relation' => $subject->relation,
            ]);
        }

        Log::channel('audit')->info('domain_audit_event', [
            'audit_event_id' => $auditEvent->id,
            'event_type' => $eventType->value,
            'operation' => $operation->value,
            'actor_type' => $context->actorType->value,
            'actor_id' => $context->actorId,
            'actor_role' => $context->actorRole?->value,
            'source' => $context->source->value,
            'request_id' => $context->requestId,
            'model_type' => $model->getMorphClass(),
            'model_id' => $model->getKey(),
            'subjects' => array_map(fn (AuditSubject $s): array => [
                'type' => $s->subjectType,
                'id' => $s->subjectId,
                'relation' => $s->relation,
            ], $subjects),
            'old_values' => $safeOldValues,
            'new_values' => $safeNewValues,
            'metadata' => array_merge($safeMetadata, $context->metadata),
        ]);

        return $auditEvent;
    }
}
