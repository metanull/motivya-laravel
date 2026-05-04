<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AuditEventSubjectFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditEventSubject extends Model
{
    /** @use HasFactory<AuditEventSubjectFactory> */
    use HasFactory;

    /**
     * Audit event subjects are append-only; no updated_at is needed.
     */
    public const UPDATED_AT = null;

    protected $fillable = [
        'audit_event_id',
        'subject_type',
        'subject_id',
        'relation',
    ];

    /**
     * @return BelongsTo<AuditEvent, $this>
     */
    public function auditEvent(): BelongsTo
    {
        return $this->belongsTo(AuditEvent::class);
    }
}
