<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentAnomalyType;
use Database\Factories\PaymentAnomalyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PaymentAnomaly extends Model
{
    /** @use HasFactory<PaymentAnomalyFactory> */
    use HasFactory;

    protected $fillable = [
        'anomaly_type',
        'anomalous_model_type',
        'anomalous_model_id',
        'related_invoice_id',
        'related_booking_id',
        'related_session_id',
        'related_coach_id',
        'related_statement_id',
        'resolution_status',
        'resolution_reason',
        'resolved_by',
        'resolved_at',
        'description',
        'recommended_action',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'anomaly_type' => PaymentAnomalyType::class,
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function anomalousModel(): MorphTo
    {
        return $this->morphTo('anomalous_model');
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'related_invoice_id');
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'related_booking_id');
    }

    /**
     * @return BelongsTo<SportSession, $this>
     */
    public function sportSession(): BelongsTo
    {
        return $this->belongsTo(SportSession::class, 'related_session_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'related_coach_id');
    }

    /**
     * @return BelongsTo<CoachPayoutStatement, $this>
     */
    public function payoutStatement(): BelongsTo
    {
        return $this->belongsTo(CoachPayoutStatement::class, 'related_statement_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
