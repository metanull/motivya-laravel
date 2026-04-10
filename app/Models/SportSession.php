<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ActivityType;
use App\Enums\SessionLevel;
use App\Enums\SessionStatus;
use Database\Factories\SportSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SportSession extends Model
{
    /** @use HasFactory<SportSessionFactory> */
    use HasFactory;

    protected $fillable = [
        'coach_id',
        'activity_type',
        'level',
        'title',
        'description',
        'location',
        'postal_code',
        'latitude',
        'longitude',
        'date',
        'start_time',
        'end_time',
        'price_per_person',
        'min_participants',
        'max_participants',
        'current_participants',
        'status',
        'cover_image_id',
        'recurrence_group_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activity_type' => ActivityType::class,
            'level' => SessionLevel::class,
            'status' => SessionStatus::class,
            'date' => 'date',
            'price_per_person' => 'integer',
            'min_participants' => 'integer',
            'max_participants' => 'integer',
            'current_participants' => 'integer',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'cover_image_id' => 'integer',
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
     * @return BelongsTo<ActivityImage, $this>
     */
    public function coverImage(): BelongsTo
    {
        return $this->belongsTo(ActivityImage::class, 'cover_image_id');
    }
}
