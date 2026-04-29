<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BookingStatus;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    protected $fillable = [
        'sport_session_id',
        'athlete_id',
        'status',
        'stripe_payment_intent_id',
        'stripe_checkout_session_id',
        'amount_paid',
        'cancelled_at',
        'refunded_at',
        'payment_expires_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'amount_paid' => 'integer',
            'cancelled_at' => 'datetime',
            'refunded_at' => 'datetime',
            'payment_expires_at' => 'datetime',
        ];
    }

    public function isPaymentExpired(): bool
    {
        return $this->payment_expires_at !== null
            && $this->payment_expires_at->isPast();
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
    public function athlete(): BelongsTo
    {
        return $this->belongsTo(User::class, 'athlete_id');
    }
}
