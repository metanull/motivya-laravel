<?php

declare(strict_types=1);

namespace App\Contracts\Maps;

/**
 * Contract for geocoding providers.
 *
 * Implementations must resolve a location query to geographic coordinates,
 * returning [latitude, longitude] on success or null when the query cannot
 * be resolved.
 */
interface GeocodingProviderContract
{
    /**
     * Geocode a location query.
     *
     * @param  string  $query  Location query (normalised, lower-case)
     * @param  string  $locale  BCP-47 locale hint
     * @param  string  $country  ISO 3166-1 alpha-2 scope
     * @return array{0: float, 1: float}|null [latitude, longitude] or null
     */
    public function geocode(string $query, string $locale, string $country): ?array;
}
