<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

/**
 * Immutable value object representing a resolved geographic location.
 *
 * Carries the coordinates, the source of the resolution, and the precision
 * level so callers can communicate to users whether the location is exact
 * (street-level geocoding) or approximate (postal-code centroid).
 */
final class ResolvedLocation
{
    /**
     * @param  float  $latitude  WGS-84 latitude
     * @param  float  $longitude  WGS-84 longitude
     * @param  string  $source  Human-readable source description (e.g. query string)
     * @param  string  $precision  'exact' | 'approximate' | 'browser'
     * @param  string  $query  The original input query
     * @param  ?string  $provider  Geocoding provider name, or null for local/browser sources
     */
    public function __construct(
        public readonly float $latitude,
        public readonly float $longitude,
        public readonly string $source,
        public readonly string $precision,
        public readonly string $query,
        public readonly ?string $provider,
    ) {}
}
