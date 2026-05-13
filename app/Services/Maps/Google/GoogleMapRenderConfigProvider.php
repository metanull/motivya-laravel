<?php

declare(strict_types=1);

namespace App\Services\Maps\Google;

use App\Contracts\Maps\MapRenderConfigProviderContract;
use App\DataTransferObjects\Maps\MapRenderConfig;

/**
 * Map render configuration provider for Google Maps Platform.
 *
 * Returns a MapRenderConfig with provider='google', the Google API key,
 * and the Google Maps JS URL; no styleUrl or attribution.
 */
final class GoogleMapRenderConfigProvider implements MapRenderConfigProviderContract
{
    public function getRenderConfig(
        string $containerId,
        array $markers,
        array $fallbackCenter,
        bool $singleMarker,
    ): MapRenderConfig {
        return new MapRenderConfig(
            provider: 'google',
            containerId: $containerId,
            center: $fallbackCenter,
            zoom: $singleMarker ? 14 : 11,
            markers: $markers,
            clustering: ! $singleMarker,
            locale: app()->getLocale(),
            styleUrl: null,
            googleApiKey: (string) config('maps.google.api_key', ''),
            googleMapsJsUrl: (string) config('maps.google.maps_js_url', 'https://maps.googleapis.com/maps/api/js'),
            attribution: null,
        );
    }
}
