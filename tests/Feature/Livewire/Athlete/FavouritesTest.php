<?php

declare(strict_types=1);

use App\Livewire\Athlete\Favourites;
use App\Livewire\Session\Show as SessionShow;
use App\Models\SportSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('athlete favourites', function () {
    it('renders for an athlete', function () {
        $athlete = User::factory()->athlete()->create();

        $this->actingAs($athlete)
            ->get(route('athlete.favourites'))
            ->assertOk();
    });

    it('does not render for a coach', function () {
        $coach = User::factory()->coach()->create();

        $this->actingAs($coach)
            ->get(route('athlete.favourites'))
            ->assertForbidden();
    });

    it('does not render for a guest', function () {
        $this->get(route('athlete.favourites'))
            ->assertRedirect(route('login'));
    });

    it('shows favourite sessions', function () {
        $athlete = User::factory()->athlete()->create();
        $session = SportSession::factory()->published()->create(['title' => 'My Fav Yoga']);
        $athlete->favouriteSessions()->attach($session->id);

        Livewire::actingAs($athlete)
            ->test(Favourites::class)
            ->assertSee('My Fav Yoga');
    });

    it('shows empty state when no favourites', function () {
        $athlete = User::factory()->athlete()->create();

        Livewire::actingAs($athlete)
            ->test(Favourites::class)
            ->assertSee(__('athlete.no_favourites'));
    });

    it('does not show other athletes favourites', function () {
        $athlete = User::factory()->athlete()->create();
        $other = User::factory()->athlete()->create();
        $session = SportSession::factory()->published()->create(['title' => 'Not Mine']);
        $other->favouriteSessions()->attach($session->id);

        Livewire::actingAs($athlete)
            ->test(Favourites::class)
            ->assertDontSee('Not Mine');
    });

    it('can toggle a favourite on from the session show page', function () {
        $athlete = User::factory()->athlete()->create();
        $session = SportSession::factory()->published()->create();

        expect($athlete->favouriteSessions()->where('sport_session_id', $session->id)->exists())->toBeFalse();

        Livewire::actingAs($athlete)
            ->test(SessionShow::class, ['sportSession' => $session])
            ->call('toggleFavourite');

        expect($athlete->favouriteSessions()->where('sport_session_id', $session->id)->exists())->toBeTrue();
    });

    it('can toggle a favourite off from the session show page', function () {
        $athlete = User::factory()->athlete()->create();
        $session = SportSession::factory()->published()->create();
        $athlete->favouriteSessions()->attach($session->id);

        Livewire::actingAs($athlete)
            ->test(SessionShow::class, ['sportSession' => $session])
            ->call('toggleFavourite');

        expect($athlete->favouriteSessions()->where('sport_session_id', $session->id)->exists())->toBeFalse();
    });

    it('dispatches notify on toggle favourite add', function () {
        $athlete = User::factory()->athlete()->create();
        $session = SportSession::factory()->published()->create();

        Livewire::actingAs($athlete)
            ->test(SessionShow::class, ['sportSession' => $session])
            ->call('toggleFavourite')
            ->assertDispatched('notify');
    });
});
