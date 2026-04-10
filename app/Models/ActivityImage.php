<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ActivityType;
use Database\Factories\ActivityImageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityImage extends Model
{
    /** @use HasFactory<ActivityImageFactory> */
    use HasFactory;

    protected $fillable = [
        'activity_type',
        'path',
        'alt_text',
        'uploaded_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activity_type' => ActivityType::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
