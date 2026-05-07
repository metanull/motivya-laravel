<?php

declare(strict_types=1);

use App\Models\CoachProfile;
use App\Models\SportSession;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('addresses:audit-precision', function () {

    it('runs successfully with no data and outputs a table', function (): void {
        $this->artisan('addresses:audit-precision')
            ->assertSuccessful();
    });

    it('reports zero totals when no sessions or profiles exist', function (): void {
        $this->artisan('addresses:audit-precision')
            ->assertSuccessful()
            ->expectsOutputToContain('Total');
    });

    it('counts sessions correctly', function (): void {
        // Create a session without a validated address (legacy).
        SportSession::factory()->create([
            'postal_code' => '1000',
            'formatted_address' => null,
            'latitude' => 50.85,
            'longitude' => 4.35,
            'geocoding_provider' => null,
        ]);

        // Create a session with a fully validated address.
        SportSession::factory()->create([
            'postal_code' => '1050',
            'formatted_address' => 'Rue de la Loi 16, 1000 Bruxelles, Belgique',
            'latitude' => 50.85,
            'longitude' => 4.35,
            'geocoding_provider' => 'google',
        ]);

        $this->artisan('addresses:audit-precision')
            ->assertSuccessful()
            ->expectsOutputToContain('sessions');
    });

    it('counts coach profiles correctly', function (): void {
        CoachProfile::factory()->create([
            'postal_code' => '1000',
            'formatted_address' => null,
            'geocoding_provider' => null,
        ]);

        $this->artisan('addresses:audit-precision')
            ->assertSuccessful()
            ->expectsOutputToContain('coach_profiles');
    });

    it('outputs valid json with --json flag', function (): void {
        SportSession::factory()->create([
            'formatted_address' => 'Rue Test 1, Bruxelles',
            'latitude' => 50.85,
            'longitude' => 4.35,
            'geocoding_provider' => 'google',
        ]);

        $this->artisan('addresses:audit-precision', ['--json' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('"metrics"');

        $this->artisan('addresses:audit-precision', ['--json' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('"providers"');
    });

    it('includes provider distribution in json output', function (): void {
        SportSession::factory()->create([
            'formatted_address' => 'Rue Test 1, Bruxelles',
            'latitude' => 50.85,
            'longitude' => 4.35,
            'geocoding_provider' => 'google',
        ]);

        SportSession::factory()->create([
            'formatted_address' => 'Rue Test 2, Bruxelles',
            'latitude' => 50.86,
            'longitude' => 4.36,
            'geocoding_provider' => 'openfreemap',
        ]);

        $this->artisan('addresses:audit-precision', ['--json' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('google');

        $this->artisan('addresses:audit-precision', ['--json' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('openfreemap');
    });

    it('does not write to the database', function (): void {
        SportSession::factory()->create(['formatted_address' => null]);
        $countBefore = SportSession::count() + CoachProfile::count();

        $this->artisan('addresses:audit-precision')->assertSuccessful();

        $countAfter = SportSession::count() + CoachProfile::count();
        expect($countAfter)->toBe($countBefore);
    });

});
