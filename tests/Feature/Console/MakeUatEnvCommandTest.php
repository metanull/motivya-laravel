<?php

declare(strict_types=1);

use App\Models\CoachProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

describe('env:make-uat', function () {
    it('generates a separate UAT env file with a discovered Stripe Connect account', function (): void {
        $directory = storage_path('framework/testing/env-uat-'.bin2hex(random_bytes(4)));
        File::makeDirectory($directory, 0775, true);

        $source = $directory.'/.env';
        $destination = $directory.'/.env.uat';
        File::put($source, implode(PHP_EOL, [
            'APP_ENV=production',
            'APP_DEBUG=false',
            'APP_URL=https://motivya.metanull.eu',
            'STRIPE_KEY=pk_test_123',
            'STRIPE_SECRET=sk_test_123',
            'STRIPE_WEBHOOK_SECRET=whsec_123',
            '',
        ]));

        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->for($coach)->create([
            'stripe_account_id' => 'acct_env_ready',
            'stripe_onboarding_complete' => true,
        ]);

        $this->artisan("env:make-uat --path={$destination} --from={$source}")
            ->assertSuccessful();

        expect(File::get($source))->toContain('APP_ENV=production')
            ->and(File::get($destination))->toContain('APP_ENV=uat')
            ->and(File::get($destination))->toContain('MOTIVYA_DEPLOY_PROFILE=uat')
            ->and(File::get($destination))->toContain('MOTIVYA_STRIPE_LIVE_TESTS=1')
            ->and(File::get($destination))->toContain('MOTIVYA_STRIPE_CONNECTED_ACCOUNT_ID=acct_env_ready');
    });

    it('refuses to overwrite an existing UAT env file without force', function (): void {
        $directory = storage_path('framework/testing/env-uat-'.bin2hex(random_bytes(4)));
        File::makeDirectory($directory, 0775, true);

        $source = $directory.'/.env';
        $destination = $directory.'/.env.uat';
        File::put($source, "APP_ENV=production\nSTRIPE_KEY=pk_test_123\nSTRIPE_SECRET=sk_test_123\nSTRIPE_WEBHOOK_SECRET=whsec_123\n");
        File::put($destination, "APP_ENV=uat\n");

        $this->artisan("env:make-uat --path={$destination} --from={$source}")
            ->assertFailed();

        expect(File::get($destination))->toBe("APP_ENV=uat\n");
    });

    it('rejects live Stripe keys when generating UAT env files', function (): void {
        $directory = storage_path('framework/testing/env-uat-'.bin2hex(random_bytes(4)));
        File::makeDirectory($directory, 0775, true);

        $source = $directory.'/.env';
        $destination = $directory.'/.env.uat';
        File::put($source, "APP_ENV=production\nSTRIPE_KEY=pk_live_123\nSTRIPE_SECRET=sk_test_123\nSTRIPE_WEBHOOK_SECRET=whsec_123\n");

        $this->artisan("env:make-uat --path={$destination} --from={$source}")
            ->assertFailed();

        expect(File::exists($destination))->toBeFalse();
    });
});
