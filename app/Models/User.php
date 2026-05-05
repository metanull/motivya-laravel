<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TwoFactorMethod;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use Billable, HasApiTokens, HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'locale',
        'two_factor_type',
        'suspended_at',
        'suspension_reason',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'suspended_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'two_factor_type' => TwoFactorMethod::class,
        ];
    }

    /**
     * Determine if the user account is suspended.
     */
    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    /**
     * @return HasOne<CoachProfile, $this>
     */
    public function coachProfile(): HasOne
    {
        return $this->hasOne(CoachProfile::class);
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'athlete_id');
    }

    /**
     * @return HasMany<SportSession, $this>
     */
    public function sportSessions(): HasMany
    {
        return $this->hasMany(SportSession::class, 'coach_id');
    }

    /**
     * @return HasMany<CoachPayoutStatement, $this>
     */
    public function payoutStatements(): HasMany
    {
        return $this->hasMany(CoachPayoutStatement::class, 'coach_id');
    }

    /**
     * @return BelongsToMany<SportSession, $this>
     */
    public function favouriteSessions(): BelongsToMany
    {
        return $this->belongsToMany(SportSession::class, 'favourites')->withTimestamps();
    }
}
