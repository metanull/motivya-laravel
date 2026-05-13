<?php

declare(strict_types=1);

namespace App\Services\Maps\Google;

use App\Contracts\Maps\HealthProbeProviderContract;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Health probe for the Google Maps Platform provider.
 *
 * Checks:
 *  1. API key is present
 *  2. API key starts with 'AIza' (standard Google key format)
 *  3. Geocoding endpoint is reachable (lightweight probe request)
 */
final class GoogleHealthProbeProvider implements HealthProbeProviderContract
{
    /** Fixed Belgian test address used for the reachability probe. */
    private const PROBE_ADDRESS = 'Grand-Place, 1000 Bruxelles, Belgium';

    /**
     * @return array<int, array{capability: string, status: 'ok'|'fail', message: string}>
     */
    public function probe(): array
    {
        $results = [];
        $key = (string) config('maps.google.api_key', '');

        // 1. Key present
        if ($key === '') {
            $results[] = [
                'capability' => 'api_key_present',
                'status' => 'fail',
                'message' => 'GOOGLE_MAPS_API_KEY is not configured.',
            ];

            return $results;
        }

        $results[] = [
            'capability' => 'api_key_present',
            'status' => 'ok',
            'message' => 'Google Maps API key is configured.',
        ];

        // 2. Key format
        $formatOk = str_starts_with($key, 'AIza');
        $results[] = [
            'capability' => 'api_key_format',
            'status' => $formatOk ? 'ok' : 'fail',
            'message' => $formatOk
                ? 'API key matches the expected AIza... format.'
                : 'API key does not start with "AIza". Check GOOGLE_MAPS_API_KEY.',
        ];

        // 3. Geocoding endpoint reachable
        try {
            $response = Http::timeout((int) config('maps.geocoding_timeout', 5))
                ->get((string) config('maps.google.geocoding_base_url'), [
                    'address' => self::PROBE_ADDRESS,
                    'key' => $key,
                    'language' => 'en',
                    'region' => 'be',
                ]);

            if ($response->successful()) {
                $results[] = [
                    'capability' => 'geocoding_reachable',
                    'status' => 'ok',
                    'message' => 'Google Geocoding API is reachable.',
                ];
            } else {
                $results[] = [
                    'capability' => 'geocoding_reachable',
                    'status' => 'fail',
                    'message' => "Google Geocoding API returned HTTP {$response->status()}.",
                ];
            }
        } catch (\Throwable $e) {
            Log::warning('GoogleHealthProbeProvider: geocoding probe failed.', ['message' => $e->getMessage()]);
            $results[] = [
                'capability' => 'geocoding_reachable',
                'status' => 'fail',
                'message' => 'Google Geocoding API is not reachable: '.$e->getMessage(),
            ];
        }

        return $results;
    }
}
