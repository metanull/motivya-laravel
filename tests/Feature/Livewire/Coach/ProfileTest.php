<?php

declare(strict_types=1);

use App\Livewire\Coach\Profile;
use App\Models\CoachProfile;
use App\Models\SportSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('coach public profile', function () {
    it('renders for a coach user', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->create(['user_id' => $coach->id]);

        $this->get(route('coaches.show', $coach))
            ->assertOk();
    });

    it('returns 404 for a non-coach user', function () {
        $athlete = User::factory()->athlete()->create();

        $this->get(route('coaches.show', $athlete))
            ->assertNotFound();
    });

    it('displays coach name and bio', function () {
        $coach = User::factory()->coach()->create(['name' => 'Coach Alice']);
        CoachProfile::factory()->approved()->create([
            'user_id' => $coach->id,
            'bio' => 'Experienced fitness coach.',
        ]);

        Livewire::test(Profile::class, ['user' => $coach])
            ->assertSee('Coach Alice')
            ->assertSee('Experienced fitness coach.');
    });

    it('shows verified badge for approved coaches', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->create(['user_id' => $coach->id]);

        Livewire::test(Profile::class, ['user' => $coach])
            ->assertSee(__('coach.verified_badge'));
    });

    it('does not show verified badge for pending coaches', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->pending()->create(['user_id' => $coach->id]);

        Livewire::test(Profile::class, ['user' => $coach])
            ->assertDontSee(__('coach.verified_badge'));
    });

    it('shows coach specialties', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->create([
            'user_id' => $coach->id,
            'specialties' => ['yoga', 'fitness'],
        ]);

        Livewire::test(Profile::class, ['user' => $coach])
            ->assertSee(__('coach.specialty_yoga'))
            ->assertSee(__('coach.specialty_fitness'));
    });

    it('shows upcoming published sessions', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->create(['user_id' => $coach->id]);

        SportSession::factory()->published()->create([
            'coach_id' => $coach->id,
            'title' => 'Morning Yoga',
            'date' => now()->addDays(3),
        ]);

        Livewire::test(Profile::class, ['user' => $coach])
            ->assertSee('Morning Yoga');
    });

    it('does not show draft sessions on public profile', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->create(['user_id' => $coach->id]);

        SportSession::factory()->draft()->create([
            'coach_id' => $coach->id,
            'title' => 'Secret Draft',
        ]);

        Livewire::test(Profile::class, ['user' => $coach])
            ->assertDontSee('Secret Draft');
    });

    it('shows empty state when no upcoming sessions', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->create(['user_id' => $coach->id]);

        Livewire::test(Profile::class, ['user' => $coach])
            ->assertSee(__('coach.no_upcoming_public'));
    });
});
