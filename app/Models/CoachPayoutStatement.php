<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CoachPayoutStatementStatus;
use Database\Factories\CoachPayoutStatementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoachPayoutStatement extends Model
{
    /** @use HasFactory<CoachPayoutStatementFactory> */
    use HasFactory;

    protected $fillable = [
        'coach_id',
        'period_month',
        'period_year',
        'status',
        'sessions_count',
        'paid_bookings_count',
        'revenue_ttc',
        'revenue_htva',
        'vat_amount',
        'payment_fees',
        'subscription_tier',
        'commission_rate',
        'commission_amount',
        'coach_payout',
        'is_vat_subject',
        'block_reason',
        'invoice_submitted_at',
        'approved_at',
        'paid_at',
        'approved_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CoachPayoutStatementStatus::class,
            'period_month' => 'integer',
            'period_year' => 'integer',
            'sessions_count' => 'integer',
            'paid_bookings_count' => 'integer',
            'revenue_ttc' => 'integer',
            'revenue_htva' => 'integer',
            'vat_amount' => 'integer',
            'payment_fees' => 'integer',
            'commission_rate' => 'integer',
            'commission_amount' => 'integer',
            'coach_payout' => 'integer',
            'is_vat_subject' => 'boolean',
            'invoice_submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
