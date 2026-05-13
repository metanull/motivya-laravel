<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Provider Selection
    |--------------------------------------------------------------------------
    |
    | Provider is resolved automatically: when GOOGLE_MAPS_API_KEY is set to a
    | non-empty string, all mapping uses Google Maps Platform; otherwise the
    | free stack (MapLibre + OpenFreeMap + Nominatim + OSM) is used.
    |
    | There is NO manual provider toggle — the key presence IS the switch.
    |
    */

    'geocoding_timeout' => (int) env('GEOCODING_TIMEOUT', 5),
    'geocoding_cache_ttl' => (int) env('GEOCODING_CACHE_TTL', 86400),

    /*
    |--------------------------------------------------------------------------
    | Google Maps Platform
    |--------------------------------------------------------------------------
    */
    'google' => [
        'api_key' => env('GOOGLE_MAPS_API_KEY'),
        'geocoding_base_url' => 'https://maps.googleapis.com/maps/api/geocode/json',
        'directions_base_url' => 'https://www.google.com/maps/dir/',
        'maps_js_url' => 'https://maps.googleapis.com/maps/api/js',
    ],

    /*
    |--------------------------------------------------------------------------
    | Free Stack (MapLibre + OpenFreeMap + Nominatim + OSM)
    |--------------------------------------------------------------------------
    |
    | Used when GOOGLE_MAPS_API_KEY is absent or empty.
    | OPENFREEMAP_GEOCODING_API_KEY is optional — only needed for hosted
    | Nominatim instances that require authentication (X-Api-Key header).
    |
    */
    'free' => [
        'tile_style_url' => env('MAPS_TILE_STYLE_URL', 'https://tiles.openfreemap.org/styles/liberty'),
        'geocoding_base_url' => env('OPENFREEMAP_GEOCODING_BASE_URL', 'https://nominatim.openstreetmap.org/search'),
        'geocoding_api_key' => env('OPENFREEMAP_GEOCODING_API_KEY'),
        'nominatim_user_agent' => env('NOMINATIM_USER_AGENT', 'Motivya/1.0 (+https://motivya.be)'),
        'directions_base_url' => env('MAPS_FREE_DIRECTIONS_BASE_URL', 'https://www.openstreetmap.org/directions'),
        'attribution' => '© OpenStreetMap contributors',
    ],
];
