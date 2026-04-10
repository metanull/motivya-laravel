<?php

declare(strict_types=1);

use App\Livewire\Session\Show;
use App\Models\SportSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('session detail page', function () {
    it('renders correctly for a published session', function () {
        $athlete = User::factory()->athlete()->create();
        $session = SportSession::factory()->published()->create([
            'title' => 'Morning Yoga Flow',
        ]);

        Livewire::actingAs($athlete)
            ->test(Show::class, ['sportSession' => $session])
            ->assertOk()
            ->assertSee('Morning Yoga Flow')
            ->assertSee($session->coach->name)
            ->assertSeeHtml('wa.me');
    });

    it('renders correctly for a confirmed session', function () {
        $athlete = User::factory()->athlete()->create();
        $session = SportSession::factory()->confirmed()->create([
            'title' => 'Boxing Bootcamp',
        ]);

        Livewire::actingAs($athlete)
            ->test(Show::class, ['sportSession' => $session])
            ->assertOk()
            ->assertSee('Boxing Bootcamp');
    });

    it('returns 403 for draft session when accessed by athlete', function () {
        $athlete = User::factory()->athlete()->create();
        $session = SportSession::factory()->draft()->create();

        $this->actingAs($athlete)
            ->get(route('sessions.show', $session))
            ->assertForbidden();
    });

    it('allows the owning coach to view their draft session', function () {
        $coach = User::factory()->coach()->create();
        $session = SportSession::factory()->draft()->create([
            'coach_id' => $coach->id,
            'title' => 'My Draft Session',
        ]);

        Livewire::actingAs($coach)
            ->test(Show::class, ['sportSession' => $session])
            ->assertOk()
            ->assertSee('My Draft Session');
    });

    it('allows admin to view any draft session', function () {
        $admin = User::factory()->admin()->create();
        $session = SportSession::factory()->draft()->create([
            'title' => 'Secret Draft',
        ]);

        Livewire::actingAs($admin)
            ->test(Show::class, ['sportSession' => $session])
            ->assertOk()
            ->assertSee('Secret Draft');
    });

    it('denies another coach from viewing a draft session', function () {
        $otherCoach = User::factory()->coach()->create();
        $session = SportSession::factory()->draft()->create();

        $this->actingAs($otherCoach)
            ->get(route('sessions.show', $session))
            ->assertForbidden();
    });

    it('shows session details correctly', function () {
        $athlete = User::factory()->athlete()->create();
        $session = SportSession::factory()->published()->create([
            'title' => 'Pilates in the Park',
            'location' => 'Parc du Cinquantenaire',
            'postal_code' => '1000',
            'price_per_person' => 1500,
            'max_participants' => 10,
            'current_participants' => 3,
        ]);

        Livewire::actingAs($athlete)
            ->test(Show::class, ['sportSession' => $session])
            ->assertSee('Pilates in the Park')
            ->assertSee('Parc du Cinquantenaire')
            ->assertSee('1000');
    });

    it('shows spots remaining', function () {
        $athlete = User::factory()->athlete()->create();
        $session = SportSession::factory()->published()->create([
            'max_participants' => 10,
            'current_participants' => 7,
        ]);

        Livewire::actingAs($athlete)
            ->test(Show::class, ['sportSession' => $session])
            ->assertViewHas('spotsRemaining', 3);
    });

    it('requires authentication', function () {
        $session = SportSession::factory()->published()->create();

        $this->get(route('sessions.show', $session))
            ->assertRedirect(route('login'));
    });
});
