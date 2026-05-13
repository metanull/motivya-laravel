<?php

declare(strict_types=1);

namespace App\Services\Maps\Google;

use App\Contracts\Maps\GeocodingProviderContract;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Geocoding provider backed by the Google Geocoding API.
 *
 * Returns [latitude, longitude] on success, or null on failure.
 */
final class GoogleGeocodingProvider implements GeocodingProviderContract
{
    /**
     * @return array{0: float, 1: float}|null
     */
    public function geocode(string $query, string $locale, string $country): ?array
    {
        try {
            $response = Http::timeout((int) config('maps.geocoding_timeout', 5))
                ->get((string) config('maps.google.geocoding_base_url'), [
                    'address' => $query.', '.$country,
                    'key' => config('maps.google.api_key'),
                    'language' => $locale,
                    'region' => strtolower($country),
                ]);

            if (! $response->successful()) {
                Log::warning('GeocodingService: Google HTTP error.', [
                    'status' => $response->status(),
                ]);

                return null;
            }

            /** @var array<string,mixed> $data */
            $data = $response->json();

            if (empty($data['results'])) {
                return null;
            }

            $lat = $data['results'][0]['geometry']['location']['lat'] ?? null;
            $lng = $data['results'][0]['geometry']['location']['lng'] ?? null;

            if ($lat === null || $lng === null) {
                return null;
            }

            return [(float) $lat, (float) $lng];
        } catch (\Throwable $e) {
            Log::warning('GeocodingService: Google provider failed.', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
