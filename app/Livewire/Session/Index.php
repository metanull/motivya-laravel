<?php

declare(strict_types=1);

namespace App\Livewire\Session;

use App\Enums\ActivityType;
use App\Enums\SessionLevel;
use App\Services\PostalCodeCoordinateService;
use App\Services\SessionQueryService;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

final class Index extends Component
{
    use WithPagination;

    /** Valid search radius options in kilometres. */
    private const VALID_RADII = [2, 5, 10, 20, 50];

    #[Url]
    public string $activityType = '';

    #[Url]
    public string $level = '';

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    #[Url]
    public string $timeFrom = '';

    #[Url]
    public string $timeTo = '';

    /** Free-text location query: postal code or municipality name. */
    #[Url]
    public string $locationQuery = '';

    /** Backwards-compatible URL alias for postal-code-only searches. */
    #[Url]
    public string $postalCode = '';

    /** Search radius in kilometres; coerced to a value in VALID_RADII on update. */
    #[Url]
    public int $radiusKm = 10;

    /** Latitude from browser geolocation — sent via JS. */
    public ?float $latitude = null;

    /** Longitude from browser geolocation — sent via JS. */
    public ?float $longitude = null;

    /** Whether browser geolocation is currently active for the search. */
    public bool $useGeolocation = false;

    /** Set to true when the browser denied location access. */
    public bool $geoDenied = false;

    public function updatedActivityType(): void
    {
        $this->resetPage();
    }

    public function updatedLevel(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function updatedTimeFrom(): void
    {
        $this->resetPage();
    }

    public function updatedTimeTo(): void
    {
        $this->resetPage();
    }

    public function updatedPostalCode(): void
    {
        $this->resetPage();
    }

    /** Coerce an invalid radius to the default and reset pagination. */
    public function updatedRadiusKm(): void
    {
        $this->radiusKm = $this->coerceRadius();
        $this->resetPage();
    }

    /** Clear page when the location query changes. */
    public function updatedLocationQuery(): void
    {
        $this->resetPage();
    }

    /** Called from JS when browser geolocation is granted. */
    public function setGeolocation(float $lat, float $lng): void
    {
        $this->latitude = $lat;
        $this->longitude = $lng;
        $this->useGeolocation = true;
        $this->postalCode = '';
        $this->locationQuery = '';
        $this->geoDenied = false;
        $this->resetPage();
    }

    /** Called from JS when the browser denies geolocation access. */
    public function setGeolocationDenied(): void
    {
        $this->geoDenied = true;
    }

    /** Reset geolocation and fall back to manual location search. */
    public function clearGeolocation(): void
    {
        $this->latitude = null;
        $this->longitude = null;
        $this->useGeolocation = false;
        $this->geoDenied = false;
        $this->resetPage();
    }

    /** Reset all filters to their default values. */
    public function resetFilters(): void
    {
        $this->activityType = '';
        $this->level = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->timeFrom = '';
        $this->timeTo = '';
        $this->postalCode = '';
        $this->locationQuery = '';
        $this->radiusKm = 10;
        $this->latitude = null;
        $this->longitude = null;
        $this->useGeolocation = false;
        $this->geoDenied = false;
        $this->resetPage();
    }

    public function render(): View
    {
        $queryService = app(SessionQueryService::class);
        $postalService = app(PostalCodeCoordinateService::class);

        $filters = $this->buildFilters();
        $radius = $this->coerceRadius();

        // Backwards-compatible: if locationQuery is blank but postalCode has a
        // value (old URL bookmark), promote postalCode as the effective query.
        $effectiveQuery = $this->locationQuery !== '' ? $this->locationQuery : $this->postalCode;

        $activeLat = null;
        $activeLng = null;
        $locationInvalid = false;

        if ($this->useGeolocation && $this->latitude !== null && $this->longitude !== null) {
            $activeLat = $this->latitude;
            $activeLng = $this->longitude;
        } elseif ($effectiveQuery !== '') {
            $coords = $postalService->resolveByLocationQuery($effectiveQuery);

            if ($coords !== null) {
                [$activeLat, $activeLng] = $coords;
            } else {
                $locationInvalid = true;
            }
        }

        // True when we have a usable centre point for distance-based queries.
        $hasActiveLocation = $activeLat !== null && $activeLng !== null;

        // True when any location input is present, even if not yet resolved —
        // used by the view to decide whether to show the radius selector.
        $hasAnyLocationInput = $this->useGeolocation || $effectiveQuery !== '';

        if ($locationInvalid) {
            // Unresolvable input — show no results without a DB round-trip.
            $sessions = new LengthAwarePaginator([], 0, SessionQueryService::PER_PAGE);
            $markers = collect();
        } elseif ($activeLat !== null && $activeLng !== null) {
            $sessions = $queryService->searchByLocation($activeLat, $activeLng, $filters, (float) $radius);
            $markers = $queryService->mapMarkers($filters, $activeLat, $activeLng, (float) $radius);
        } else {
            $sessions = $queryService->search($filters);
            $markers = $queryService->mapMarkers($filters);
        }

        return view('livewire.session.index', [
            'sessions' => $sessions,
            'markers' => $markers,
            'activityTypes' => ActivityType::cases(),
            'levels' => SessionLevel::cases(),
            'locationInvalid' => $locationInvalid,
            'radius' => $radius,
            'validRadii' => self::VALID_RADII,
            'effectiveQuery' => $effectiveQuery,
            'hasActiveLocation' => $hasActiveLocation,
            'hasAnyLocationInput' => $hasAnyLocationInput,
        ])->title(__('sessions.discovery_title'));
    }

    /**
     * Build the array of non-location filters passed to the query service.
     * Location is handled via coordinate resolution in render().
     *
     * @return array<string, string>
     */
    private function buildFilters(): array
    {
        $filters = [];

        if ($this->activityType !== '') {
            $filters['activity_type'] = $this->activityType;
        }

        if ($this->level !== '') {
            $filters['level'] = $this->level;
        }

        if ($this->dateFrom !== '') {
            $filters['date_from'] = $this->dateFrom;
        }

        if ($this->dateTo !== '') {
            $filters['date_to'] = $this->dateTo;
        }

        if ($this->timeFrom !== '') {
            $filters['time_from'] = $this->timeFrom;
        }

        if ($this->timeTo !== '') {
            $filters['time_to'] = $this->timeTo;
        }

        return $filters;
    }

    /**
     * Return $radiusKm if it is in VALID_RADII, or 10 as the safe default.
     */
    private function coerceRadius(): int
    {
        return in_array($this->radiusKm, self::VALID_RADII, true) ? $this->radiusKm : 10;
    }
}
