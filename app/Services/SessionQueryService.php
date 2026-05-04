<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ActivityType;
use App\Enums\SessionLevel;
use App\Enums\SessionStatus;
use App\Models\SportSession;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class SessionQueryService
{
    public const PER_PAGE = 12;

    public const DEFAULT_RADIUS_KM = 2.0;

    /**
     * Build a base query limited to discoverable sessions (published / confirmed, future dates).
     *
     * @return Builder<SportSession>
     */
    private function baseQuery(): Builder
    {
        return SportSession::query()
            ->with(['coach', 'coverImage'])
            ->whereIn('status', [SessionStatus::Published->value, SessionStatus::Confirmed->value])
            ->whereDate('date', '>=', now()->toDateString())
            ->orderBy('date')
            ->orderBy('start_time');
    }

    /**
     * Return a paginated list of sessions with optional filters.
     *
     * @param  array{activity_type?: string, level?: string, date_from?: string, date_to?: string, time_from?: string, time_to?: string}  $filters
     */
    public function search(array $filters = []): LengthAwarePaginator
    {
        $query = $this->baseQuery();

        $this->applyFilters($query, $filters);

        return $query->paginate(self::PER_PAGE);
    }

    /**
     * Return a paginated list of sessions within a radius (Haversine formula).
     *
     * @param  array{activity_type?: string, level?: string, date_from?: string, date_to?: string, time_from?: string, time_to?: string}  $filters
     */
    public function searchByLocation(
        float $latitude,
        float $longitude,
        array $filters = [],
        float $radiusKm = self::DEFAULT_RADIUS_KM,
    ): LengthAwarePaginator {
        $query = $this->baseQuery();

        // Haversine formula — extracted to avoid duplication with mapMarkers().
        $haversine = $this->haversineExpression();

        $query->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereRaw("{$haversine} <= CAST(? AS REAL)", [
                // CAST(? AS REAL) forces the bound parameter to a numeric type.
                // Without it, SQLite treats bound float values as TEXT, causing
                // incorrect comparisons (TEXT values are always > REAL in SQLite).
                // MySQL handles this correctly without the cast.
                $latitude,
                $longitude,
                $latitude,
                $radiusKm,
            ])
            ->selectRaw("sport_sessions.*, {$haversine} AS distance_km", [
                $latitude,
                $longitude,
                $latitude,
            ])
            ->orderByRaw("{$haversine}", [
                $latitude,
                $longitude,
                $latitude,
            ]);

        $this->applyFilters($query, $filters);

        return $query->paginate(self::PER_PAGE);
    }

    /**
     * Return all discoverable sessions as a flat collection for map markers,
     * limited to sessions that have coordinates.
     *
     * When latitude/longitude are provided the same Haversine distance filter
     * as {@see searchByLocation()} is applied, keeping map markers consistent
     * with the active search scope.
     *
     * @param  array{activity_type?: string, level?: string, date_from?: string, date_to?: string, time_from?: string, time_to?: string}  $filters
     * @return Collection<int, array{id: int, title: string, latitude: float, longitude: float, coach: string, date: string, time: string, price: int, url: string}>
     */
    public function mapMarkers(
        array $filters = [],
        ?float $latitude = null,
        ?float $longitude = null,
        float $radiusKm = self::DEFAULT_RADIUS_KM,
    ): Collection {
        $query = $this->baseQuery();

        $this->applyFilters($query, $filters);

        $query->whereNotNull('latitude')
            ->whereNotNull('longitude');

        // When a centre point is provided, restrict markers to the same radius
        // used for the session list so map pins always match the visible results.
        if ($latitude !== null && $longitude !== null) {
            $haversine = $this->haversineExpression();
            $query->whereRaw("{$haversine} <= CAST(? AS REAL)", [
                $latitude,
                $longitude,
                $latitude,
                $radiusKm,
            ]);
        }

        return $query
            ->get()
            ->map(fn (SportSession $session) => [
                'id' => $session->id,
                'title' => $session->title,
                'latitude' => (float) $session->latitude,
                'longitude' => (float) $session->longitude,
                'coach' => $session->coach->name,
                'date' => $session->date->translatedFormat('d/m/Y'),
                'time' => Carbon::parse($session->start_time)->format('H:i'),
                'price' => $session->price_per_person,
                'url' => route('sessions.show', $session),
            ]);
    }

    /**
     * Build the Haversine great-circle distance SQL expression.
     *
     * Returns a parameterised SQL fragment that expects three positional
     * bindings in order: latitude, longitude, latitude.  The result is the
     * distance in kilometres between the bound point and each row.
     */
    private function haversineExpression(): string
    {
        return '(6371 * ACOS(
            COS(RADIANS(?)) * COS(RADIANS(latitude)) *
            COS(RADIANS(longitude) - RADIANS(?)) +
            SIN(RADIANS(?)) * SIN(RADIANS(latitude))
        ))';
    }

    /**
     * Apply shared filter criteria to the given query builder.
     *
     * Location filtering (postal code / municipality) is handled upstream via
     * coordinate resolution; it is intentionally absent here.
     *
     * @param  Builder<SportSession>  $query
     * @param  array<string, string>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['activity_type']) && ActivityType::tryFrom($filters['activity_type']) !== null) {
            $query->where('activity_type', $filters['activity_type']);
        }

        if (! empty($filters['level']) && SessionLevel::tryFrom($filters['level']) !== null) {
            $query->where('level', $filters['level']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('date', '<=', $filters['date_to']);
        }

        if (! empty($filters['time_from'])) {
            $query->where('start_time', '>=', $filters['time_from']);
        }

        if (! empty($filters['time_to'])) {
            $query->where('start_time', '<=', $filters['time_to']);
        }
    }
}
