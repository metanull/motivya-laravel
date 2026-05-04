<?php

declare(strict_types=1);

namespace App\Services\Audit;

use Illuminate\Database\Eloquent\Model;

/**
 * Immutable value object linking an audit event to a domain model subject.
 *
 * Use the named constructors to create subjects with the correct relation label.
 */
final readonly class AuditSubject
{
    public function __construct(
        public string $subjectType,
        public int $subjectId,
        public string $relation,
    ) {}

    /**
     * The primary model being audited (e.g. the booking in a booking.created event).
     */
    public static function primary(Model $model): self
    {
        return new self(
            subjectType: $model->getMorphClass(),
            subjectId: (int) $model->getKey(),
            relation: 'primary',
        );
    }

    /**
     * A related model that provides focused lookup access (e.g. the coach on a session).
     */
    public static function related(Model $model, string $relation): self
    {
        return new self(
            subjectType: $model->getMorphClass(),
            subjectId: (int) $model->getKey(),
            relation: $relation,
        );
    }
}
