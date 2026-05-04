<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RefundAuditStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminRefundAudit extends Model
{
    protected $fillable = [
        'admin_id',
        'booking_id',
        'refund_amount',
        'reason',
        'stripe_refund_id',
        'status',
        'error_message',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'refund_amount' => 'integer',
            'status' => RefundAuditStatus::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
