<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Maps\AddressValidationProviderContract;
use App\DataTransferObjects\ValidatedAddress;
use App\Services\Maps\MapProviderResolver;
use Illuminate\Support\Facades\Cache;

/**
 * Validates a free-text address against the active map provider and returns a
 * structured, Belgium-scoped ValidatedAddress DTO.
 *
 * Provider selection is fully automatic: when GOOGLE_MAPS_API_KEY is set the
 * Google Geocoding API is used; otherwise the free Nominatim-backed stack is
 * used. The selection is made by MapProviderResolver at request time so that
 * changing the environment variable takes effect without a cache flush.
 *
 * Results are cached in the Laravel cache store (keyed by query + locale +
 * country + resolved-provider-name) for config('maps.geocoding_cache_ttl')
 * seconds.
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
        private readonly AddressValidationProviderContract $provider,
        private readonly MapProviderResolver $resolver,
    ) {}

    /**
     * Validate a free-text address query against the active provider.
     *
     * Returns a ValidatedAddress DTO on success, or null when:
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
        $providerName = $this->resolver->resolve()->value;

        $ttl = (int) config('maps.geocoding_cache_ttl', 86400);
        $cacheKey = $this->buildCacheKey($normalized, $locale, $country, $providerName);

        /** @var array{found: bool, data: ?array<string,mixed>} $entry */
        $entry = Cache::remember(
            $cacheKey,
            $ttl,
            function () use ($normalized, $locale, $country): array {
                $result = $this->provider->validate($normalized, $locale, $country);

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
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Derive a unique cache key for an address validation query.
     * The 'address_validation:' prefix prevents collisions with GeocodingService entries.
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
