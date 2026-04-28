<?php

declare(strict_types=1);

use App\Models\PostalCodeCoordinate;
use App\Models\SportSession;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('sessions:backfill-coordinates', function () {

    it('backfills sessions that have no coordinates', function (): void {
        PostalCodeCoordinate::create([
            'postal_code' => '1000',
            'municipality' => 'Bruxelles/Brussel',
            'latitude' => 50.8503000,
            'longitude' => 4.3517000,
        ]);

        $session = SportSession::factory()->create([
            'postal_code' => '1000',
            'latitude' => null,
            'longitude' => null,
        ]);

        $this->artisan('sessions:backfill-coordinates')->assertSuccessful();

        $fresh = $session->fresh();
        expect((float) $fresh->latitude)->toEqual(50.8503)
            ->and((float) $fresh->longitude)->toEqual(4.3517);
    });

    it('skips sessions that already have coordinates', function (): void {
        PostalCodeCoordinate::create([
            'postal_code' => '1000',
            'municipality' => 'Bruxelles/Brussel',
            'latitude' => 50.8503000,
            'longitude' => 4.3517000,
        ]);

        $session = SportSession::factory()->create([
            'postal_code' => '1000',
            'latitude' => 51.0000000,
            'longitude' => 5.0000000,
        ]);

        $this->artisan('sessions:backfill-coordinates')->assertSuccessful();

        // Coordinates must be unchanged — the session was not selected.
        $fresh = $session->fresh();
        expect((float) $fresh->latitude)->toEqual(51.0)
            ->and((float) $fresh->longitude)->toEqual(5.0);
    });

    it('handles sessions with unknown postal codes gracefully', function (): void {
        $session = SportSession::factory()->create([
            'postal_code' => '9999',
            'latitude' => null,
            'longitude' => null,
        ]);

        $this->artisan('sessions:backfill-coordinates')->assertSuccessful();

        $fresh = $session->fresh();
        expect($fresh->latitude)->toBeNull()
            ->and($fresh->longitude)->toBeNull();
    });

});
