<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CoachProfileStatus;
use Database\Factories\CoachProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoachProfile extends Model
{
    /** @use HasFactory<CoachProfileFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        'specialties',
        'bio',
        'experience_level',
        'postal_code',
        'country',
        'formatted_address',
        'street_address',
        'locality',
        'latitude',
        'longitude',
        'geocoding_provider',
        'geocoding_place_id',
        'geocoded_at',
        'geocoding_payload',
        'enterprise_number',
        'is_vat_subject',
        'stripe_account_id',
        'stripe_onboarding_complete',
        'verified_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CoachProfileStatus::class,
            'specialties' => 'array',
            'is_vat_subject' => 'boolean',
            'stripe_onboarding_complete' => 'boolean',
            'verified_at' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'geocoded_at' => 'datetime',
            'geocoding_payload' => 'array',
        ];
    }

    /**
     * Whether this coach profile is fully ready to accept bookings via Stripe.
     */
    public function isStripeReady(): bool
    {
        return is_string($this->stripe_account_id)
            && $this->stripe_account_id !== ''
            && $this->stripe_onboarding_complete;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
