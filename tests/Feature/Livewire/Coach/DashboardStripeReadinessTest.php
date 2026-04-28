<?php

declare(strict_types=1);

use App\Livewire\Coach\Dashboard;
use App\Models\CoachProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('coach dashboard stripe readiness', function () {
    it('shows stripe setup banner when coach has no stripe account', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->for($coach)->create([
            'stripe_account_id' => null,
            'stripe_onboarding_complete' => false,
        ]);

        Livewire::actingAs($coach)
            ->test(Dashboard::class)
            ->assertSee(__('coach.stripe_setup_required_heading'))
            ->assertSee(__('coach.stripe_setup_start'));
    });

    it('shows continue stripe setup link when account exists but onboarding is incomplete', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->for($coach)->create([
            'stripe_account_id' => 'acct_incomplete',
            'stripe_onboarding_complete' => false,
        ]);

        Livewire::actingAs($coach)
            ->test(Dashboard::class)
            ->assertSee(__('coach.stripe_setup_required_heading'))
            ->assertSee(__('coach.stripe_setup_continue'));
    });

    it('hides stripe setup banner when onboarding is complete', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->for($coach)->create([
            'stripe_account_id' => 'acct_complete',
            'stripe_onboarding_complete' => true,
        ]);

        Livewire::actingAs($coach)
            ->test(Dashboard::class)
            ->assertDontSee(__('coach.stripe_setup_required_heading'));
    });

    it('hides stripe setup banner when coach profile is null', function () {
        $coach = User::factory()->coach()->create();

        Livewire::actingAs($coach)
            ->test(Dashboard::class)
            ->assertDontSee(__('coach.stripe_setup_required_heading'));
    });
});
