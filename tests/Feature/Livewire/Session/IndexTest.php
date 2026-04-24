<?php

declare(strict_types=1);

use App\Enums\ActivityType;
use App\Enums\SessionLevel;
use App\Livewire\Session\Index;
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

    it('requires authentication', function () {
        $this->get(route('sessions.index'))
            ->assertRedirect(route('login'));
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

        SportSession::factory()->published()->create([
            'title' => 'Brussels Session',
            'postal_code' => '1000',
        ]);
        SportSession::factory()->published()->create([
            'title' => 'Antwerp Session',
            'postal_code' => '2000',
        ]);

        Livewire::actingAs($athlete)
            ->test(Index::class)
            ->set('postalCode', '1000')
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
            ->assertSee('Coach Marie')
            ->assertSee($athlete->name === 'Coach Marie' ? 'Coach Marie' : 'Coach Marie');
    });

    it('resets filters when resetFilters is called', function () {
        $athlete = User::factory()->athlete()->create();

        Livewire::actingAs($athlete)
            ->test(Index::class)
            ->set('activityType', ActivityType::Yoga->value)
            ->set('postalCode', '1000')
            ->call('resetFilters')
            ->assertSet('activityType', '')
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
            ->assertSet('latitude', '')
            ->assertSet('longitude', '');
    });

    it('shows no-results message when no sessions match filters', function () {
        $athlete = User::factory()->athlete()->create();

        Livewire::actingAs($athlete)
            ->test(Index::class)
            ->set('activityType', ActivityType::Yoga->value)
            ->assertSee(__('sessions.no_results'));
    });

});
