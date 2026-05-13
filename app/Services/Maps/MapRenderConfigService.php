<?php

declare(strict_types=1);

namespace App\Services\Maps;

use App\Contracts\Maps\MapRenderConfigProviderContract;
use App\DataTransferObjects\Maps\MapRenderConfig;

/**
 * Facade service for building map render configurations.
 *
 * Delegates to the active provider (Google or Free) via the bound
 * MapRenderConfigProviderContract. The binding is resolved at construction time
 * by AppServiceProvider based on GOOGLE_MAPS_API_KEY presence.
 */
final class MapRenderConfigService
{
    public function __construct(
        private readonly MapRenderConfigProviderContract $provider,
    ) {}

    /**
     * Build a MapRenderConfig for the given container and marker set.
     *
     * @param  string  $containerId  DOM element ID
     * @param  array<int,array<string,mixed>>  $markers
     * @param  array{0: float, 1: float}  $fallbackCenter  [lng, lat]
     * @param  bool  $singleMarker  Whether this is a single-marker detail view
     */
    public function getRenderConfig(
        string $containerId,
        array $markers,
        array $fallbackCenter,
        bool $singleMarker = false,
    ): MapRenderConfig {
        return $this->provider->getRenderConfig($containerId, $markers, $fallbackCenter, $singleMarker);
    }
}
