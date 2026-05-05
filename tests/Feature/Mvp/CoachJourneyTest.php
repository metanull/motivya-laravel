<?php

declare(strict_types=1);

use App\Models\CoachProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('MVP Coach Journey', function () {

    it('can reach the coach dashboard', function () {
        $coach = User::factory()->coach()->create();

        $this->actingAs($coach)
            ->get(route('coach.dashboard'))
            ->assertOk();
    });

    it('can reach the coach profile edit page', function () {
        $coach = User::factory()->coach()->create();

        $this->actingAs($coach)
            ->get(route('coach.profile.edit'))
            ->assertOk();
    });

    it('can reach the session create page', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->create(['user_id' => $coach->id]);

        $this->actingAs($coach)
            ->get(route('coach.sessions.create'))
            ->assertOk();
    });

    it('can reach the payout history page', function () {
        $coach = User::factory()->coach()->create();

        $this->actingAs($coach)
            ->get(route('coach.payout-history'))
            ->assertOk();
    });

    it('can reach the payout statements page', function () {
        $coach = User::factory()->coach()->create();

        $this->actingAs($coach)
            ->get(route('coach.payout-statements.index'))
            ->assertOk();
    });

    it('cannot access the admin dashboard', function () {
        $coach = User::factory()->coach()->create();

        $this->actingAs($coach)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    });

    it('cannot access the accountant dashboard', function () {
        $coach = User::factory()->coach()->create();

        $this->actingAs($coach)
            ->get(route('accountant.dashboard'))
            ->assertForbidden();
    });

    it('cannot access the athlete dashboard', function () {
        $coach = User::factory()->coach()->create();

        $this->actingAs($coach)
            ->get(route('athlete.dashboard'))
            ->assertForbidden();
    });

});
