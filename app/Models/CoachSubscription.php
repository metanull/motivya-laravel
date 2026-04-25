<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SubscriptionPlan;
use Database\Factories\CoachSubscriptionFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class CoachSubscription extends Model
{
    /** @use HasFactory<CoachSubscriptionFactory> */
    use HasFactory;

    protected $fillable = [
        'coach_id',
        'plan',
        'month',
        'revenue_ttc',
        'applied_plan',
        'subscription_fee',
        'commission_rate',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'plan' => SubscriptionPlan::class,
            'applied_plan' => SubscriptionPlan::class,
        ];
    }

    /**
     * Always stores month as a 'Y-m-d' string to guarantee consistent date
     * comparisons across MySQL and SQLite (avoids the 'Y-m-d H:i:s' format
     * that Laravel's built-in `date` cast serialises to via fromDateTime()).
     *
     * @return Attribute<Carbon, string|\DateTimeInterface>
     */
    protected function month(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => Carbon::parse($value),
            set: fn (mixed $value) => Carbon::parse($value)->format('Y-m-d'),
        );
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_id');
    }
}
