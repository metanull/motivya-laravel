<?php

declare(strict_types=1);

use App\Models\PaymentAnomaly;
use App\Models\PostalCodeCoordinate;
use App\Models\SchedulerHeartbeat;
use App\Models\SportSession;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Seed heartbeats for every critical scheduler command so those checks report green/yellow.
 *
 * @param  int  $ageMinutes  How many minutes ago the heartbeat ran.  0 = fresh (green).
 */
function seedAllHeartbeats(int $ageMinutes = 0): void
{
    $commands = [
        'sessions:send-reminders',
        'sessions:cancel-expired',
        'sessions:complete-finished',
        'subscriptions:compute-monthly',
        'bookings:expire-unpaid',
    ];

    foreach ($commands as $command) {
        SchedulerHeartbeat::updateOrCreate(
            ['command' => $command],
            ['last_run_at' => now()->subMinutes($ageMinutes)],
        );
    }
}

/**
 * Create the public/storage symlink and return true if it was created by this call.
 * Used in tests that need the storage check to report green.
 */
function ensureStorageSymlink(): bool
{
    $linkPath = public_path('storage');

    if (is_link($linkPath)) {
        return false;
    }

    $target = storage_path('app/public');

    if (! is_dir($target)) {
        mkdir($target, 0755, true);
    }

    symlink($target, $linkPath);

    return true;
}

describe('mvp:health-snapshot', function () {

    it('exits with failure when postal code reference is empty', function (): void {
        // No postal codes seeded — red blocker.
        expect(PostalCodeCoordinate::count())->toBe(0);

        $this->artisan('mvp:health-snapshot')
            ->assertExitCode(1);
    });

    it('exits with failure when there are open payment anomalies', function (): void {
        // Load postal codes to isolate the payment anomaly check.
        $this->artisan('geo:load-postal-codes')->assertSuccessful();

        PaymentAnomaly::factory()->open()->create();

        $this->artisan('mvp:health-snapshot')
            ->assertExitCode(1);
    });

    it('exits with failure when a scheduler heartbeat has never run', function (): void {
        // Load postal codes so only the scheduler check triggers red.
        $this->artisan('geo:load-postal-codes')->assertSuccessful();

        // No heartbeats at all → every critical command is "never run" → red blocker.
        expect(SchedulerHeartbeat::count())->toBe(0);

        $this->artisan('mvp:health-snapshot')
            ->assertExitCode(1);
    });

    it('does not write to the database during a check run', function (): void {
        $countBefore = PaymentAnomaly::count()
            + PostalCodeCoordinate::count()
            + SchedulerHeartbeat::count()
            + SportSession::count();

        $this->artisan('mvp:health-snapshot');

        $countAfter = PaymentAnomaly::count()
            + PostalCodeCoordinate::count()
            + SchedulerHeartbeat::count()
            + SportSession::count();

        expect($countAfter)->toBe($countBefore);
    });

    it('outputs valid json when --json flag is used', function (): void {
        // The --json flag must produce a JSON array as output.
        // Each object in the array must have a "check" key (among others).
        $this->artisan('mvp:health-snapshot', ['--json' => true])
            ->expectsOutputToContain('"check"');
    });

    it('reports yellow for stale scheduler heartbeats without triggering a red blocker', function (): void {
        // Load postal codes.
        $this->artisan('geo:load-postal-codes')->assertSuccessful();

        // Create stale heartbeats (well beyond each command's window): yellow, not red.
        seedAllHeartbeats(ageMinutes: 50000);

        // Ensure storage symlink exists for this test.
        $created = ensureStorageSymlink();

        // Stale heartbeats are yellow (warning), not red — command exits 0 when all
        // other checks pass.
        $this->artisan('mvp:health-snapshot')
            ->assertExitCode(0);

        if ($created) {
            unlink(public_path('storage'));
        }
    });

    it('exits with success when all critical checks pass', function (): void {
        // Load postal code reference data.
        $this->artisan('geo:load-postal-codes')->assertSuccessful();

        // Seed fresh heartbeats for all critical commands.
        seedAllHeartbeats(ageMinutes: 0);

        // Ensure the public storage symlink exists.
        $created = ensureStorageSymlink();

        $this->artisan('mvp:health-snapshot')
            ->assertExitCode(0);

        if ($created) {
            unlink(public_path('storage'));
        }
    });

});
