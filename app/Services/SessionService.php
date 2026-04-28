<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\SessionStatus;
use App\Events\SessionCancelled;
use App\Events\SessionCompleted;
use App\Models\SportSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

final class SessionService
{
    public function __construct(
        private readonly PostalCodeCoordinateService $geoService,
    ) {}

    /**
     * Create a new session in draft status.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(User $coach, array $data): SportSession
    {
        $coords = $this->resolveCoords($data);

        return SportSession::create([
            'coach_id' => $coach->id,
            'activity_type' => $data['activity_type'],
            'level' => $data['level'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'location' => $data['location'],
            'postal_code' => $data['postal_code'],
            'latitude' => $coords['latitude'] ?? null,
            'longitude' => $coords['longitude'] ?? null,
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
     * Create recurring weekly sessions sharing a recurrence group.
     *
     * @param  array<string, mixed>  $data
     * @return Collection<int, SportSession>
     */
    public function createRecurring(User $coach, array $data, int $numberOfWeeks): Collection
    {
        $groupId = Str::uuid()->toString();
        $baseDate = Carbon::parse($data['date']);

        // Resolve coordinates once and reuse for every session in the group.
        $coords = $this->resolveCoords($data);

        $sessions = collect();

        for ($i = 0; $i < $numberOfWeeks; $i++) {
            $sessionDate = $baseDate->copy()->addWeeks($i);

            $session = SportSession::create([
                'coach_id' => $coach->id,
                'activity_type' => $data['activity_type'],
                'level' => $data['level'],
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'location' => $data['location'],
                'postal_code' => $data['postal_code'],
                'latitude' => $coords['latitude'] ?? null,
                'longitude' => $coords['longitude'] ?? null,
                'date' => $sessionDate->format('Y-m-d'),
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'price_per_person' => $data['price_per_person'],
                'min_participants' => $data['min_participants'],
                'max_participants' => $data['max_participants'],
                'cover_image_id' => $data['cover_image_id'] ?? null,
                'status' => SessionStatus::Draft->value,
                'current_participants' => 0,
                'recurrence_group_id' => $groupId,
            ]);

            $sessions->push($session);
        }

        return $sessions;
    }

    /**
     * Update an existing session.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(SportSession $session, array $data): SportSession
    {
        $coords = $this->resolveCoords($data);

        $session->update([
            'activity_type' => $data['activity_type'],
            'level' => $data['level'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'location' => $data['location'],
            'postal_code' => $data['postal_code'],
            'latitude' => $coords['latitude'] ?? null,
            'longitude' => $coords['longitude'] ?? null,
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
     * Update all future sessions in a recurrence group.
     *
     * Only updates sessions with date >= today that are in draft or published status.
     *
     * @param  array<string, mixed>  $data  Fields to update (date/time excluded — each session keeps its own schedule)
     * @return int Number of sessions updated
     */
    public function updateGroup(SportSession $session, array $data): int
    {
        if ($session->recurrence_group_id === null) {
            throw new InvalidArgumentException('Session does not belong to a recurrence group.');
        }

        $coords = $this->resolveCoords($data);

        $updatableFields = [
            'activity_type' => $data['activity_type'],
            'level' => $data['level'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'location' => $data['location'],
            'postal_code' => $data['postal_code'],
            'latitude' => $coords['latitude'] ?? null,
            'longitude' => $coords['longitude'] ?? null,
            'price_per_person' => $data['price_per_person'],
            'min_participants' => $data['min_participants'],
            'max_participants' => $data['max_participants'],
            'cover_image_id' => $data['cover_image_id'] ?? null,
        ];

        return SportSession::where('recurrence_group_id', $session->recurrence_group_id)
            ->where('date', '>=', now()->toDateString())
            ->whereIn('status', [SessionStatus::Draft, SessionStatus::Published])
            ->update($updatableFields);
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

    /**
     * Mark a confirmed session as completed.
     */
    public function complete(SportSession $session): void
    {
        if ($session->status !== SessionStatus::Confirmed) {
            throw new InvalidArgumentException('Only confirmed sessions can be marked as completed.');
        }

        $session->update(['status' => SessionStatus::Completed->value]);

        SessionCompleted::dispatch($session);
    }

    /**
     * Publish a draft session (make it visible to athletes).
     *
     * @throws ValidationException if required fields are missing
     * @throws InvalidArgumentException if session is not in draft status
     */
    public function publish(SportSession $session): void
    {
        if ($session->status !== SessionStatus::Draft) {
            throw new InvalidArgumentException('Only draft sessions can be published.');
        }

        $missing = [];

        if (empty($session->title)) {
            $missing['title'] = [__('validation.required', ['attribute' => __('sessions.title_label')])];
        }
        if (empty($session->location)) {
            $missing['location'] = [__('validation.required', ['attribute' => __('sessions.location_label')])];
        }
        if (empty($session->postal_code)) {
            $missing['postal_code'] = [__('validation.required', ['attribute' => __('sessions.postal_code_label')])];
        }
        if ($session->date === null) {
            $missing['date'] = [__('validation.required', ['attribute' => __('sessions.date_label')])];
        }
        if (empty($session->start_time)) {
            $missing['start_time'] = [__('validation.required', ['attribute' => __('sessions.start_time_label')])];
        }
        if (empty($session->end_time)) {
            $missing['end_time'] = [__('validation.required', ['attribute' => __('sessions.end_time_label')])];
        }
        if ($session->price_per_person <= 0) {
            $missing['price_per_person'] = [__('validation.required', ['attribute' => __('sessions.price_label')])];
        }
        if ($session->min_participants < 1) {
            $missing['min_participants'] = [__('validation.required', ['attribute' => __('sessions.min_participants_label')])];
        }
        if ($session->max_participants < 1) {
            $missing['max_participants'] = [__('validation.required', ['attribute' => __('sessions.max_participants_label')])];
        }

        if ($missing !== []) {
            throw ValidationException::withMessages($missing);
        }

        $session->update(['status' => SessionStatus::Published->value]);
    }

    /**
     * Resolve coordinates for a data array.
     *
     * If explicit latitude AND longitude are present in $data, use them directly.
     * Otherwise attempt a lookup via the geo service.
     *
     * @param  array<string, mixed>  $data
     * @return array{latitude: float, longitude: float}|null
     */
    private function resolveCoords(array $data): ?array
    {
        if (
            isset($data['latitude'], $data['longitude'])
            && $data['latitude'] !== null
            && $data['longitude'] !== null
        ) {
            return ['latitude' => (float) $data['latitude'], 'longitude' => (float) $data['longitude']];
        }

        $coords = $this->geoService->resolveCoordinates((string) $data['postal_code']);

        if ($coords === null) {
            return null;
        }

        return ['latitude' => $coords[0], 'longitude' => $coords[1]];
    }
}
