<?php

declare(strict_types=1);

namespace App\Services;

use App\DataTransferObjects\ValidatedAddress;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Validates a free-text address against an external geocoding provider and
 * returns a structured, Belgium-scoped ValidatedAddress DTO.
 *
 * Provider selection is driven by config('maps.geocoding_provider'):
 *   "google"      — Google Geocoding API
 *   "openfreemap" — Nominatim-compatible API (OpenFreeMap / self-hosted)
 *
 * Results are cached in the Laravel cache store (keyed by query + locale +
 * country + provider) for config('maps.geocoding_cache_ttl') seconds.
 * The geocoding_cache DB table is NOT used here; it belongs to GeocodingService.
 */
final class AddressValidationService
{
    /**
     * Cache entry wrapper key indicating a successful lookup.
     * Using a wrapper avoids the "null is not cached" issue with Cache::remember().
     */
    private const FOUND = 'found';

    private const DATA = 'data';

    public function __construct(
        private readonly GeocodingService $geocodingService,
    ) {}

    /**
     * Validate a free-text address query against the configured provider.
     *
     * Returns a ValidatedAddress DTO on success, or null when:
     *  - The provider is not configured (e.g. missing API key)
     *  - The provider returns no results
     *  - The result is outside Belgium
     *  - An HTTP or parsing error occurs
     *
     * @param  string  $query  Human-readable address query (e.g. "Rue de la Loi 16, Bruxelles")
     * @param  string  $locale  BCP-47 locale hint forwarded to the provider (default: fr)
     * @param  string  $country  ISO 3166-1 alpha-2 country scope (default: BE)
     */
    public function validate(
        string $query,
        string $locale = 'fr',
        string $country = 'BE',
    ): ?ValidatedAddress {
        $normalized = strtolower(trim($query));
        $provider = (string) config('maps.geocoding_provider', 'google');

        // Fail fast when the Google key is absent — do not cache this outcome
        // because the key may be added later without a cache flush.
        if ($provider === 'google' && ! $this->geocodingService->googleApiKeyConfigured()) {
            Log::warning('AddressValidationService: Google API key is not configured.');

            return null;
        }

        $ttl = (int) config('maps.geocoding_cache_ttl', 86400);
        $cacheKey = $this->buildCacheKey($normalized, $locale, $country, $provider);

        /** @var array{found: bool, data: ?array<string,mixed>} $entry */
        $entry = Cache::remember(
            $cacheKey,
            $ttl,
            function () use ($normalized, $locale, $country, $provider): array {
                $result = match ($provider) {
                    'openfreemap' => $this->validateOpenFreeMap($normalized, $locale, $country),
                    default => $this->validateGoogle($normalized, $locale, $country),
                };

                if ($result === null) {
                    return [self::FOUND => false, self::DATA => null];
                }

                return [self::FOUND => true, self::DATA => $this->addressToArray($result)];
            },
        );

        if (! $entry[self::FOUND]) {
            return null;
        }

        /** @var array<string,mixed> $data */
        $data = $entry[self::DATA];

        return $this->addressFromArray($data);
    }

    // -------------------------------------------------------------------------
    // Google Geocoding provider
    // -------------------------------------------------------------------------

    /**
     * Call the Google Geocoding API and parse the first Belgium-scoped result.
     */
    private function validateGoogle(
        string $query,
        string $locale,
        string $country,
    ): ?ValidatedAddress {
        try {
            $response = Http::timeout((int) config('maps.geocoding_timeout', 5))
                ->get((string) config('maps.google_geocoding_base_url'), [
                    'address' => $query.', '.$country,
                    'key' => config('maps.google_api_key'),
                    'language' => $locale,
                    'region' => strtolower($country),
                ]);

            if (! $response->successful()) {
                Log::warning('AddressValidationService: Google HTTP error.', [
                    'status' => $response->status(),
                ]);

                return null;
            }

            /** @var array<string,mixed> $data */
            $data = $response->json();

            if (empty($data['results'])) {
                return null;
            }

            /** @var array<string,mixed> $result */
            $result = $data['results'][0];

            // Require coordinates — reject results without geometry.
            if (empty($result['geometry']['location']['lat']) || empty($result['geometry']['location']['lng'])) {
                return null;
            }

            // Reject results outside Belgium.
            $countryCode = $this->extractGoogleComponent(
                $result['address_components'] ?? [],
                'country',
                'short_name',
            );

            if ($countryCode !== 'BE') {
                return null;
            }

            $streetNumber = $this->extractGoogleComponent($result['address_components'] ?? [], 'street_number');
            $route = $this->extractGoogleComponent($result['address_components'] ?? [], 'route');
            $streetAddress = $this->buildStreetAddress($route, $streetNumber);

            $locality = $this->extractGoogleComponent($result['address_components'] ?? [], 'locality')
                          ?? $this->extractGoogleComponent($result['address_components'] ?? [], 'postal_town');
            $postalCode = $this->extractGoogleComponent($result['address_components'] ?? [], 'postal_code');

            $lat = (float) $result['geometry']['location']['lat'];
            $lng = (float) $result['geometry']['location']['lng'];

            $rawJson = json_encode($result) ?: '';
            $rawPayload = ['_raw' => substr($rawJson, 0, 500)];

            return new ValidatedAddress(
                formattedAddress: (string) ($result['formatted_address'] ?? $query),
                streetAddress: $streetAddress,
                locality: $locality,
                postalCode: $postalCode,
                country: 'BE',
                latitude: $lat,
                longitude: $lng,
                provider: 'google',
                providerPlaceId: isset($result['place_id']) ? (string) $result['place_id'] : null,
                rawPayload: $rawPayload,
            );
        } catch (\Throwable $e) {
            Log::warning('AddressValidationService: Google provider failed.', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Extract a single value from a Google address_components array by type.
     *
     * @param  array<int,array<string,mixed>>  $components
     */
    private function extractGoogleComponent(
        array $components,
        string $type,
        string $field = 'long_name',
    ): ?string {
        foreach ($components as $component) {
            /** @var array<string> $types */
            $types = $component['types'] ?? [];

            if (in_array($type, $types, true)) {
                $value = $component[$field] ?? null;

                return is_string($value) ? $value : null;
            }
        }

        return null;
    }

    // -------------------------------------------------------------------------
    // OpenFreeMap / Nominatim provider
    // -------------------------------------------------------------------------

    /**
     * Call a Nominatim-compatible endpoint and parse the first Belgium-scoped result.
     */
    private function validateOpenFreeMap(
        string $query,
        string $locale,
        string $country,
    ): ?ValidatedAddress {
        try {
            $baseUrl = (string) config(
                'maps.openfreemap_geocoding_base_url',
                'https://nominatim.openstreetmap.org/search',
            );
            $apiKey = config('maps.openfreemap_geocoding_api_key');

            $request = Http::timeout((int) config('maps.geocoding_timeout', 5))
                ->acceptJson();

            // Only add the header when a key is actually configured.
            if (is_string($apiKey) && $apiKey !== '') {
                $request = $request->withHeaders(['X-Api-Key' => $apiKey]);
            }

            $response = $request->get($baseUrl, [
                'q' => $query,
                'format' => 'json',
                'addressdetails' => 1,
                'countrycodes' => 'be',
                'limit' => 1,
            ]);

            if (! $response->successful()) {
                Log::warning('AddressValidationService: OpenFreeMap HTTP error.', [
                    'status' => $response->status(),
                ]);

                return null;
            }

            /** @var array<int,array<string,mixed>> $data */
            $data = $response->json();

            if (empty($data) || ! isset($data[0])) {
                return null;
            }

            /** @var array<string,mixed> $result */
            $result = $data[0];

            /** @var array<string,mixed> $address */
            $address = $result['address'] ?? [];

            // Reject results outside Belgium (double-check even though we pass countrycodes=be).
            $countryCode = strtolower((string) ($address['country_code'] ?? ''));

            if ($countryCode !== 'be') {
                return null;
            }

            $houseNumber = isset($address['house_number']) ? (string) $address['house_number'] : null;
            $road = isset($address['road']) ? (string) $address['road'] : null;
            $streetAddress = $this->buildStreetAddress($road, $houseNumber);

            $locality = isset($address['city']) ? (string) $address['city']
                        : (isset($address['town']) ? (string) $address['town']
                        : (isset($address['village']) ? (string) $address['village'] : null));
            $postalCode = isset($address['postcode']) ? (string) $address['postcode'] : null;

            $lat = (float) ($result['lat'] ?? 0.0);
            $lng = (float) ($result['lon'] ?? 0.0);

            $rawJson = json_encode($result) ?: '';
            $rawPayload = ['_raw' => substr($rawJson, 0, 500)];

            $placeId = isset($result['place_id']) ? (string) $result['place_id'] : null;

            return new ValidatedAddress(
                formattedAddress: (string) ($result['display_name'] ?? $query),
                streetAddress: $streetAddress,
                locality: $locality,
                postalCode: $postalCode,
                country: 'BE',
                latitude: $lat,
                longitude: $lng,
                provider: 'openfreemap',
                providerPlaceId: $placeId,
                rawPayload: $rawPayload,
            );
        } catch (\Throwable $e) {
            Log::warning('AddressValidationService: OpenFreeMap provider failed.', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Build a human-readable street address from its components.
     * Returns null when the street name itself is absent.
     */
    private function buildStreetAddress(?string $street, ?string $number): ?string
    {
        if ($street === null) {
            return null;
        }

        return $number !== null ? "{$street} {$number}" : $street;
    }

    /**
     * Derive a unique cache key for an address validation query.
     * The 'av:' prefix prevents collisions with the GeocodingService entries
     * which use 'sha256(query|locale|country|google)' without any prefix.
     */
    private function buildCacheKey(
        string $normalized,
        string $locale,
        string $country,
        string $provider,
    ): string {
        $hash = hash('sha256', $normalized.'|'.$locale.'|'.$country.'|'.$provider);

        return 'address_validation:'.$hash;
    }

    /**
     * Serialize a ValidatedAddress to a plain PHP array for cache storage.
     *
     * @return array<string,mixed>
     */
    private function addressToArray(ValidatedAddress $address): array
    {
        return [
            'formattedAddress' => $address->formattedAddress,
            'streetAddress' => $address->streetAddress,
            'locality' => $address->locality,
            'postalCode' => $address->postalCode,
            'country' => $address->country,
            'latitude' => $address->latitude,
            'longitude' => $address->longitude,
            'provider' => $address->provider,
            'providerPlaceId' => $address->providerPlaceId,
            'rawPayload' => $address->rawPayload,
        ];
    }

    /**
     * Rehydrate a ValidatedAddress from a plain PHP array (cache hit).
     *
     * @param  array<string,mixed>  $data
     */
    private function addressFromArray(array $data): ValidatedAddress
    {
        return new ValidatedAddress(
            formattedAddress: (string) $data['formattedAddress'],
            streetAddress: isset($data['streetAddress']) ? (string) $data['streetAddress'] : null,
            locality: isset($data['locality']) ? (string) $data['locality'] : null,
            postalCode: isset($data['postalCode']) ? (string) $data['postalCode'] : null,
            country: (string) $data['country'],
            latitude: (float) $data['latitude'],
            longitude: (float) $data['longitude'],
            provider: (string) $data['provider'],
            providerPlaceId: isset($data['providerPlaceId']) ? (string) $data['providerPlaceId'] : null,
            rawPayload: isset($data['rawPayload']) && is_array($data['rawPayload'])
                                  ? $data['rawPayload']
                                  : null,
        );
    }
}
