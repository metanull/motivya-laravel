<?php

declare(strict_types=1);

use App\Enums\ActivityType;
use App\Models\SportSession;
use App\Models\User;
use App\Services\SessionQueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('SessionQueryService geolocation search', function () {

    it('returns sessions within the default 2 km radius', function () {
        // Brussels city centre: 50.8503, 4.3517
        // ~1.5 km away from centre
        $nearby = SportSession::factory()->published()->create([
            'title' => 'Nearby Session',
            'latitude' => 50.8503,
            'longitude' => 4.3517,
        ]);

        // ~15 km away (Waterloo area)
        SportSession::factory()->published()->create([
            'title' => 'Far Away Session',
            'latitude' => 50.7168,
            'longitude' => 4.3956,
        ]);

        $service = app(SessionQueryService::class);
        $results = $service->searchByLocation(50.8503, 4.3517);

        $ids = $results->pluck('id')->all();
        expect($ids)->toContain($nearby->id)
            ->not->toContain(
                SportSession::where('title', 'Far Away Session')->first()->id
            );
    });

    it('respects a custom radius', function () {
        // ~3 km from centre
        $mediumDistance = SportSession::factory()->published()->create([
            'title' => 'Medium Distance',
            'latitude' => 50.8230,
            'longitude' => 4.3517,
        ]);

        $service = app(SessionQueryService::class);

        // 2 km radius should NOT include it
        $narrowResults = $service->searchByLocation(50.8503, 4.3517, [], 2.0);
        expect($narrowResults->pluck('id')->all())->not->toContain($mediumDistance->id);

        // 5 km radius should include it
        $wideResults = $service->searchByLocation(50.8503, 4.3517, [], 5.0);
        expect($wideResults->pluck('id')->all())->toContain($mediumDistance->id);
    });

    it('excludes sessions without coordinates from geolocation search', function () {
        SportSession::factory()->published()->create([
            'title' => 'No Coordinates Session',
            'latitude' => null,
            'longitude' => null,
        ]);

        $service = app(SessionQueryService::class);
        $results = $service->searchByLocation(50.8503, 4.3517);

        expect($results->pluck('title')->all())->not->toContain('No Coordinates Session');
    });

    it('search without location filters returns all published sessions', function () {
        SportSession::factory()->published()->create(['title' => 'Brussels Session']);
        SportSession::factory()->published()->create(['title' => 'Antwerp Session']);

        $service = app(SessionQueryService::class);
        $results = $service->search([]);

        $titles = $results->pluck('title')->all();
        expect($titles)->toContain('Brussels Session')
            ->toContain('Antwerp Session');
    });

    it('returns map markers only for sessions with coordinates', function () {
        SportSession::factory()->published()->withCoordinates()->create(['title' => 'Geo Session']);
        SportSession::factory()->published()->create([
            'title' => 'No-Geo Session',
            'latitude' => null,
            'longitude' => null,
        ]);

        $service = app(SessionQueryService::class);
        $markers = $service->mapMarkers();

        expect($markers->pluck('title')->all())->toContain('Geo Session')
            ->not->toContain('No-Geo Session');
    });

    it('map markers include required fields', function () {
        $coach = User::factory()->coach()->create(['name' => 'Coach Test']);
        SportSession::factory()->published()->withCoordinates()->create([
            'title' => 'Marker Test Session',
            'coach_id' => $coach->id,
        ]);

        $service = app(SessionQueryService::class);
        $markers = $service->mapMarkers();

        expect($markers)->toHaveCount(1);
        $marker = $markers->first();
        expect($marker)->toHaveKeys(['id', 'title', 'latitude', 'longitude', 'coach', 'date', 'time', 'price', 'url']);
        expect($marker['coach'])->toBe('Coach Test');
        expect($marker['title'])->toBe('Marker Test Session');
    });

    it('applies filters in combination with geolocation search', function () {
        SportSession::factory()->published()->create([
            'title' => 'Nearby Yoga',
            'latitude' => 50.8503,
            'longitude' => 4.3517,
            'activity_type' => ActivityType::Yoga->value,
        ]);
        SportSession::factory()->published()->create([
            'title' => 'Nearby Running',
            'latitude' => 50.8503,
            'longitude' => 4.3517,
            'activity_type' => ActivityType::Running->value,
        ]);

        $service = app(SessionQueryService::class);
        $results = $service->searchByLocation(50.8503, 4.3517, ['activity_type' => ActivityType::Yoga->value]);

        $titles = $results->pluck('title')->all();
        expect($titles)->toContain('Nearby Yoga')
            ->not->toContain('Nearby Running');
    });

    it('mapMarkers with coordinates excludes sessions outside radius', function () {
        // Right at Brussels centre — within any reasonable radius.
        SportSession::factory()->published()->create([
            'title' => 'Near Session',
            'latitude' => 50.8503,
            'longitude' => 4.3517,
        ]);
        // Antwerp area — ~45 km from Brussels.
        SportSession::factory()->published()->create([
            'title' => 'Far Session',
            'latitude' => 51.2194,
            'longitude' => 4.4025,
        ]);

        $service = app(SessionQueryService::class);
        $markers = $service->mapMarkers([], 50.8503, 4.3517, 10.0);

        $titles = $markers->pluck('title')->all();
        expect($titles)->toContain('Near Session')
            ->not->toContain('Far Session');
    });

    it('mapMarkers without coordinates returns all sessions that have coordinates', function () {
        SportSession::factory()->published()->create([
            'title' => 'Session A',
            'latitude' => 50.8503,
            'longitude' => 4.3517,
        ]);
        SportSession::factory()->published()->create([
            'title' => 'Session B',
            'latitude' => 51.2194,
            'longitude' => 4.4025,
        ]);

        $service = app(SessionQueryService::class);
        $markers = $service->mapMarkers();

        $titles = $markers->pluck('title')->all();
        expect($titles)->toContain('Session A')
            ->toContain('Session B');
    });

});
