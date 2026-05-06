<?php

declare(strict_types=1);

return [
    'tile_style_url' => env('MAPS_TILE_STYLE_URL', 'https://tiles.openfreemap.org/styles/liberty'),
    'google_directions_base_url' => 'https://www.google.com/maps/dir/',
    'google_geocoding_base_url' => 'https://maps.googleapis.com/maps/api/geocode/json',
    'google_api_key' => env('GOOGLE_MAPS_API_KEY'),
    'geocoding_timeout' => (int) env('GEOCODING_TIMEOUT', 5),
    'geocoding_cache_ttl' => (int) env('GEOCODING_CACHE_TTL', 86400),

    /*
    |--------------------------------------------------------------------------
    | Address Validation Provider
    |--------------------------------------------------------------------------
    |
    | The geocoding provider used by AddressValidationService. Accepted values:
    |   "google"      — Google Geocoding API (requires GOOGLE_MAPS_API_KEY)
    |   "openfreemap" — Nominatim-compatible endpoint (no key required by default)
    |
    */
    'geocoding_provider' => env('MAPS_GEOCODING_PROVIDER', 'google'),

    /*
    |--------------------------------------------------------------------------
    | OpenFreeMap / Nominatim Geocoding
    |--------------------------------------------------------------------------
    |
    | Base URL for the Nominatim-compatible address search endpoint.
    | An optional API key can be supplied for hosted Nominatim services that
    | require authentication (sent as the X-Api-Key request header).
    |
    */
    'openfreemap_geocoding_base_url' => env(
        'OPENFREEMAP_GEOCODING_BASE_URL',
        'https://nominatim.openstreetmap.org/search',
    ),
    'openfreemap_geocoding_api_key' => env('OPENFREEMAP_GEOCODING_API_KEY'),
];
