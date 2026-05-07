<?php

declare(strict_types=1);

use App\Models\SportSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

describe('SportSession address columns', function () {

    it('stores null for all address columns by default', function () {
        $session = SportSession::factory()->create();
        $session->refresh();

        expect($session->formatted_address)->toBeNull();
        expect($session->street_address)->toBeNull();
        expect($session->locality)->toBeNull();
        expect($session->country)->toBeNull();
        expect($session->geocoding_provider)->toBeNull();
        expect($session->geocoding_place_id)->toBeNull();
        expect($session->geocoded_at)->toBeNull();
        expect($session->geocoding_payload)->toBeNull();
    });

    it('allows mass-assignment of all new address fillable columns', function () {
        $session = SportSession::factory()->create([
            'formatted_address' => 'Rue de la Loi 200, 1049 Bruxelles, Belgium',
            'street_address' => 'Rue de la Loi 200',
            'locality' => 'Bruxelles',
            'country' => 'BE',
            'geocoding_provider' => 'google',
            'geocoding_place_id' => 'ChIJmass_assign_test',
            'geocoded_at' => '2026-05-06 12:00:00',
            'geocoding_payload' => ['place_id' => 'ChIJmass_assign_test', 'status' => 'OK'],
        ]);

        $session->refresh();

        expect($session->formatted_address)->toBe('Rue de la Loi 200, 1049 Bruxelles, Belgium');
        expect($session->street_address)->toBe('Rue de la Loi 200');
        expect($session->locality)->toBe('Bruxelles');
        expect($session->country)->toBe('BE');
        expect($session->geocoding_provider)->toBe('google');
        expect($session->geocoding_place_id)->toBe('ChIJmass_assign_test');
    });

    it('casts geocoded_at to a Carbon datetime', function () {
        $session = SportSession::factory()->create([
            'geocoded_at' => '2026-05-06 14:30:00',
        ]);

        $session->refresh();

        expect($session->geocoded_at)->toBeInstanceOf(Carbon::class);
        expect($session->geocoded_at->format('Y-m-d H:i:s'))->toBe('2026-05-06 14:30:00');
    });

    it('casts geocoding_payload to array', function () {
        $payload = ['place_id' => 'ChIJtest', 'types' => ['premise'], 'status' => 'OK'];

        $session = SportSession::factory()->create([
            'geocoding_payload' => $payload,
        ]);

        $session->refresh();

        expect($session->geocoding_payload)->toBeArray();
        expect($session->geocoding_payload)->toBe($payload);
    });

    it('stores null geocoding_payload when not provided', function () {
        $session = SportSession::factory()->create(['geocoding_payload' => null]);
        $session->refresh();

        expect($session->geocoding_payload)->toBeNull();
    });
});

describe('SportSession::hasValidatedAddress()', function () {

    it('returns false when all address columns are null', function () {
        $session = SportSession::factory()->create();

        expect($session->hasValidatedAddress())->toBeFalse();
    });

    it('returns false when formatted_address is null', function () {
        $session = SportSession::factory()->withCoordinates()->create([
            'formatted_address' => null,
            'geocoding_provider' => 'google',
        ]);

        expect($session->hasValidatedAddress())->toBeFalse();
    });

    it('returns false when latitude is null', function () {
        $session = SportSession::factory()->create([
            'formatted_address' => 'Rue Test 1, 1000 Bruxelles',
            'latitude' => null,
            'longitude' => 4.3517,
            'geocoding_provider' => 'google',
        ]);

        expect($session->hasValidatedAddress())->toBeFalse();
    });

    it('returns false when longitude is null', function () {
        $session = SportSession::factory()->create([
            'formatted_address' => 'Rue Test 1, 1000 Bruxelles',
            'latitude' => 50.8503,
            'longitude' => null,
            'geocoding_provider' => 'google',
        ]);

        expect($session->hasValidatedAddress())->toBeFalse();
    });

    it('returns false when geocoding_provider is null', function () {
        $session = SportSession::factory()->withCoordinates()->create([
            'formatted_address' => 'Rue Test 1, 1000 Bruxelles',
            'geocoding_provider' => null,
        ]);

        expect($session->hasValidatedAddress())->toBeFalse();
    });

    it('returns true when formatted_address, lat, lng and provider are all set', function () {
        $session = SportSession::factory()->withValidatedAddress()->create();

        expect($session->hasValidatedAddress())->toBeTrue();
    });
});

describe('SportSessionFactory::withValidatedAddress()', function () {

    it('creates a session with all geocoding fields populated', function () {
        $session = SportSession::factory()->withValidatedAddress()->create();

        expect($session->formatted_address)->not()->toBeNull();
        expect($session->street_address)->not()->toBeNull();
        expect($session->locality)->not()->toBeNull();
        expect($session->country)->toBe('BE');
        expect($session->latitude)->not()->toBeNull();
        expect($session->longitude)->not()->toBeNull();
        expect($session->geocoding_provider)->toBe('google');
        expect($session->geocoding_place_id)->not()->toBeNull();
        expect($session->geocoded_at)->not()->toBeNull();
    });

    it('creates a session that passes hasValidatedAddress()', function () {
        $session = SportSession::factory()->withValidatedAddress()->create();

        expect($session->hasValidatedAddress())->toBeTrue();
    });
});
