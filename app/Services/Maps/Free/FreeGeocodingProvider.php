<?php

declare(strict_types=1);

namespace App\Services\Maps\Free;

use App\Contracts\Maps\GeocodingProviderContract;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Geocoding provider backed by a Nominatim-compatible endpoint.
 *
 * Returns [latitude, longitude] on success, or null on failure.
 */
final class FreeGeocodingProvider implements GeocodingProviderContract
{
    /**
     * @return array{0: float, 1: float}|null
     */
    public function geocode(string $query, string $locale, string $country): ?array
    {
        try {
            $baseUrl = (string) config(
                'maps.free.geocoding_base_url',
                'https://nominatim.openstreetmap.org/search',
            );
            $apiKey = config('maps.free.geocoding_api_key');

            $request = Http::timeout((int) config('maps.geocoding_timeout', 5))
                ->acceptJson()
                ->withHeaders(['User-Agent' => (string) config('maps.free.nominatim_user_agent', 'Motivya/1.0 (+https://motivya.be)')]);

            if (is_string($apiKey) && $apiKey !== '') {
                $request = $request->withHeaders(['X-Api-Key' => $apiKey]);
            }

            $response = $request->get($baseUrl, [
                'q' => $query,
                'format' => 'json',
                'addressdetails' => 0,
                'countrycodes' => strtolower($country),
                'limit' => 1,
            ]);

            if (! $response->successful()) {
                Log::warning('GeocodingService: Free provider HTTP error.', [
                    'status' => $response->status(),
                ]);

                return null;
            }

            /** @var array<int,array<string,mixed>> $data */
            $data = $response->json();

            if (empty($data) || ! isset($data[0])) {
                return null;
            }

            $lat = $data[0]['lat'] ?? null;
            $lng = $data[0]['lon'] ?? null;

            if ($lat === null || $lng === null) {
                return null;
            }

            return [(float) $lat, (float) $lng];
        } catch (\Throwable $e) {
            Log::warning('GeocodingService: Free provider failed.', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
