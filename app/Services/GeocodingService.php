<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PostalCodeCoordinate;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class GeocodingService
{
    /**
     * Resolve a location query to coordinates.
     * Tries local postal-code table first, then Google Geocoding API.
     *
     * @return array{latitude: float, longitude: float}|null
     */
    public function resolve(string $query, string $locale = 'en', string $country = 'BE'): ?array
    {
        $normalized = strtolower(trim($query));

        // 1. Try local postal-code table
        $local = $this->resolveLocal($normalized);

        if ($local !== null) {
            return $local;
        }

        // 2. Try Google Geocoding (if key is configured)
        if ($this->googleApiKeyConfigured()) {
            return $this->resolveGoogle($normalized, $locale, $country);
        }

        return null;
    }

    private function resolveLocal(string $query): ?array
    {
        $coord = PostalCodeCoordinate::where('postal_code', $query)
            ->orWhere('municipality', 'like', $query.'%')
            ->first();

        if ($coord === null) {
            return null;
        }

        return ['latitude' => (float) $coord->latitude, 'longitude' => (float) $coord->longitude];
    }

    public function googleApiKeyConfigured(): bool
    {
        $key = config('maps.google_api_key');

        return ! empty($key) && is_string($key) && strlen($key) > 10;
    }

    private function resolveGoogle(string $query, string $locale, string $country): ?array
    {
        $hash = hash('sha256', $query.'|'.$locale.'|'.$country.'|google');
        $ttl = (int) config('maps.geocoding_cache_ttl', 86400);

        // Check database cache
        try {
            $cached = DB::table('geocoding_cache')->where('query_hash', $hash)->first();

            if ($cached !== null && now()->diffInSeconds(Carbon::parse($cached->cached_at)) < $ttl) {
                if (! $cached->found) {
                    return null;
                }

                return ['latitude' => (float) $cached->latitude, 'longitude' => (float) $cached->longitude];
            }
        } catch (\Throwable) {
            // geocoding_cache table may not exist yet
        }

        // Call Google API
        try {
            $response = Http::timeout((int) config('maps.geocoding_timeout', 5))
                ->get((string) config('maps.google_geocoding_base_url'), [
                    'address' => $query.', '.$country,
                    'key' => config('maps.google_api_key'),
                    'language' => $locale,
                    'region' => strtolower($country),
                ]);

            $data = $response->json();
            $found = ! empty($data['results']);
            $lat = $found ? $data['results'][0]['geometry']['location']['lat'] : null;
            $lng = $found ? $data['results'][0]['geometry']['location']['lng'] : null;

            // Store in database cache
            try {
                DB::table('geocoding_cache')->upsert(
                    [
                        'query_hash' => $hash,
                        'query' => $query,
                        'locale' => $locale,
                        'country' => $country,
                        'provider' => 'google',
                        'latitude' => $lat,
                        'longitude' => $lng,
                        'found' => $found,
                        'cached_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    ['query_hash'],
                    ['latitude', 'longitude', 'found', 'cached_at', 'updated_at'],
                );
            } catch (\Throwable) {
                // Silently ignore cache write failures
            }

            if (! $found) {
                return null;
            }

            return ['latitude' => (float) $lat, 'longitude' => (float) $lng];
        } catch (\Throwable $e) {
            Log::warning('Google geocoding failed: '.$e->getMessage());

            return null;
        }
    }
}
