<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\SessionStatus;
use App\Models\SportSession;
use App\Models\User;

final class SessionService
{
    /**
     * Create a new session in draft status.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(User $coach, array $data): SportSession
    {
        return SportSession::create([
            'coach_id' => $coach->id,
            'activity_type' => $data['activity_type'],
            'level' => $data['level'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'location' => $data['location'],
            'postal_code' => $data['postal_code'],
            'date' => $data['date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'price_per_person' => $data['price_per_person'],
            'min_participants' => $data['min_participants'],
            'max_participants' => $data['max_participants'],
            'cover_image_id' => $data['cover_image_id'] ?? null,
            'status' => SessionStatus::Draft->value,
            'current_participants' => 0,
        ]);
    }
}
