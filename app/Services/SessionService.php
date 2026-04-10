<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\SessionStatus;
use App\Events\SessionCancelled;
use App\Models\SportSession;
use App\Models\User;
use InvalidArgumentException;

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

    /**
     * Update an existing session.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(SportSession $session, array $data): SportSession
    {
        $session->update([
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
        ]);

        return $session->refresh();
    }

    /**
     * Delete a draft session (hard delete).
     */
    public function delete(SportSession $session): void
    {
        if ($session->status !== SessionStatus::Draft) {
            throw new InvalidArgumentException('Only draft sessions can be deleted.');
        }

        $session->delete();
    }

    /**
     * Cancel a published or confirmed session.
     */
    public function cancel(SportSession $session): void
    {
        if (! in_array($session->status, [SessionStatus::Published, SessionStatus::Confirmed], true)) {
            throw new InvalidArgumentException('Only published or confirmed sessions can be cancelled.');
        }

        $wasConfirmed = $session->status === SessionStatus::Confirmed;

        $session->update(['status' => SessionStatus::Cancelled->value]);

        if ($wasConfirmed) {
            SessionCancelled::dispatch($session);
        }
    }
}
