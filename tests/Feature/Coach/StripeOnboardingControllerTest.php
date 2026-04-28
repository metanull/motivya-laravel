<?php

declare(strict_types=1);

use App\Models\CoachProfile;
use App\Models\User;
use App\Services\StripeConnectService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('coach stripe onboarding', function () {
    it('redirects to stripe from start endpoint', function () {
        $coach = User::factory()->coach()->create();
        $coachProfile = CoachProfile::factory()->approved()->for($coach)->create();

        $mockService = new StripeConnectService(
            createAccountUsing: fn (array $payload): object => (object) ['id' => 'acct_test_123'],
            createAccountLinkUsing: fn (array $payload): object => (object) ['url' => 'https://connect.stripe.test/onboarding'],
        );

        app()->instance(StripeConnectService::class, $mockService);

        $response = $this->actingAs($coach)->get(route('coach.stripe.onboard'));

        $response->assertRedirect('https://connect.stripe.test/onboarding');

        expect($coachProfile->fresh()->stripe_account_id)->toBe('acct_test_123');
    });

    it('reuses existing stripe account id on start', function () {
        $coach = User::factory()->coach()->create();
        $coachProfile = CoachProfile::factory()->approved()->for($coach)->create([
            'stripe_account_id' => 'acct_existing',
        ]);

        $mockService = new StripeConnectService(
            createAccountLinkUsing: fn (array $payload): object => (object) ['url' => 'https://connect.stripe.test/onboarding'],
        );

        app()->instance(StripeConnectService::class, $mockService);

        $response = $this->actingAs($coach)->get(route('coach.stripe.onboard'));

        $response->assertRedirect('https://connect.stripe.test/onboarding');

        // account ID unchanged
        expect($coachProfile->fresh()->stripe_account_id)->toBe('acct_existing');
    });

    it('forbids start endpoint for users without a coach profile', function () {
        $coach = User::factory()->coach()->create();

        $response = $this->actingAs($coach)->get(route('coach.stripe.onboard'));

        $response->assertForbidden();
    });

    it('redirects to dashboard after return from stripe', function () {
        $coach = User::factory()->coach()->create();

        $response = $this->actingAs($coach)->get(route('coach.stripe.return'));

        $response->assertRedirect(route('coach.dashboard'));
    });

    it('returns 404 on refresh when coach has no stripe account', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->for($coach)->create([
            'stripe_account_id' => null,
        ]);

        $response = $this->actingAs($coach)->get(route('coach.stripe.refresh'));

        $response->assertNotFound();
    });

    it('generates new onboarding link on refresh', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->for($coach)->create([
            'stripe_account_id' => 'acct_refresh_123',
        ]);

        $mockService = new StripeConnectService(
            createAccountLinkUsing: fn (array $payload): object => (object) ['url' => 'https://connect.stripe.test/refresh'],
        );

        app()->instance(StripeConnectService::class, $mockService);

        $response = $this->actingAs($coach)->get(route('coach.stripe.refresh'));

        $response->assertRedirect('https://connect.stripe.test/refresh');
    });

    it('requires authentication for start endpoint', function () {
        $response = $this->get(route('coach.stripe.onboard'));

        $response->assertRedirect(route('login'));
    });

    it('denies athletes access to start endpoint', function () {
        $athlete = User::factory()->athlete()->create();

        $response = $this->actingAs($athlete)->get(route('coach.stripe.onboard'));

        $response->assertForbidden();
    });
});
