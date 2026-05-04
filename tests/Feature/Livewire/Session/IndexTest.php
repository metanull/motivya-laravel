<?php

declare(strict_types=1);

use App\Enums\ActivityType;
use App\Enums\SessionLevel;
use App\Livewire\Session\Index;
use App\Models\PostalCodeCoordinate;
use App\Models\SportSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('session discovery page', function () {

    it('renders the discovery page for an authenticated athlete', function () {
        $athlete = User::factory()->athlete()->create();
        SportSession::factory()->published()->count(3)->create();

        Livewire::actingAs($athlete)
            ->test(Index::class)
            ->assertOk();
    });

    it('is accessible as a guest', function () {
        SportSession::factory()->published()->count(3)->create();

        $this->get(route('sessions.index'))
            ->assertOk();
    });

    it('renders the discovery page for a guest', function () {
        SportSession::factory()->published()->count(3)->create();

        Livewire::test(Index::class)
            ->assertOk();
    });

    it('shows only published and confirmed sessions', function () {
        $athlete = User::factory()->athlete()->create();

        $published = SportSession::factory()->published()->create(['title' => 'Visible Published']);
        $confirmed = SportSession::factory()->confirmed()->create(['title' => 'Visible Confirmed']);
        SportSession::factory()->draft()->create(['title' => 'Hidden Draft']);
        SportSession::factory()->cancelled()->create(['title' => 'Hidden Cancelled']);
        SportSession::factory()->completed()->create(['title' => 'Hidden Completed']);

        Livewire::actingAs($athlete)
            ->test(Index::class)
            ->assertSee('Visible Published')
            ->assertSee('Visible Confirmed')
            ->assertDontSee('Hidden Draft')
            ->assertDontSee('Hidden Cancelled')
            ->assertDontSee('Hidden Completed');
    });

    it('shows only future sessions', function () {
        $athlete = User::factory()->athlete()->create();

        SportSession::factory()->published()->create([
            'title' => 'Future Session',
            'date' => now()->addDay()->format('Y-m-d'),
        ]);
        SportSession::factory()->published()->create([
            'title' => 'Past Session',
            'date' => now()->subDay()->format('Y-m-d'),
        ]);

        Livewire::actingAs($athlete)
            ->test(Index::class)
            ->assertSee('Future Session')
            ->assertDontSee('Past Session');
    });

    it('filters by activity type', function () {
        $athlete = User::factory()->athlete()->create();

        SportSession::factory()->published()->create([
            'title' => 'Yoga Session',
            'activity_type' => ActivityType::Yoga->value,
        ]);
        SportSession::factory()->published()->create([
            'title' => 'Running Session',
            'activity_type' => ActivityType::Running->value,
        ]);

        Livewire::actingAs($athlete)
            ->test(Index::class)
            ->set('activityType', ActivityType::Yoga->value)
            ->assertSee('Yoga Session')
            ->assertDontSee('Running Session');
    });

    it('filters by level', function () {
        $athlete = User::factory()->athlete()->create();

        SportSession::factory()->published()->create([
            'title' => 'Beginner Session',
            'level' => SessionLevel::Beginner->value,
        ]);
        SportSession::factory()->published()->create([
            'title' => 'Advanced Session',
            'level' => SessionLevel::Advanced->value,
        ]);

        Livewire::actingAs($athlete)
            ->test(Index::class)
            ->set('level', SessionLevel::Beginner->value)
            ->assertSee('Beginner Session')
            ->assertDontSee('Advanced Session');
    });

    it('filters by postal code', function () {
        $athlete = User::factory()->athlete()->create();

        // Seed the coordinate lookup so '1000' resolves to Brussels.
        PostalCodeCoordinate::create([
            'postal_code' => '1000',
            'municipality' => 'Bruxelles/Brussel',
            'latitude' => 50.8503,
            'longitude' => 4.3517,
        ]);

        // Session placed exactly at Brussels — distance = 0, always within radius.
        SportSession::factory()->published()->create([
            'title' => 'Brussels Session',
            'latitude' => 50.8503,
            'longitude' => 4.3517,
        ]);
        // Session in Antwerp area — ~45 km away, well outside the 10 km default radius.
        SportSession::factory()->published()->create([
            'title' => 'Antwerp Session',
            'latitude' => 51.2194,
            'longitude' => 4.4025,
        ]);

        Livewire::actingAs($athlete)
            ->test(Index::class)
            ->set('locationQuery', '1000')
            ->assertSee('Brussels Session')
            ->assertDontSee('Antwerp Session');
    });

    it('filters by date range', function () {
        $athlete = User::factory()->athlete()->create();

        SportSession::factory()->published()->create([
            'title' => 'Tomorrow Session',
            'date' => now()->addDay()->format('Y-m-d'),
        ]);
        SportSession::factory()->published()->create([
            'title' => 'Next Month Session',
            'date' => now()->addMonth()->format('Y-m-d'),
        ]);

        Livewire::actingAs($athlete)
            ->test(Index::class)
            ->set('dateFrom', now()->format('Y-m-d'))
            ->set('dateTo', now()->addDays(3)->format('Y-m-d'))
            ->assertSee('Tomorrow Session')
            ->assertDontSee('Next Month Session');
    });

    it('filters by time range', function () {
        $athlete = User::factory()->athlete()->create();

        SportSession::factory()->published()->create([
            'title' => 'Morning Session',
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
        ]);
        SportSession::factory()->published()->create([
            'title' => 'Evening Session',
            'start_time' => '20:00:00',
            'end_time' => '21:00:00',
        ]);

        Livewire::actingAs($athlete)
            ->test(Index::class)
            ->set('timeFrom', '07:00')
            ->set('timeTo', '10:00')
            ->assertSee('Morning Session')
            ->assertDontSee('Evening Session');
    });

    it('paginates results at 12 per page', function () {
        $athlete = User::factory()->athlete()->create();

        SportSession::factory()->published()->count(14)->create();

        $component = Livewire::actingAs($athlete)->test(Index::class);

        // The sessions collection should have total of 14 but paginate at 12
        $component->assertViewHas('sessions', fn ($sessions) => $sessions->perPage() === 12);
    });

    it('each session card shows required fields', function () {
        $athlete = User::factory()->athlete()->create();
        $coach = User::factory()->coach()->create(['name' => 'Coach Marie']);

        SportSession::factory()->published()->create([
            'title' => 'Park Yoga',
            'coach_id' => $coach->id,
            'price_per_person' => 1500,
            'max_participants' => 10,
            'current_participants' => 2,
            'activity_type' => ActivityType::Yoga->value,
            'level' => SessionLevel::Beginner->value,
        ]);

        Livewire::actingAs($athlete)
            ->test(Index::class)
            ->assertSee('Park Yoga')
            ->assertSee('Coach Marie');
    });

    it('resets filters when resetFilters is called', function () {
        $athlete = User::factory()->athlete()->create();

        Livewire::actingAs($athlete)
            ->test(Index::class)
            ->set('activityType', ActivityType::Yoga->value)
            ->set('locationQuery', '1000')
            ->call('resetFilters')
            ->assertSet('activityType', '')
            ->assertSet('locationQuery', '')
            ->assertSet('postalCode', '');
    });

    it('ignores postal code when geolocation is active', function () {
        $athlete = User::factory()->athlete()->create();

        SportSession::factory()->published()->withCoordinates()->count(3)->create([
            'postal_code' => '9999',
        ]);

        // Simulate geolocation active — postal code should be cleared
        Livewire::actingAs($athlete)
            ->test(Index::class)
            ->call('setGeolocation', 50.85, 4.35)
            ->assertSet('useGeolocation', true)
            ->assertSet('postalCode', '');
    });

    it('clears geolocation correctly', function () {
        $athlete = User::factory()->athlete()->create();

        Livewire::actingAs($athlete)
            ->test(Index::class)
            ->call('setGeolocation', 50.85, 4.35)
            ->call('clearGeolocation')
            ->assertSet('useGeolocation', false)
            ->assertSet('latitude', null)
            ->assertSet('longitude', null);
    });

    it('shows no-results message when no sessions match filters', function () {
        $athlete = User::factory()->athlete()->create();

        Livewire::actingAs($athlete)
            ->test(Index::class)
            ->set('activityType', ActivityType::Yoga->value)
            ->assertSee(__('sessions.no_results'));
    });

    it('radius 2 km excludes session ~3 km away but 10 km includes it', function () {
        $athlete = User::factory()->athlete()->create();

        // ~3 km south of Brussels centre (50.8503, 4.3517).
        SportSession::factory()->published()->create([
            'title' => 'Medium Distance Session',
            'latitude' => 50.8230,
            'longitude' => 4.3517,
        ]);

        $component = Livewire::actingAs($athlete)
            ->test(Index::class)
            ->call('setGeolocation', 50.8503, 4.3517)
            ->set('radiusKm', 2);

        $component->assertDontSee('Medium Distance Session');

        $component
            ->set('radiusKm', 10)
            ->assertSee('Medium Distance Session');
    });

    it('invalid radius value is coerced to 10 km', function () {
        $athlete = User::factory()->athlete()->create();

        // 99 is not in VALID_RADII — updatedRadiusKm() should coerce it to 10.
        Livewire::actingAs($athlete)
            ->test(Index::class)
            ->set('radiusKm', 99)
            ->assertSet('radiusKm', 10);
    });

    it('city name search returns sessions within the resolved radius', function () {
        $athlete = User::factory()->athlete()->create();

        PostalCodeCoordinate::create([
            'postal_code' => '1050',
            'municipality' => 'Ixelles/Elsene',
            'latitude' => 50.8274,
            'longitude' => 4.3773,
        ]);

        SportSession::factory()->published()->create([
            'title' => 'Ixelles Session',
            'latitude' => 50.8274,
            'longitude' => 4.3773,
        ]);

        Livewire::actingAs($athlete)
            ->test(Index::class)
            ->set('locationQuery', 'Ixelles')
            ->assertSee('Ixelles Session');
    });

    it('invalid location shows locationInvalid flag and no sessions', function () {
        $athlete = User::factory()->athlete()->create();

        SportSession::factory()->published()->count(3)->create();

        Livewire::actingAs($athlete)
            ->test(Index::class)
            ->set('locationQuery', 'NonExistentCity99999')
            ->assertViewHas('locationInvalid', true)
            ->assertSee(__('sessions.invalid_location'));
    });

    it('geolocation-denied state sets geoDenied and shows the banner message', function () {
        $athlete = User::factory()->athlete()->create();

        Livewire::actingAs($athlete)
            ->test(Index::class)
            ->call('setGeolocationDenied')
            ->assertSet('geoDenied', true)
            ->assertSee(__('sessions.geo_denied_state'));
    });

    it('geolocation denied does not prevent subsequent location query search', function () {
        $athlete = User::factory()->athlete()->create();

        PostalCodeCoordinate::create([
            'postal_code' => '1000',
            'municipality' => 'Bruxelles/Brussel',
            'latitude' => 50.8503,
            'longitude' => 4.3517,
        ]);

        SportSession::factory()->published()->create([
            'title' => 'Brussels Session',
            'latitude' => 50.8503,
            'longitude' => 4.3517,
        ]);

        Livewire::actingAs($athlete)
            ->test(Index::class)
            ->call('setGeolocationDenied')
            ->set('locationQuery', '1000')
            ->assertSee('Brussels Session');
    });

    it('shows no-results-radius empty state when all sessions are outside the selected radius', function () {
        $athlete = User::factory()->athlete()->create();

        // Antwerp area — ~45 km from Brussels centre.
        SportSession::factory()->published()->create([
            'title' => 'Far Away Session',
            'latitude' => 51.2194,
            'longitude' => 4.4025,
        ]);

        Livewire::actingAs($athlete)
            ->test(Index::class)
            ->call('setGeolocation', 50.8503, 4.3517)
            ->set('radiusKm', 2)
            ->assertSee(__('sessions.no_results_radius', ['km' => 2]));
    });

});
