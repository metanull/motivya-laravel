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
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
