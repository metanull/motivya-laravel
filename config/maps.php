<?php

declare(strict_types=1);

return [
    'tile_style_url' => env('MAPS_TILE_STYLE_URL', 'https://tiles.openfreemap.org/styles/liberty'),
    'google_directions_base_url' => 'https://www.google.com/maps/dir/',
    'google_geocoding_base_url' => 'https://maps.googleapis.com/maps/api/geocode/json',
    'google_api_key' => env('GOOGLE_MAPS_API_KEY'),
    'geocoding_timeout' => (int) env('GEOCODING_TIMEOUT', 5),
    'geocoding_cache_ttl' => (int) env('GEOCODING_CACHE_TTL', 86400),
];
