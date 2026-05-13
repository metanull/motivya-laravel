<?php

declare(strict_types=1);

use App\DataTransferObjects\Maps\MapRenderConfig;
use App\Services\Maps\Free\FreeMapRenderConfigProvider;
use App\Services\Maps\Google\GoogleMapRenderConfigProvider;
use Illuminate\Support\Facades\Config;

// ---------------------------------------------------------------------------
// GoogleMapRenderConfigProvider
// ---------------------------------------------------------------------------

describe('GoogleMapRenderConfigProvider', function (): void {
    beforeEach(function (): void {
        Config::set('maps.google.api_key', 'AIzaTestKey123');
        Config::set('maps.google.maps_js_url', 'https://maps.googleapis.com/maps/api/js');
    });

    it('returns a MapRenderConfig with provider=google', function (): void {
        $provider = new GoogleMapRenderConfigProvider;
        $config = $provider->getRenderConfig(
            containerId: 'map',
            markers: [],
            fallbackCenter: [50.8503, 4.3517],
            singleMarker: false,
        );

        expect($config)->toBeInstanceOf(MapRenderConfig::class)
            ->and($config->provider)->toBe('google')
            ->and($config->googleApiKey)->toBe('AIzaTestKey123')
            ->and($config->googleMapsJsUrl)->toBe('https://maps.googleapis.com/maps/api/js')
            ->and($config->styleUrl)->toBeNull()
            ->and($config->attribution)->toBeNull();
    });

    it('sets zoom=14 for single-marker mode', function (): void {
        $provider = new GoogleMapRenderConfigProvider;
        $config = $provider->getRenderConfig(
            containerId: 'detail-map',
            markers: [['id' => 1, 'latitude' => 50.85, 'longitude' => 4.35]],
            fallbackCenter: [50.85, 4.35],
            singleMarker: true,
        );

        expect($config->zoom)->toBe(14)
            ->and($config->clustering)->toBeFalse();
    });

    it('sets zoom=11 and clustering=true for multi-marker mode', function (): void {
        $provider = new GoogleMapRenderConfigProvider;
        $config = $provider->getRenderConfig(
            containerId: 'index-map',
            markers: [
                ['id' => 1, 'latitude' => 50.85, 'longitude' => 4.35],
                ['id' => 2, 'latitude' => 50.86, 'longitude' => 4.36],
            ],
            fallbackCenter: [50.8503, 4.3517],
            singleMarker: false,
        );

        expect($config->zoom)->toBe(11)
            ->and($config->clustering)->toBeTrue();
    });

    it('includes passed markers in the config', function (): void {
        $markers = [
            ['id' => 1, 'title' => 'Test Session', 'latitude' => 50.85, 'longitude' => 4.35],
        ];

        $provider = new GoogleMapRenderConfigProvider;
        $config = $provider->getRenderConfig(
            containerId: 'map',
            markers: $markers,
            fallbackCenter: [50.8503, 4.3517],
            singleMarker: true,
        );

        expect($config->markers)->toHaveCount(1)
            ->and($config->markers[0]['title'])->toBe('Test Session');
    });

    it('toArray() contains all expected keys', function (): void {
        $provider = new GoogleMapRenderConfigProvider;
        $config = $provider->getRenderConfig(
            containerId: 'map',
            markers: [],
            fallbackCenter: [50.8503, 4.3517],
            singleMarker: false,
        );

        $array = $config->toArray();

        expect($array)->toHaveKeys([
            'provider', 'containerId', 'center', 'zoom', 'markers',
            'clustering', 'locale', 'styleUrl', 'googleApiKey',
            'googleMapsJsUrl', 'attribution',
        ]);
    });
});

// ---------------------------------------------------------------------------
// FreeMapRenderConfigProvider
// ---------------------------------------------------------------------------

describe('FreeMapRenderConfigProvider', function (): void {
    beforeEach(function (): void {
        Config::set('maps.free.tile_style_url', 'https://tiles.openfreemap.org/styles/liberty');
        Config::set('maps.free.attribution', '© OpenStreetMap contributors');
    });

    it('returns a MapRenderConfig with provider=free', function (): void {
        $provider = new FreeMapRenderConfigProvider;
        $config = $provider->getRenderConfig(
            containerId: 'map',
            markers: [],
            fallbackCenter: [50.8503, 4.3517],
            singleMarker: false,
        );

        expect($config)->toBeInstanceOf(MapRenderConfig::class)
            ->and($config->provider)->toBe('free')
            ->and($config->styleUrl)->toBe('https://tiles.openfreemap.org/styles/liberty')
            ->and($config->attribution)->toBe('© OpenStreetMap contributors')
            ->and($config->googleApiKey)->toBeNull()
            ->and($config->googleMapsJsUrl)->toBeNull();
    });

    it('sets zoom=14 for single-marker mode', function (): void {
        $provider = new FreeMapRenderConfigProvider;
        $config = $provider->getRenderConfig(
            containerId: 'detail-map',
            markers: [['id' => 1, 'latitude' => 50.85, 'longitude' => 4.35]],
            fallbackCenter: [50.85, 4.35],
            singleMarker: true,
        );

        expect($config->zoom)->toBe(14)
            ->and($config->clustering)->toBeFalse();
    });

    it('sets zoom=11 and clustering=true for multi-marker mode', function (): void {
        $provider = new FreeMapRenderConfigProvider;
        $config = $provider->getRenderConfig(
            containerId: 'index-map',
            markers: [],
            fallbackCenter: [50.8503, 4.3517],
            singleMarker: false,
        );

        expect($config->zoom)->toBe(11)
            ->and($config->clustering)->toBeTrue();
    });

    it('uses default tile_style_url when config key is absent', function (): void {
        // Remove tile_style_url from the maps.free config array entirely so that
        // config('maps.free.tile_style_url', 'default') returns the default value.
        $freeConfig = config('maps.free');
        unset($freeConfig['tile_style_url']);
        Config::set('maps.free', $freeConfig);

        $provider = new FreeMapRenderConfigProvider;
        $config = $provider->getRenderConfig(
            containerId: 'map',
            markers: [],
            fallbackCenter: [50.8503, 4.3517],
            singleMarker: false,
        );

        expect($config->styleUrl)->toBe('https://tiles.openfreemap.org/styles/liberty');
    });
});
