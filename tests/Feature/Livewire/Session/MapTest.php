<?php

declare(strict_types=1);

use App\Enums\ActivityType;
use App\Livewire\Session\Index;
use App\Models\SportSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('session map', function () {

    it('passes map markers to the view', function () {
        $athlete = User::factory()->athlete()->create();

        SportSession::factory()->published()->withCoordinates()->create(['title' => 'Mapped Session']);
        SportSession::factory()->published()->create([
            'title' => 'Unmapped Session',
            'latitude' => null,
            'longitude' => null,
        ]);

        Livewire::actingAs($athlete)
            ->test(Index::class)
            ->assertViewHas('markers', fn ($markers) => $markers->pluck('title')->contains('Mapped Session')
                && ! $markers->pluck('title')->contains('Unmapped Session'));
    });

    it('markers contain all required fields', function () {
        $athlete = User::factory()->athlete()->create();
        $coach = User::factory()->coach()->create(['name' => 'Coach Lena']);

        SportSession::factory()->published()->withCoordinates()->create([
            'title' => 'Map Test Session',
            'coach_id' => $coach->id,
        ]);

        Livewire::actingAs($athlete)
            ->test(Index::class)
            ->assertViewHas('markers', function ($markers) {
                $marker = $markers->first();

                return $marker !== null
                    && isset($marker['id'], $marker['title'], $marker['latitude'], $marker['longitude'],
                        $marker['coach'], $marker['date'], $marker['time'], $marker['price'], $marker['url']);
            });
    });

    it('map markers sync with active filters', function () {
        $athlete = User::factory()->athlete()->create();

        SportSession::factory()->published()->withCoordinates()->create([
            'title' => 'Yoga Mapped',
            'activity_type' => ActivityType::Yoga->value,
        ]);
        SportSession::factory()->published()->withCoordinates()->create([
            'title' => 'Running Mapped',
            'activity_type' => ActivityType::Running->value,
        ]);

        Livewire::actingAs($athlete)
            ->test(Index::class)
            ->set('activityType', ActivityType::Yoga->value)
            ->assertViewHas('markers', fn ($markers) => $markers->pluck('title')->contains('Yoga Mapped')
                && ! $markers->pluck('title')->contains('Running Mapped'));
    });

    it('renders the session-map component when markers exist', function () {
        $athlete = User::factory()->athlete()->create();

        SportSession::factory()->published()->withCoordinates()->create(['title' => 'Geo Session']);

        Livewire::actingAs($athlete)
            ->test(Index::class)
            ->assertSeeHtml('session-map');
    });

    it('renders the map container even when no sessions have coordinates', function () {
        $athlete = User::factory()->athlete()->create();

        SportSession::factory()->published()->create([
            'latitude' => null,
            'longitude' => null,
        ]);

        Livewire::actingAs($athlete)
            ->test(Index::class)
            ->assertSeeHtml('id="session-map"')
            ->assertSee(__('sessions.map_no_markers'));
    });

});
