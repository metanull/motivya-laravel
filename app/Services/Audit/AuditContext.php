<?php

declare(strict_types=1);

namespace App\Services\Audit;

use App\Enums\AuditActorType;
use App\Enums\AuditSource;
use App\Enums\UserRole;

/**
 * Immutable value object capturing the context of an operation for auditing.
 */
final readonly class AuditContext
{
    public function __construct(
        public string $requestId,
        public AuditSource $source,
        public AuditActorType $actorType,
        public ?int $actorId,
        public ?UserRole $actorRole,
        public ?string $ipAddress,
        public ?string $userAgent,
        public ?string $routeName,
        public ?string $jobUuid,
        /** @var array<string, mixed> */
        public array $metadata,
    ) {}
}
