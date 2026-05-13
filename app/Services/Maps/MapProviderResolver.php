<?php

declare(strict_types=1);

namespace App\Services\Maps;

use App\Enums\MapProvider;

/**
 * Resolves the active map provider based on application configuration.
 *
 * Rule: Google is selected when `config('maps.google.api_key')` is a
 * non-empty string; otherwise the free stack (OpenFreeMap + Nominatim) is
 * used. The legacy `MAPS_GEOCODING_PROVIDER` environment variable is
 * intentionally ignored by this resolver.
 */
final class MapProviderResolver
{
    /**
     * Resolve the active map provider.
     *
     * Returns MapProvider::Google when GOOGLE_MAPS_API_KEY is configured,
     * MapProvider::Free otherwise.
     */
    public function resolve(): MapProvider
    {
        $key = config('maps.google.api_key');

        if (is_string($key) && $key !== '') {
            return MapProvider::Google;
        }

        return MapProvider::Free;
    }

    /**
     * Validate all capabilities for the resolved provider.
     *
     * Each entry has:
     *   - capability: human-readable name
     *   - status: 'ok' | 'fail'
     *   - message: explanatory text
     *
     * @return array<int, array{capability: string, status: 'ok'|'fail', message: string}>
     */
    public function validateCapabilities(): array
    {
        $provider = $this->resolve();
        $results = [];

        if ($provider === MapProvider::Google) {
            $key = (string) config('maps.google.api_key', '');

            $results[] = [
                'capability' => 'api_key_present',
                'status' => $key !== '' ? 'ok' : 'fail',
                'message' => $key !== '' ? 'Google Maps API key is set.' : 'GOOGLE_MAPS_API_KEY is not configured.',
            ];

            $results[] = [
                'capability' => 'api_key_format',
                'status' => str_starts_with($key, 'AIza') ? 'ok' : 'fail',
                'message' => str_starts_with($key, 'AIza')
                    ? 'API key matches the expected AIza... format.'
                    : 'API key does not match the expected AIza... format.',
            ];
        } else {
            $styleUrl = (string) config('maps.free.tile_style_url', '');
            $results[] = [
                'capability' => 'tile_style_url',
                'status' => $styleUrl !== '' ? 'ok' : 'fail',
                'message' => $styleUrl !== '' ? "Tile style URL configured: {$styleUrl}" : 'MAPS_TILE_STYLE_URL is not configured.',
            ];

            $geocodingUrl = (string) config('maps.free.geocoding_base_url', '');
            $results[] = [
                'capability' => 'geocoding_url',
                'status' => $geocodingUrl !== '' ? 'ok' : 'fail',
                'message' => $geocodingUrl !== '' ? "Geocoding URL configured: {$geocodingUrl}" : 'Free geocoding URL is not configured.',
            ];
        }

        return $results;
    }
}
