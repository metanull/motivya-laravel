<?php

declare(strict_types=1);

use App\Models\CoachProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

describe('stripe:connect-accounts', function () {
    it('outputs usable Stripe Connect accounts as JSON', function (): void {
        $coach = User::factory()->coach()->create([
            'email' => 'coach@example.test',
            'name' => 'UAT Coach',
        ]);

        CoachProfile::factory()->approved()->for($coach)->create([
            'stripe_account_id' => 'acct_uat_ready',
            'stripe_onboarding_complete' => true,
        ]);

        expect(Artisan::call('stripe:connect-accounts', ['--json' => true]))->toBe(0);

        $payload = json_decode(trim(Artisan::output()), true, 512, JSON_THROW_ON_ERROR);

        expect($payload['recommended_account_id'])->toBe('acct_uat_ready')
            ->and($payload['accounts'][0]['user_email'])->toBe('coach@example.test')
            ->and($payload['accounts'][0]['is_usable_for_uat'])->toBeTrue();
    });

    it('prints only the recommended account id for shell scripts', function (): void {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->for($coach)->create([
            'stripe_account_id' => 'acct_script_ready',
            'stripe_onboarding_complete' => true,
        ]);

        $this->artisan('stripe:connect-accounts --usable-only --account-id-only')
            ->expectsOutput('acct_script_ready')
            ->assertSuccessful();
    });

    it('fails account-id-only when no usable account exists', function (): void {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->pending()->for($coach)->create([
            'stripe_account_id' => 'acct_not_ready',
            'stripe_onboarding_complete' => false,
        ]);

        $this->artisan('stripe:connect-accounts --usable-only --account-id-only')
            ->assertFailed();
    });
});
