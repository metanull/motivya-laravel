<?php

declare(strict_types=1);

namespace App\Livewire\Session;

use App\Enums\ActivityType;
use App\Enums\SessionLevel;
use App\Services\SessionQueryService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

final class Index extends Component
{
    use WithPagination;

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

    #[Url]
    public string $postalCode = '';

    /** Latitude from browser geolocation — sent via JS */
    public float|string $latitude = '';

    /** Longitude from browser geolocation — sent via JS */
    public float|string $longitude = '';

    /** Whether to use geolocation for the search */
    public bool $useGeolocation = false;

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

    /** Called from JS when browser geolocation is granted. */
    public function setGeolocation(float $lat, float $lng): void
    {
        $this->latitude = $lat;
        $this->longitude = $lng;
        $this->useGeolocation = true;
        $this->postalCode = '';
        $this->resetPage();
    }

    /** Reset geolocation and fall back to postal-code search. */
    public function clearGeolocation(): void
    {
        $this->latitude = '';
        $this->longitude = '';
        $this->useGeolocation = false;
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->activityType = '';
        $this->level = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->timeFrom = '';
        $this->timeTo = '';
        $this->postalCode = '';
        $this->latitude = '';
        $this->longitude = '';
        $this->useGeolocation = false;
        $this->resetPage();
    }

    public function render(): View
    {
        $service = app(SessionQueryService::class);

        $filters = $this->buildFilters();

        $sessions = $this->useGeolocation && is_float($this->latitude) && is_float($this->longitude)
            ? $service->searchByLocation((float) $this->latitude, (float) $this->longitude, $filters)
            : $service->search($filters);

        $markers = $service->mapMarkers($filters);

        return view('livewire.session.index', [
            'sessions' => $sessions,
            'markers' => $markers,
            'activityTypes' => ActivityType::cases(),
            'levels' => SessionLevel::cases(),
        ])->title(__('sessions.discovery_title'));
    }

    /**
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

        if (! $this->useGeolocation && $this->postalCode !== '') {
            $filters['postal_code'] = $this->postalCode;
        }

        return $filters;
    }
}
