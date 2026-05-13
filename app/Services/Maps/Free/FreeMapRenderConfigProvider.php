<?php

declare(strict_types=1);

namespace App\Services\Maps\Free;

use App\Contracts\Maps\MapRenderConfigProviderContract;
use App\DataTransferObjects\Maps\MapRenderConfig;

/**
 * Map render configuration provider for the free stack (MapLibre + OpenFreeMap).
 *
 * Returns a MapRenderConfig with provider='free', the tile style URL,
 * attribution text, and no Google-specific fields.
 */
final class FreeMapRenderConfigProvider implements MapRenderConfigProviderContract
{
    public function getRenderConfig(
        string $containerId,
        array $markers,
        array $fallbackCenter,
        bool $singleMarker,
    ): MapRenderConfig {
        return new MapRenderConfig(
            provider: 'free',
            containerId: $containerId,
            center: $fallbackCenter,
            zoom: $singleMarker ? 14 : 11,
            markers: $markers,
            clustering: ! $singleMarker,
            locale: app()->getLocale(),
            styleUrl: (string) config('maps.free.tile_style_url', 'https://tiles.openfreemap.org/styles/liberty'),
            googleApiKey: null,
            googleMapsJsUrl: null,
            attribution: (string) config('maps.free.attribution', '© OpenStreetMap contributors'),
        );
    }
}
