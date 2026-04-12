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
        'amount_paid',
        'cancelled_at',
        'refunded_at',
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
        ];
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
