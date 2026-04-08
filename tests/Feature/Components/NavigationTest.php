<?php

declare(strict_types=1);

use App\Models\CoachProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Role-based navigation links', function () {

    it('shows admin links to admin users', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        $this->actingAs($admin)
            ->get(route('home'))
            ->assertOk()
            ->assertSee(__('common.nav.admin_coach_approval'));
    });

    it('does not show admin links to athletes', function () {
        $athlete = User::factory()->athlete()->create();

        $this->actingAs($athlete)
            ->get(route('home'))
            ->assertOk()
            ->assertDontSee(__('common.nav.admin_coach_approval'));
    });

    it('shows become-a-coach link to athletes without coach profile', function () {
        $athlete = User::factory()->athlete()->create();

        $this->actingAs($athlete)
            ->get(route('home'))
            ->assertOk()
            ->assertSee(__('common.nav.become_coach'));
    });

    it('does not show become-a-coach link to athletes with pending coach profile', function () {
        $athlete = User::factory()->athlete()->create();
        CoachProfile::factory()->pending()->create(['user_id' => $athlete->id]);

        $this->actingAs($athlete)
            ->get(route('home'))
            ->assertOk()
            ->assertDontSee(__('common.nav.become_coach'));
    });

    it('does not show become-a-coach link to coaches', function () {
        $coach = User::factory()->coach()->create();

        $this->actingAs($coach)
            ->get(route('home'))
            ->assertOk()
            ->assertDontSee(__('common.nav.become_coach'));
    });

    it('shows my-sessions link to coaches', function () {
        $coach = User::factory()->coach()->create();

        $this->actingAs($coach)
            ->get(route('home'))
            ->assertOk()
            ->assertSee(__('common.nav.my_sessions'));
    });

    it('does not show my-sessions link to athletes', function () {
        $athlete = User::factory()->athlete()->create();

        $this->actingAs($athlete)
            ->get(route('home'))
            ->assertOk()
            ->assertDontSee(__('common.nav.my_sessions'));
    });

    it('does not show role-specific links to guests', function () {
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee(__('common.nav.admin_coach_approval'))
            ->assertDontSee(__('common.nav.become_coach'))
            ->assertDontSee(__('common.nav.my_sessions'));
    });
});
