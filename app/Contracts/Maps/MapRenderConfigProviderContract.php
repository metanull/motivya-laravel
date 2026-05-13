<?php

declare(strict_types=1);

namespace App\Contracts\Maps;

use App\DataTransferObjects\Maps\MapRenderConfig;

/**
 * Contract for map render configuration providers.
 *
 * Implementations return a provider-specific MapRenderConfig DTO that the
 * Blade component can encode as JSON and pass to the JS layer.
 */
interface MapRenderConfigProviderContract
{
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
        bool $singleMarker,
    ): MapRenderConfig;
}
