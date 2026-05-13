<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StripeTransfer extends Model
{
    protected $fillable = [
        'stripe_transfer_id',
        'stripe_payment_intent_id',
        'stripe_charge_id',
        'booking_id',
        'sport_session_id',
        'coach_id',
        'destination_account_id',
        'amount',
        'currency',
        'status',
        'stripe_created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'stripe_created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * @return BelongsTo<SportSession, $this>
     */
    public function sportSession(): BelongsTo
    {
        return $this->belongsTo(SportSession::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_id');
    }
}
