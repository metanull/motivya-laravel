<?php

declare(strict_types=1);

namespace App\Services\Maps\Free;

use App\Contracts\Maps\HealthProbeProviderContract;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Health probe for the free map stack (OpenFreeMap + Nominatim + OSM).
 *
 * Checks:
 *  1. Tile style URL is configured
 *  2. Geocoding endpoint URL is configured
 *  3. Geocoding endpoint is reachable
 */
final class FreeHealthProbeProvider implements HealthProbeProviderContract
{
    /**
     * @return array<int, array{capability: string, status: 'ok'|'fail', message: string}>
     */
    public function probe(): array
    {
        $results = [];

        // 1. Style URL configured
        $styleUrl = (string) config('maps.free.tile_style_url', '');
        $results[] = [
            'capability' => 'tile_style_url',
            'status' => $styleUrl !== '' ? 'ok' : 'fail',
            'message' => $styleUrl !== ''
                ? "Tile style URL configured: {$styleUrl}"
                : 'MAPS_TILE_STYLE_URL is not configured.',
        ];

        // 2. Geocoding URL configured
        $geocodingUrl = (string) config('maps.free.geocoding_base_url', '');
        if ($geocodingUrl === '') {
            $results[] = [
                'capability' => 'geocoding_url',
                'status' => 'fail',
                'message' => 'Free geocoding URL is not configured.',
            ];

            return $results;
        }

        $results[] = [
            'capability' => 'geocoding_url',
            'status' => 'ok',
            'message' => "Geocoding URL configured: {$geocodingUrl}",
        ];

        // 3. Geocoding endpoint reachable
        try {
            $apiKey = config('maps.free.geocoding_api_key');
            $request = Http::timeout((int) config('maps.geocoding_timeout', 5))
                ->acceptJson()
                ->withHeaders(['User-Agent' => (string) config('maps.free.nominatim_user_agent', 'Motivya/1.0 (+https://motivya.be)')]);

            if (is_string($apiKey) && $apiKey !== '') {
                $request = $request->withHeaders(['X-Api-Key' => $apiKey]);
            }

            $response = $request->get($geocodingUrl, [
                'q' => 'Grand-Place, Bruxelles',
                'format' => 'json',
                'countrycodes' => 'be',
                'limit' => 1,
            ]);

            if ($response->successful()) {
                $results[] = [
                    'capability' => 'geocoding_reachable',
                    'status' => 'ok',
                    'message' => 'Nominatim geocoding endpoint is reachable.',
                ];
            } else {
                $results[] = [
                    'capability' => 'geocoding_reachable',
                    'status' => 'fail',
                    'message' => "Nominatim geocoding endpoint returned HTTP {$response->status()}.",
                ];
            }
        } catch (\Throwable $e) {
            Log::warning('FreeHealthProbeProvider: geocoding probe failed.', ['message' => $e->getMessage()]);
            $results[] = [
                'capability' => 'geocoding_reachable',
                'status' => 'fail',
                'message' => 'Nominatim geocoding endpoint is not reachable: '.$e->getMessage(),
            ];
        }

        return $results;
    }
}
