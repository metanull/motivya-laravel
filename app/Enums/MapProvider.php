<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Represents the selected map/geocoding provider stack.
 *
 * Google: requires GOOGLE_MAPS_API_KEY — uses Google Maps Platform for all
 *         mapping capabilities (display, geocoding, directions, health checks).
 *
 * Free:   no API key required — uses the free-service stack
 *         (MapLibre + OpenFreeMap tiles + Nominatim + OSM directions).
 */
enum MapProvider: string
{
    case Google = 'google';
    case Free = 'free';
}
