<?php

declare(strict_types=1);

use App\Models\CoachProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

describe('CoachProfile address columns', function () {

    it('stores null for all new address columns by default', function () {
        $profile = CoachProfile::factory()->create();
        $profile->refresh();

        expect($profile->formatted_address)->toBeNull();
        expect($profile->street_address)->toBeNull();
        expect($profile->locality)->toBeNull();
        expect($profile->latitude)->toBeNull();
        expect($profile->longitude)->toBeNull();
        expect($profile->geocoding_provider)->toBeNull();
        expect($profile->geocoding_place_id)->toBeNull();
        expect($profile->geocoded_at)->toBeNull();
        expect($profile->geocoding_payload)->toBeNull();
    });

    it('allows mass-assignment of all new address fillable columns', function () {
        $profile = CoachProfile::factory()->create([
            'formatted_address' => 'Avenue Louise 54, 1050 Bruxelles, Belgium',
            'street_address' => 'Avenue Louise 54',
            'locality' => 'Ixelles',
            'latitude' => 50.8264,
            'longitude' => 4.3700,
            'geocoding_provider' => 'google',
            'geocoding_place_id' => 'ChIJcoach_mass_assign',
            'geocoded_at' => '2026-05-06 10:00:00',
            'geocoding_payload' => ['place_id' => 'ChIJcoach_mass_assign', 'status' => 'OK'],
        ]);

        $profile->refresh();

        expect($profile->formatted_address)->toBe('Avenue Louise 54, 1050 Bruxelles, Belgium');
        expect($profile->street_address)->toBe('Avenue Louise 54');
        expect($profile->locality)->toBe('Ixelles');
        expect($profile->geocoding_provider)->toBe('google');
        expect($profile->geocoding_place_id)->toBe('ChIJcoach_mass_assign');
    });

    it('casts latitude to a decimal string with 7 decimal places', function () {
        $profile = CoachProfile::factory()->create(['latitude' => 50.8467123]);
        $profile->refresh();

        // Laravel decimal cast returns a numeric string formatted to the given precision
        expect($profile->latitude)->not()->toBeNull();
        expect((float) $profile->latitude)->toBeFloat();
        expect((float) $profile->latitude)->toBeGreaterThan(50.0);
    });

    it('casts longitude to a decimal string with 7 decimal places', function () {
        $profile = CoachProfile::factory()->create(['longitude' => 4.3525678]);
        $profile->refresh();

        expect($profile->longitude)->not()->toBeNull();
        expect((float) $profile->longitude)->toBeFloat();
        expect((float) $profile->longitude)->toBeGreaterThan(4.0);
    });

    it('stores null latitude and longitude when not provided', function () {
        $profile = CoachProfile::factory()->create([
            'latitude' => null,
            'longitude' => null,
        ]);
        $profile->refresh();

        expect($profile->latitude)->toBeNull();
        expect($profile->longitude)->toBeNull();
    });

    it('casts geocoded_at to a Carbon datetime', function () {
        $profile = CoachProfile::factory()->create([
            'geocoded_at' => '2026-05-06 09:15:00',
        ]);

        $profile->refresh();

        expect($profile->geocoded_at)->toBeInstanceOf(Carbon::class);
        expect($profile->geocoded_at->format('Y-m-d H:i:s'))->toBe('2026-05-06 09:15:00');
    });

    it('casts geocoding_payload to array', function () {
        $payload = ['place_id' => 'ChIJcoach_payload', 'types' => ['street_address'], 'status' => 'OK'];

        $profile = CoachProfile::factory()->create([
            'geocoding_payload' => $payload,
        ]);

        $profile->refresh();

        expect($profile->geocoding_payload)->toBeArray();
        expect($profile->geocoding_payload)->toBe($payload);
    });

    it('stores null geocoding_payload when not provided', function () {
        $profile = CoachProfile::factory()->create(['geocoding_payload' => null]);
        $profile->refresh();

        expect($profile->geocoding_payload)->toBeNull();
    });
});

describe('CoachProfileFactory::withValidatedAddress()', function () {

    it('creates a profile with all geocoding fields populated', function () {
        $profile = CoachProfile::factory()->withValidatedAddress()->create();

        expect($profile->formatted_address)->not()->toBeNull();
        expect($profile->street_address)->not()->toBeNull();
        expect($profile->locality)->not()->toBeNull();
        expect($profile->latitude)->not()->toBeNull();
        expect($profile->longitude)->not()->toBeNull();
        expect($profile->geocoding_provider)->toBe('google');
        expect($profile->geocoding_place_id)->not()->toBeNull();
        expect($profile->geocoded_at)->not()->toBeNull();
    });

    it('can be combined with other factory states', function () {
        $profile = CoachProfile::factory()
            ->approved()
            ->withValidatedAddress()
            ->create();

        expect($profile->verified_at)->not()->toBeNull();
        expect($profile->formatted_address)->not()->toBeNull();
    });
});
