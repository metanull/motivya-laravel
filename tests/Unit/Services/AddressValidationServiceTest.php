<?php

declare(strict_types=1);

use App\Contracts\Maps\AddressValidationProviderContract;
use App\DataTransferObjects\ValidatedAddress;
use App\Services\AddressValidationService;
use App\Services\Maps\Free\FreeAddressValidationProvider;
use App\Services\Maps\Google\GoogleAddressValidationProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Shared fixture helpers
// ---------------------------------------------------------------------------

/**
 * A minimal valid Google Geocoding API success response for a Belgian address.
 *
 * @return array<string,mixed>
 */
function googleBelgianSuccessResponse(): array
{
    return [
        'status' => 'OK',
        'results' => [
            [
                'place_id' => 'ChIJxxx123',
                'formatted_address' => 'Rue de la Loi 16, 1000 Bruxelles, Belgium',
                'geometry' => [
                    'location' => ['lat' => 50.8451, 'lng' => 4.3569],
                ],
                'address_components' => [
                    ['long_name' => '16', 'short_name' => '16', 'types' => ['street_number']],
                    ['long_name' => 'Rue de la Loi', 'short_name' => 'Rue de la Loi', 'types' => ['route']],
                    ['long_name' => 'Bruxelles', 'short_name' => 'Bruxelles', 'types' => ['locality', 'political']],
                    ['long_name' => '1000', 'short_name' => '1000', 'types' => ['postal_code']],
                    ['long_name' => 'Belgium', 'short_name' => 'BE', 'types' => ['country', 'political']],
                ],
            ],
        ],
    ];
}

/**
 * A valid Google Geocoding API response for a non-Belgian (French) address.
 *
 * @return array<string,mixed>
 */
function googleFrenchResponse(): array
{
    return [
        'status' => 'OK',
        'results' => [
            [
                'place_id' => 'ChIJfr000',
                'formatted_address' => '1 Rue de Rivoli, 75001 Paris, France',
                'geometry' => [
                    'location' => ['lat' => 48.8566, 'lng' => 2.3522],
                ],
                'address_components' => [
                    ['long_name' => 'France', 'short_name' => 'FR', 'types' => ['country', 'political']],
                ],
            ],
        ],
    ];
}

/**
 * A minimal valid Nominatim success response for a Belgian address.
 *
 * @return array<int,array<string,mixed>>
 */
function nominatimBelgianSuccessResponse(): array
{
    return [
        [
            'place_id' => 98765,
            'display_name' => 'Grand-Place / Grote Markt, Bruxelles, Région de Bruxelles-Capitale, Belgique',
            'lat' => '50.8467',
            'lon' => '4.3525',
            'address' => [
                'road' => 'Grand-Place',
                'house_number' => '1',
                'city' => 'Bruxelles',
                'postcode' => '1000',
                'country' => 'Belgique',
                'country_code' => 'be',
            ],
        ],
    ];
}

/**
 * A Nominatim response whose country_code is non-Belgian.
 *
 * @return array<int,array<string,mixed>>
 */
function nominatimFrenchResponse(): array
{
    return [
        [
            'place_id' => 11111,
            'display_name' => 'Place de la République, Paris, Île-de-France, France',
            'lat' => '48.8676',
            'lon' => '2.3635',
            'address' => [
                'city' => 'Paris',
                'country' => 'France',
                'country_code' => 'fr',
            ],
        ],
    ];
}

// ---------------------------------------------------------------------------
// Google provider — success
// ---------------------------------------------------------------------------

describe('AddressValidationService — Google provider', function (): void {

    beforeEach(function (): void {
        // Setting a non-empty api_key causes MapProviderResolver to select Google.
        Config::set('maps.google.api_key', 'test-google-api-key-xxxxxxxxxx');
        Config::set('maps.google.geocoding_base_url', 'https://maps.googleapis.com/maps/api/geocode/json');
        // Rebind the contract to the Google provider so tests target the right class.
        app()->bind(AddressValidationProviderContract::class, GoogleAddressValidationProvider::class);
        Cache::flush();
    });

    it('returns a ValidatedAddress for a successful Belgian result', function (): void {
        Http::fake([
            'https://maps.googleapis.com/*' => Http::response(googleBelgianSuccessResponse(), 200),
        ]);

        $service = app(AddressValidationService::class);
        $result = $service->validate('Rue de la Loi 16, Bruxelles');

        expect($result)->toBeInstanceOf(ValidatedAddress::class)
            ->and($result->formattedAddress)->toBe('Rue de la Loi 16, 1000 Bruxelles, Belgium')
            ->and($result->streetAddress)->toBe('Rue de la Loi 16')
            ->and($result->locality)->toBe('Bruxelles')
            ->and($result->postalCode)->toBe('1000')
            ->and($result->country)->toBe('BE')
            ->and($result->latitude)->toBe(50.8451)
            ->and($result->longitude)->toBe(4.3569)
            ->and($result->provider)->toBe('google')
            ->and($result->providerPlaceId)->toBe('ChIJxxx123')
            ->and($result->rawPayload)->toBeArray()
            ->and($result->rawPayload)->toHaveKey('_raw');
    });

    it('returns null when the Google result is outside Belgium', function (): void {
        Http::fake([
            'https://maps.googleapis.com/*' => Http::response(googleFrenchResponse(), 200),
        ]);

        $service = app(AddressValidationService::class);
        $result = $service->validate('1 Rue de Rivoli, Paris');

        expect($result)->toBeNull();
    });

    it('returns null when Google returns zero results', function (): void {
        Http::fake([
            'https://maps.googleapis.com/*' => Http::response(['results' => [], 'status' => 'ZERO_RESULTS'], 200),
        ]);

        $service = app(AddressValidationService::class);
        $result = $service->validate('Fake Street That Does Not Exist 9999');

        expect($result)->toBeNull();
    });

    it('returns null on a Google HTTP error and logs a warning', function (): void {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(fn (string $msg): bool => str_contains($msg, 'Google HTTP error'));

        Http::fake([
            'https://maps.googleapis.com/*' => Http::response([], 500),
        ]);

        $service = app(AddressValidationService::class);
        $result = $service->validate('Rue de la Loi 16, Bruxelles');

        expect($result)->toBeNull();
    });

    it('switches to the free provider when google api_key is absent and calls Nominatim', function (): void {
        // No Google key → resolver selects Free → Nominatim is called.
        Config::set('maps.google.api_key', null);
        Config::set('maps.free.geocoding_base_url', 'https://nominatim.openstreetmap.org/search');
        // Rebind to the free provider to reflect what AppServiceProvider would do.
        app()->bind(AddressValidationProviderContract::class, FreeAddressValidationProvider::class);
        Cache::flush();

        Http::fake([
            'https://nominatim.openstreetmap.org/*' => Http::response(nominatimBelgianSuccessResponse(), 200),
        ]);

        $service = app(AddressValidationService::class);
        $result = $service->validate('Grand-Place 1, Bruxelles');

        expect($result)->toBeInstanceOf(ValidatedAddress::class)
            ->and($result->provider)->toBe('openfreemap');

        Http::assertSent(fn ($request): bool => str_contains($request->url(), 'nominatim.openstreetmap.org'));
    });

    it('does not log the API key in the warning message', function (): void {
        $sensitiveKey = 'test-google-api-key-xxxxxxxxxx';

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function (string $msg, array $ctx) use ($sensitiveKey): bool {
                $serialised = json_encode(['msg' => $msg, 'ctx' => $ctx]);

                return ! str_contains((string) $serialised, $sensitiveKey);
            });

        Http::fake([
            'https://maps.googleapis.com/*' => Http::response([], 500),
        ]);

        $service = app(AddressValidationService::class);
        $service->validate('Rue de la Loi 16, Bruxelles');
    });

});

// ---------------------------------------------------------------------------
// OpenFreeMap / Nominatim provider — success & rejection
// ---------------------------------------------------------------------------

describe('AddressValidationService — OpenFreeMap provider', function (): void {

    beforeEach(function (): void {
        // No Google key → resolver selects Free.
        Config::set('maps.google.api_key', null);
        Config::set('maps.free.geocoding_base_url', 'https://nominatim.openstreetmap.org/search');
        Config::set('maps.free.geocoding_api_key', null);
        app()->bind(AddressValidationProviderContract::class, FreeAddressValidationProvider::class);
        Cache::flush();
    });

    it('returns a ValidatedAddress for a successful Belgian Nominatim result', function (): void {
        Http::fake([
            'https://nominatim.openstreetmap.org/*' => Http::response(nominatimBelgianSuccessResponse(), 200),
        ]);

        $service = app(AddressValidationService::class);
        $result = $service->validate('Grand-Place 1, Bruxelles');

        expect($result)->toBeInstanceOf(ValidatedAddress::class)
            ->and($result->formattedAddress)->toContain('Grand-Place')
            ->and($result->streetAddress)->toBe('Grand-Place 1')
            ->and($result->locality)->toBe('Bruxelles')
            ->and($result->postalCode)->toBe('1000')
            ->and($result->country)->toBe('BE')
            ->and($result->latitude)->toBe(50.8467)
            ->and($result->longitude)->toBe(4.3525)
            ->and($result->provider)->toBe('openfreemap')
            ->and($result->providerPlaceId)->toBe('98765')
            ->and($result->rawPayload)->toBeArray()
            ->and($result->rawPayload)->toHaveKey('_raw');
    });

    it('returns null when the Nominatim country_code is not "be"', function (): void {
        Http::fake([
            'https://nominatim.openstreetmap.org/*' => Http::response(nominatimFrenchResponse(), 200),
        ]);

        $service = app(AddressValidationService::class);
        $result = $service->validate('Place de la République, Paris');

        expect($result)->toBeNull();
    });

    it('returns null when Nominatim returns an empty result set', function (): void {
        Http::fake([
            'https://nominatim.openstreetmap.org/*' => Http::response([], 200),
        ]);

        $service = app(AddressValidationService::class);
        $result = $service->validate('Fake Street 9999');

        expect($result)->toBeNull();
    });

    it('returns null on a Nominatim HTTP error and logs a warning', function (): void {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(fn (string $msg): bool => str_contains($msg, 'OpenFreeMap HTTP error'));

        Http::fake([
            'https://nominatim.openstreetmap.org/*' => Http::response([], 503),
        ]);

        $service = app(AddressValidationService::class);
        $result = $service->validate('Grand-Place 1, Bruxelles');

        expect($result)->toBeNull();
    });

    it('sends the X-Api-Key header when an OpenFreeMap API key is configured', function (): void {
        Config::set('maps.free.geocoding_api_key', 'my-nominatim-key');

        Http::fake([
            'https://nominatim.openstreetmap.org/*' => Http::response(nominatimBelgianSuccessResponse(), 200),
        ]);

        $service = app(AddressValidationService::class);
        $service->validate('Grand-Place 1, Bruxelles');

        Http::assertSent(fn ($request): bool => $request->hasHeader('X-Api-Key', 'my-nominatim-key'));
    });

    it('does not send the X-Api-Key header when no API key is configured', function (): void {
        Http::fake([
            'https://nominatim.openstreetmap.org/*' => Http::response(nominatimBelgianSuccessResponse(), 200),
        ]);

        $service = app(AddressValidationService::class);
        $service->validate('Grand-Place 1, Bruxelles');

        Http::assertSent(fn ($request): bool => ! $request->hasHeader('X-Api-Key'));
    });

});

// ---------------------------------------------------------------------------
// Caching behaviour
// ---------------------------------------------------------------------------

describe('AddressValidationService — caching', function (): void {

    beforeEach(function (): void {
        Config::set('maps.google.api_key', 'test-google-api-key-xxxxxxxxxx');
        Config::set('maps.google.geocoding_base_url', 'https://maps.googleapis.com/maps/api/geocode/json');
        app()->bind(AddressValidationProviderContract::class, GoogleAddressValidationProvider::class);
        Cache::flush();
    });

    it('serves the second call from cache without making a second HTTP request', function (): void {
        Http::fake([
            'https://maps.googleapis.com/*' => Http::response(googleBelgianSuccessResponse(), 200),
        ]);

        $service = app(AddressValidationService::class);

        $first = $service->validate('Rue de la Loi 16, Bruxelles');
        $second = $service->validate('Rue de la Loi 16, Bruxelles');

        // Only one actual HTTP call should have been made.
        Http::assertSentCount(1);

        expect($first)->not->toBeNull()
            ->and($second)->not->toBeNull()
            ->and($first->formattedAddress)->toBe($second->formattedAddress)
            ->and($first->latitude)->toBe($second->latitude)
            ->and($first->longitude)->toBe($second->longitude);
    });

    it('caches a not-found result so the provider is not called again', function (): void {
        Http::fake([
            'https://maps.googleapis.com/*' => Http::response(['results' => [], 'status' => 'ZERO_RESULTS'], 200),
        ]);

        $service = app(AddressValidationService::class);

        $first = $service->validate('Fake Street That Does Not Exist 9999');
        $second = $service->validate('Fake Street That Does Not Exist 9999');

        Http::assertSentCount(1);

        expect($first)->toBeNull()->and($second)->toBeNull();
    });

    it('uses separate cache entries for different queries', function (): void {
        Http::fake([
            'https://maps.googleapis.com/*' => Http::sequence()
                ->push(googleBelgianSuccessResponse(), 200)
                ->push(['results' => [], 'status' => 'ZERO_RESULTS'], 200),
        ]);

        $service = app(AddressValidationService::class);

        $first = $service->validate('Rue de la Loi 16, Bruxelles');
        $second = $service->validate('Completely Different Address');

        Http::assertSentCount(2);

        expect($first)->not->toBeNull()
            ->and($second)->toBeNull();
    });

});

// ---------------------------------------------------------------------------
// Provider selection
// ---------------------------------------------------------------------------

describe('AddressValidationService — provider selection', function (): void {

    beforeEach(function (): void {
        Cache::flush();
    });

    it('dispatches requests to the Google endpoint when GOOGLE_MAPS_API_KEY is set', function (): void {
        Config::set('maps.google.api_key', 'test-google-api-key-xxxxxxxxxx');
        Config::set('maps.google.geocoding_base_url', 'https://maps.googleapis.com/maps/api/geocode/json');
        app()->bind(AddressValidationProviderContract::class, GoogleAddressValidationProvider::class);

        Http::fake(['*' => Http::response(['results' => [], 'status' => 'ZERO_RESULTS'], 200)]);

        $service = app(AddressValidationService::class);
        $service->validate('Rue de la Loi 16, Bruxelles');

        Http::assertSent(fn ($request): bool => str_contains($request->url(), 'googleapis.com'));
    });

    it('dispatches requests to the Nominatim endpoint when GOOGLE_MAPS_API_KEY is absent', function (): void {
        Config::set('maps.google.api_key', null);
        Config::set('maps.free.geocoding_base_url', 'https://nominatim.openstreetmap.org/search');
        app()->bind(AddressValidationProviderContract::class, FreeAddressValidationProvider::class);

        Http::fake(['*' => Http::response([], 200)]);

        $service = app(AddressValidationService::class);
        $service->validate('Grand-Place 1, Bruxelles');

        Http::assertSent(fn ($request): bool => str_contains($request->url(), 'nominatim.openstreetmap.org'));
    });

    it('defaults to the free stack when GOOGLE_MAPS_API_KEY is an empty string', function (): void {
        Config::set('maps.google.api_key', '');
        Config::set('maps.free.geocoding_base_url', 'https://nominatim.openstreetmap.org/search');
        app()->bind(AddressValidationProviderContract::class, FreeAddressValidationProvider::class);

        Http::fake(['*' => Http::response([], 200)]);

        $service = app(AddressValidationService::class);
        $service->validate('Rue de la Loi 16, Bruxelles');

        Http::assertSent(fn ($request): bool => str_contains($request->url(), 'nominatim.openstreetmap.org'));
    });

});
