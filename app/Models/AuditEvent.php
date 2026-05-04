<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AuditActorType;
use App\Enums\AuditEventType;
use App\Enums\AuditOperation;
use App\Enums\AuditSource;
use App\Enums\UserRole;
use Database\Factories\AuditEventFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuditEvent extends Model
{
    /** @use HasFactory<AuditEventFactory> */
    use HasFactory;

    use HasUlids;

    /**
     * Audit events are append-only; no updated_at is needed.
     */
    public const UPDATED_AT = null;

    protected $fillable = [
        'occurred_at',
        'event_type',
        'operation',
        'actor_type',
        'actor_id',
        'actor_role',
        'source',
        'request_id',
        'ip_address',
        'user_agent',
        'route_name',
        'job_uuid',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'event_type' => AuditEventType::class,
            'operation' => AuditOperation::class,
            'actor_type' => AuditActorType::class,
            'actor_role' => UserRole::class,
            'source' => AuditSource::class,
            'old_values' => 'array',
            'new_values' => 'array',
            'metadata' => 'array',
        ];
    }

    /**
     * @return HasMany<AuditEventSubject, $this>
     */
    public function subjects(): HasMany
    {
        return $this->hasMany(AuditEventSubject::class);
    }
}
