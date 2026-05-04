<?php

declare(strict_types=1);

namespace App\Services\Audit;

use App\Enums\AuditActorType;
use App\Enums\UserRole;

/**
 * Immutable value object representing the actor responsible for an audit event.
 *
 * Built from an AuditContext; separates actor data from full context to keep
 * the AuditService signature readable.
 */
final readonly class AuditActor
{
    public function __construct(
        public AuditActorType $type,
        public ?int $id,
        public ?UserRole $role,
    ) {}

    /**
     * Create an AuditActor from an AuditContext.
     */
    public static function fromContext(AuditContext $context): self
    {
        return new self(
            type: $context->actorType,
            id: $context->actorId,
            role: $context->actorRole,
        );
    }
}
