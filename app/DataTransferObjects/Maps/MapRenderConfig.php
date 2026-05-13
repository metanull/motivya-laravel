<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Maps;

/**
 * Immutable configuration DTO for rendering a map component.
 *
 * All rendering decisions are encoded here so that the Blade template
 * and JavaScript layer can be provider-neutral.
 */
final class MapRenderConfig
{
    /**
     * @param  string  $provider  'google' or 'free'
     * @param  string  $containerId  DOM element ID for the map container
     * @param  array{0: float, 1: float}  $center  [lng, lat] fallback centre
     * @param  int  $zoom  Initial zoom level
     * @param  array<int,array<string,mixed>>  $markers  Session marker objects
     * @param  bool  $clustering  Whether to cluster nearby markers
     * @param  string  $locale  BCP-47 locale passed to the provider
     * @param  ?string  $styleUrl  MapLibre style URL (free provider only)
     * @param  ?string  $googleApiKey  Google Maps API key (google provider only)
     * @param  ?string  $googleMapsJsUrl  Google Maps JS API URL (google provider only)
     * @param  ?string  $attribution  Attribution text (free provider only)
     */
    public function __construct(
        public readonly string $provider,
        public readonly string $containerId,
        public readonly array $center,
        public readonly int $zoom,
        public readonly array $markers,
        public readonly bool $clustering,
        public readonly string $locale,
        public readonly ?string $styleUrl,
        public readonly ?string $googleApiKey,
        public readonly ?string $googleMapsJsUrl,
        public readonly ?string $attribution,
    ) {}

    /**
     * Serialize to a plain array suitable for JSON encoding into the DOM.
     *
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'provider' => $this->provider,
            'containerId' => $this->containerId,
            'center' => $this->center,
            'zoom' => $this->zoom,
            'markers' => $this->markers,
            'clustering' => $this->clustering,
            'locale' => $this->locale,
            'styleUrl' => $this->styleUrl,
            'googleApiKey' => $this->googleApiKey,
            'googleMapsJsUrl' => $this->googleMapsJsUrl,
            'attribution' => $this->attribution,
        ];
    }
}
