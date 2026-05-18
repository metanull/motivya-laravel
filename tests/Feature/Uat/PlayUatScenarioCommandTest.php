<?php

declare(strict_types=1);

use App\Models\Booking;
use App\Models\CoachProfile;
use App\Models\SportSession;
use App\Models\UatMailCapture;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('uat:play-scenario', function () {
    beforeEach(function (): void {
        app()->detectEnvironment(fn (): string => 'uat');
        config([
            'app.env' => 'uat',
            'mail.default' => 'array',
            'mail.uat_capture.enabled' => true,
            'queue.default' => 'sync',
        ]);
    });

    it('plays a simulated UAT scenario with isolated generated data', function (): void {
        $this->artisan('uat:play-scenario --run-id=uat_test --coaches=2 --athletes=6 --sessions-per-coach=2 --payments=simulated --failed-payment-rate=20 --exceptional-refunds=1 --fresh --force')
            ->assertSuccessful();

        expect(User::query()->where('email', 'like', 'uat+coach%', 'and')->count('*'))->toBe(2)
            ->and(User::query()->where('email', 'like', 'uat+athlete%', 'and')->count('*'))->toBe(6)
            ->and(CoachProfile::query()->whereNotNull('stripe_account_id', 'and')->count('*'))->toBe(2)
            ->and(SportSession::query()->where('title', 'like', 'UAT Scenario uat_test%', 'and')->count('*'))->toBe(4)
            ->and(Booking::query()->count('*'))->toBeGreaterThan(0)
            ->and(Booking::query()->where('status', '=', 'refunded', 'and')->count('*'))->toBeGreaterThanOrEqual(1)
            ->and(UatMailCapture::where('run_id', 'uat_test', 'and')->count())->toBeGreaterThan(0);
    });
});
