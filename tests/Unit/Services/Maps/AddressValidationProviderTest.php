<?php

declare(strict_types=1);

use App\Services\Maps\Free\FreeAddressValidationProvider;
use App\Services\Maps\Google\GoogleAddressValidationProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// ---------------------------------------------------------------------------
// GoogleAddressValidationProvider
// ---------------------------------------------------------------------------

describe('GoogleAddressValidationProvider', function (): void {
    beforeEach(function (): void {
        Config::set('maps.google.api_key', 'AIzaTest');
        Config::set('maps.google.geocoding_base_url', 'https://maps.googleapis.com/maps/api/geocode/json');
    });

    it('returns a ValidatedAddress DTO for a successful Belgian result', function (): void {
        Http::fake([
            'maps.googleapis.com/*' => Http::response([
                'status' => 'OK',
                'results' => [[
                    'formatted_address' => 'Rue de la Loi 16, 1000 Bruxelles, Belgique',
                    'place_id' => 'ChIJgoogle123',
                    'geometry' => ['location' => ['lat' => 50.8451, 'lng' => 4.3569]],
                    'address_components' => [
                        ['types' => ['route'], 'long_name' => 'Rue de la Loi', 'short_name' => 'Rue de la Loi'],
                        ['types' => ['street_number'], 'long_name' => '16', 'short_name' => '16'],
                        ['types' => ['locality'], 'long_name' => 'Bruxelles', 'short_name' => 'Bruxelles'],
                        ['types' => ['postal_code'], 'long_name' => '1000', 'short_name' => '1000'],
                        ['types' => ['country'], 'long_name' => 'Belgique', 'short_name' => 'BE'],
                    ],
                ]],
            ]),
        ]);

        $provider = new GoogleAddressValidationProvider;
        $result = $provider->validate('Rue de la Loi 16, Bruxelles', 'fr', 'BE');

        expect($result)->not->toBeNull()
            ->and($result->latitude)->toBe(50.8451)
            ->and($result->longitude)->toBe(4.3569)
            ->and($result->postalCode)->toBe('1000')
            ->and($result->locality)->toBe('Bruxelles')
            ->and($result->streetAddress)->toBe('Rue de la Loi 16')
            ->and($result->country)->toBe('BE')
            ->and($result->provider)->toBe('google')
            ->and($result->providerPlaceId)->toBe('ChIJgoogle123');
    });

    it('returns null when the API returns ZERO_RESULTS', function (): void {
        Http::fake([
            'maps.googleapis.com/*' => Http::response([
                'status' => 'ZERO_RESULTS',
                'results' => [],
            ]),
        ]);

        $provider = new GoogleAddressValidationProvider;
        $result = $provider->validate('Nowhere XYZ 99999', 'fr', 'BE');

        expect($result)->toBeNull();
    });

    it('returns null when the result country code is not BE', function (): void {
        Http::fake([
            'maps.googleapis.com/*' => Http::response([
                'status' => 'OK',
                'results' => [[
                    'formatted_address' => 'Paris, France',
                    'place_id' => 'ChIJparis',
                    'geometry' => ['location' => ['lat' => 48.8566, 'lng' => 2.3522]],
                    'address_components' => [
                        ['types' => ['country'], 'long_name' => 'France', 'short_name' => 'FR'],
                    ],
                ]],
            ]),
        ]);

        $provider = new GoogleAddressValidationProvider;
        $result = $provider->validate('Paris', 'fr', 'BE');

        expect($result)->toBeNull();
    });

    it('returns null on HTTP error and logs a warning', function (): void {
        Http::fake([
            'maps.googleapis.com/*' => Http::response([], 503),
        ]);

        Log::spy();

        $provider = new GoogleAddressValidationProvider;
        $result = $provider->validate('Rue Test', 'fr', 'BE');

        expect($result)->toBeNull();
        Log::shouldHaveReceived('warning')->once();
    });

    it('returns null when geometry location is missing', function (): void {
        Http::fake([
            'maps.googleapis.com/*' => Http::response([
                'status' => 'OK',
                'results' => [[
                    'formatted_address' => 'Bruxelles, Belgique',
                    'geometry' => [],
                    'address_components' => [
                        ['types' => ['country'], 'long_name' => 'Belgique', 'short_name' => 'BE'],
                    ],
                ]],
            ]),
        ]);

        $provider = new GoogleAddressValidationProvider;
        $result = $provider->validate('Bruxelles', 'fr', 'BE');

        expect($result)->toBeNull();
    });
});

// ---------------------------------------------------------------------------
// FreeAddressValidationProvider
// ---------------------------------------------------------------------------

describe('FreeAddressValidationProvider', function (): void {
    beforeEach(function (): void {
        Config::set('maps.free.geocoding_base_url', 'https://nominatim.openstreetmap.org/search');
        Config::set('maps.free.geocoding_api_key', null);
    });

    it('returns a ValidatedAddress DTO for a successful Belgian Nominatim result', function (): void {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([[
                'place_id' => '123456',
                'lat' => '50.8451',
                'lon' => '4.3569',
                'display_name' => 'Rue de la Loi 16, 1000 Bruxelles, Belgique',
                'address' => [
                    'country_code' => 'be',
                    'city' => 'Bruxelles',
                    'postcode' => '1000',
                    'road' => 'Rue de la Loi',
                    'house_number' => '16',
                ],
            ]]),
        ]);

        $provider = new FreeAddressValidationProvider;
        $result = $provider->validate('Rue de la Loi 16, Bruxelles', 'fr', 'BE');

        expect($result)->not->toBeNull()
            ->and($result->latitude)->toBe(50.8451)
            ->and($result->longitude)->toBe(4.3569)
            ->and($result->postalCode)->toBe('1000')
            ->and($result->locality)->toBe('Bruxelles')
            ->and($result->streetAddress)->toBe('Rue de la Loi 16')
            ->and($result->country)->toBe('BE')
            ->and($result->provider)->toBe('openfreemap')
            ->and($result->providerPlaceId)->toBe('123456');
    });

    it('returns null when Nominatim returns an empty array', function (): void {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([]),
        ]);

        $provider = new FreeAddressValidationProvider;
        $result = $provider->validate('Nonexistent Place 99999', 'fr', 'BE');

        expect($result)->toBeNull();
    });

    it('returns null when the result country code is not be', function (): void {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([[
                'place_id' => '999',
                'lat' => '48.8566',
                'lon' => '2.3522',
                'display_name' => 'Paris, France',
                'address' => [
                    'country_code' => 'fr',
                    'city' => 'Paris',
                ],
            ]]),
        ]);

        $provider = new FreeAddressValidationProvider;
        $result = $provider->validate('Paris', 'fr', 'BE');

        expect($result)->toBeNull();
    });

    it('sends X-Api-Key header when geocoding_api_key is configured', function (): void {
        Config::set('maps.free.geocoding_api_key', 'my-secret-key');

        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([]),
        ]);

        $provider = new FreeAddressValidationProvider;
        $provider->validate('Test', 'fr', 'BE');

        Http::assertSent(function ($request): bool {
            return $request->hasHeader('X-Api-Key', 'my-secret-key');
        });
    });

    it('does not send X-Api-Key header when geocoding_api_key is null', function (): void {
        Config::set('maps.free.geocoding_api_key', null);

        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([]),
        ]);

        $provider = new FreeAddressValidationProvider;
        $provider->validate('Test', 'fr', 'BE');

        Http::assertSent(function ($request): bool {
            return ! $request->hasHeader('X-Api-Key');
        });
    });

    it('sends the default User-Agent header to Nominatim', function (): void {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([]),
        ]);

        $provider = new FreeAddressValidationProvider;
        $provider->validate('Test', 'fr', 'BE');

        Http::assertSent(fn ($request): bool => $request->hasHeader('User-Agent', 'Motivya/1.0 (+https://motivya.be)'));
    });

    it('sends the configured User-Agent header when nominatim_user_agent is overridden', function (): void {
        Config::set('maps.free.nominatim_user_agent', 'TestApp/3.0 (+https://test.example.com)');

        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([]),
        ]);

        $provider = new FreeAddressValidationProvider;
        $provider->validate('Test', 'fr', 'BE');

        Http::assertSent(fn ($request): bool => $request->hasHeader('User-Agent', 'TestApp/3.0 (+https://test.example.com)'));
    });

    it('returns null on HTTP error and logs a warning', function (): void {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([], 503),
        ]);

        Log::spy();

        $provider = new FreeAddressValidationProvider;
        $result = $provider->validate('Rue Test', 'fr', 'BE');

        expect($result)->toBeNull();
        Log::shouldHaveReceived('warning')->once();
    });
});
