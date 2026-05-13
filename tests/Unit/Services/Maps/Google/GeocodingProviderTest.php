<?php

declare(strict_types=1);

use App\Services\Maps\Free\FreeGeocodingProvider;
use App\Services\Maps\Google\GoogleGeocodingProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

describe('GoogleGeocodingProvider', function (): void {

    beforeEach(function (): void {
        Config::set('maps.google.api_key', 'AIzaFakeKeyForTest12345678');
        Config::set('maps.google.geocoding_base_url', 'https://maps.googleapis.com/maps/api/geocode/json');
        Config::set('maps.geocoding_timeout', 5);
    });

    it('returns [lat, lng] for a successful Google response', function (): void {
        Http::fake([
            'https://maps.googleapis.com/*' => Http::response([
                'results' => [[
                    'geometry' => ['location' => ['lat' => 50.8451, 'lng' => 4.3569]],
                ]],
            ], 200),
        ]);

        $provider = new GoogleGeocodingProvider;
        $result = $provider->geocode('bruxelles', 'fr', 'BE');

        expect($result)->toBe([50.8451, 4.3569]);
    });

    it('returns null when results are empty', function (): void {
        Http::fake([
            'https://maps.googleapis.com/*' => Http::response(['results' => []], 200),
        ]);

        $provider = new GoogleGeocodingProvider;
        $result = $provider->geocode('unknown place', 'fr', 'BE');

        expect($result)->toBeNull();
    });

    it('returns null on HTTP error', function (): void {
        Http::fake([
            'https://maps.googleapis.com/*' => Http::response([], 500),
        ]);

        $provider = new GoogleGeocodingProvider;
        $result = $provider->geocode('bruxelles', 'fr', 'BE');

        expect($result)->toBeNull();
    });

});

describe('FreeGeocodingProvider', function (): void {

    beforeEach(function (): void {
        Config::set('maps.free.geocoding_base_url', 'https://nominatim.openstreetmap.org/search');
        Config::set('maps.free.geocoding_api_key', null);
        Config::set('maps.geocoding_timeout', 5);
    });

    it('returns [lat, lng] for a successful Nominatim response', function (): void {
        Http::fake([
            'https://nominatim.openstreetmap.org/*' => Http::response([
                ['lat' => '50.8467', 'lon' => '4.3525'],
            ], 200),
        ]);

        $provider = new FreeGeocodingProvider;
        $result = $provider->geocode('bruxelles', 'fr', 'BE');

        expect($result)->toBe([50.8467, 4.3525]);
    });

    it('returns null when Nominatim returns empty array', function (): void {
        Http::fake([
            'https://nominatim.openstreetmap.org/*' => Http::response([], 200),
        ]);

        $provider = new FreeGeocodingProvider;
        $result = $provider->geocode('unknown place', 'fr', 'BE');

        expect($result)->toBeNull();
    });

    it('sends X-Api-Key header when key is configured', function (): void {
        Config::set('maps.free.geocoding_api_key', 'my-key');

        Http::fake([
            'https://nominatim.openstreetmap.org/*' => Http::response([
                ['lat' => '50.8467', 'lon' => '4.3525'],
            ], 200),
        ]);

        $provider = new FreeGeocodingProvider;
        $provider->geocode('bruxelles', 'fr', 'BE');

        Http::assertSent(fn ($req): bool => $req->hasHeader('X-Api-Key', 'my-key'));
    });

});
