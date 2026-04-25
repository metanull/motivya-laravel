<?php

declare(strict_types=1);

use App\Livewire\Athlete\Dashboard;
use App\Models\Booking;
use App\Models\SportSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('athlete dashboard', function () {
    it('renders for an athlete', function () {
        $athlete = User::factory()->athlete()->create();

        $this->actingAs($athlete)
            ->get(route('athlete.dashboard'))
            ->assertOk();
    });

    it('does not render for a coach', function () {
        $coach = User::factory()->coach()->create();

        $this->actingAs($coach)
            ->get(route('athlete.dashboard'))
            ->assertForbidden();
    });

    it('does not render for a guest', function () {
        $this->get(route('athlete.dashboard'))
            ->assertRedirect(route('login'));
    });

    it('shows upcoming bookings', function () {
        $athlete = User::factory()->athlete()->create();
        $session = SportSession::factory()->confirmed()->create([
            'title' => 'Morning Yoga',
            'date' => now()->addDays(3),
        ]);
        Booking::factory()->confirmed()->create([
            'athlete_id' => $athlete->id,
            'sport_session_id' => $session->id,
        ]);

        Livewire::actingAs($athlete)
            ->test(Dashboard::class)
            ->assertSee('Morning Yoga');
    });

    it('shows past bookings in past tab', function () {
        $athlete = User::factory()->athlete()->create();
        $session = SportSession::factory()->completed()->create([
            'title' => 'Old Yoga',
            'date' => now()->subDays(5),
        ]);
        Booking::factory()->confirmed()->create([
            'athlete_id' => $athlete->id,
            'sport_session_id' => $session->id,
        ]);

        Livewire::actingAs($athlete)
            ->test(Dashboard::class)
            ->set('tab', 'past')
            ->assertSee('Old Yoga');
    });

    it('does not show other athletes bookings', function () {
        $athlete = User::factory()->athlete()->create();
        $other = User::factory()->athlete()->create();
        $session = SportSession::factory()->confirmed()->create([
            'title' => 'Not My Session',
            'date' => now()->addDays(3),
        ]);
        Booking::factory()->confirmed()->create([
            'athlete_id' => $other->id,
            'sport_session_id' => $session->id,
        ]);

        Livewire::actingAs($athlete)
            ->test(Dashboard::class)
            ->assertDontSee('Not My Session');
    });

    it('shows empty state when no upcoming bookings', function () {
        $athlete = User::factory()->athlete()->create();

        Livewire::actingAs($athlete)
            ->test(Dashboard::class)
            ->assertSee(__('athlete.no_upcoming'));
    });

    it('shows empty state when no past bookings', function () {
        $athlete = User::factory()->athlete()->create();

        Livewire::actingAs($athlete)
            ->test(Dashboard::class)
            ->set('tab', 'past')
            ->assertSee(__('athlete.no_past'));
    });

    it('shows cancelled bookings in past tab', function () {
        $athlete = User::factory()->athlete()->create();
        $session = SportSession::factory()->confirmed()->create([
            'title' => 'Cancelled Yoga',
            'date' => now()->addDays(3),
        ]);
        Booking::factory()->cancelled()->create([
            'athlete_id' => $athlete->id,
            'sport_session_id' => $session->id,
        ]);

        Livewire::actingAs($athlete)
            ->test(Dashboard::class)
            ->set('tab', 'past')
            ->assertSee('Cancelled Yoga');
    });

    it('shows refunded bookings in past tab', function () {
        $athlete = User::factory()->athlete()->create();
        $session = SportSession::factory()->confirmed()->create([
            'title' => 'Refunded Pilates',
            'date' => now()->addDays(3),
        ]);
        Booking::factory()->refunded()->create([
            'athlete_id' => $athlete->id,
            'sport_session_id' => $session->id,
        ]);

        Livewire::actingAs($athlete)
            ->test(Dashboard::class)
            ->set('tab', 'past')
            ->assertSee('Refunded Pilates');
    });

    it('switches between tabs', function () {
        $athlete = User::factory()->athlete()->create();

        $component = Livewire::actingAs($athlete)->test(Dashboard::class);

        expect($component->get('tab'))->toBe('upcoming');

        $component->set('tab', 'past');

        expect($component->get('tab'))->toBe('past');
    });
});
