<?php

declare(strict_types=1);

use App\Enums\SessionStatus;
use App\Livewire\Coach\Dashboard;
use App\Models\SportSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('coach dashboard', function () {
    it('renders for a coach', function () {
        $coach = User::factory()->coach()->create();

        $this->actingAs($coach)
            ->get(route('coach.dashboard'))
            ->assertOk();
    });

    it('does not render for an athlete', function () {
        $athlete = User::factory()->athlete()->create();

        $this->actingAs($athlete)
            ->get(route('coach.dashboard'))
            ->assertForbidden();
    });

    it('shows upcoming published sessions', function () {
        $coach = User::factory()->coach()->create();
        $upcoming = SportSession::factory()->published()->create([
            'coach_id' => $coach->id,
            'title' => 'Upcoming Yoga',
            'date' => now()->addDays(3),
        ]);

        Livewire::actingAs($coach)
            ->test(Dashboard::class)
            ->assertSee('Upcoming Yoga');
    });

    it('shows upcoming confirmed sessions', function () {
        $coach = User::factory()->coach()->create();
        SportSession::factory()->confirmed()->create([
            'coach_id' => $coach->id,
            'title' => 'Confirmed Run',
            'date' => now()->addDays(5),
        ]);

        Livewire::actingAs($coach)
            ->test(Dashboard::class)
            ->assertSee('Confirmed Run');
    });

    it('shows draft sessions in drafts tab', function () {
        $coach = User::factory()->coach()->create();
        SportSession::factory()->draft()->create([
            'coach_id' => $coach->id,
            'title' => 'My Draft Session',
        ]);

        Livewire::actingAs($coach)
            ->test(Dashboard::class)
            ->set('tab', 'drafts')
            ->assertSee('My Draft Session');
    });

    it('shows past completed sessions in past tab', function () {
        $coach = User::factory()->coach()->create();
        SportSession::factory()->completed()->create([
            'coach_id' => $coach->id,
            'title' => 'Old Completed Session',
        ]);

        Livewire::actingAs($coach)
            ->test(Dashboard::class)
            ->set('tab', 'past')
            ->assertSee('Old Completed Session');
    });

    it('shows past cancelled sessions in past tab', function () {
        $coach = User::factory()->coach()->create();
        SportSession::factory()->cancelled()->create([
            'coach_id' => $coach->id,
            'title' => 'Old Cancelled Session',
        ]);

        Livewire::actingAs($coach)
            ->test(Dashboard::class)
            ->set('tab', 'past')
            ->assertSee('Old Cancelled Session');
    });

    it('does not show other coaches sessions', function () {
        $coach = User::factory()->coach()->create();
        $otherCoach = User::factory()->coach()->create();

        SportSession::factory()->published()->create([
            'coach_id' => $otherCoach->id,
            'title' => 'Not My Session',
            'date' => now()->addDays(3),
        ]);

        Livewire::actingAs($coach)
            ->test(Dashboard::class)
            ->assertDontSee('Not My Session');
    });

    it('can publish a draft session', function () {
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->draft()->create([
            'coach_id' => $coach->id,
        ]);

        Livewire::actingAs($coach)
            ->test(Dashboard::class)
            ->call('publishSession', $session->id)
            ->assertDispatched('notify');

        $session->refresh();
        expect($session->status)->toBe(SessionStatus::Published);
    });

    it('can cancel a published session', function () {
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->published()->create([
            'coach_id' => $coach->id,
            'date' => now()->addDays(3),
        ]);

        Livewire::actingAs($coach)
            ->test(Dashboard::class)
            ->call('cancelSession', $session->id)
            ->assertDispatched('notify');

        $session->refresh();
        expect($session->status)->toBe(SessionStatus::Cancelled);
    });

    it('can delete a draft session', function () {
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->draft()->create([
            'coach_id' => $coach->id,
        ]);

        Livewire::actingAs($coach)
            ->test(Dashboard::class)
            ->call('deleteSession', $session->id)
            ->assertDispatched('notify');

        $this->assertDatabaseMissing('sport_sessions', ['id' => $session->id]);
    });

    it('shows empty state for upcoming when no sessions', function () {
        $coach = User::factory()->coach()->create();

        Livewire::actingAs($coach)
            ->test(Dashboard::class)
            ->assertSee(__('coach.no_upcoming'));
    });
});
