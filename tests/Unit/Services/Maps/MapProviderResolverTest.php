<?php

declare(strict_types=1);

use App\Enums\MapProvider;
use App\Services\Maps\MapProviderResolver;
use Illuminate\Support\Facades\Config;

describe('MapProviderResolver', function (): void {

    it('resolves to Google when api_key is a non-empty string', function (): void {
        Config::set('maps.google.api_key', 'AIzaFakeKeyForTest12345678');

        $resolver = new MapProviderResolver;

        expect($resolver->resolve())->toBe(MapProvider::Google);
    });

    it('resolves to Free when api_key is null', function (): void {
        Config::set('maps.google.api_key', null);

        $resolver = new MapProviderResolver;

        expect($resolver->resolve())->toBe(MapProvider::Free);
    });

    it('resolves to Free when api_key is an empty string', function (): void {
        Config::set('maps.google.api_key', '');

        $resolver = new MapProviderResolver;

        expect($resolver->resolve())->toBe(MapProvider::Free);
    });

    it('resolves to Free when GOOGLE_MAPS_API_KEY is not set', function (): void {
        Config::set('maps.google.api_key', null);

        expect((new MapProviderResolver)->resolve())->toBe(MapProvider::Free);
    });

    describe('validateCapabilities — Google', function (): void {

        it('returns ok for api_key_present when key is set', function (): void {
            Config::set('maps.google.api_key', 'AIzaFakeKeyForTest12345678');

            $results = (new MapProviderResolver)->validateCapabilities();
            $byCapability = collect($results)->keyBy('capability');

            expect($byCapability->get('api_key_present')['status'])->toBe('ok');
        });

        it('returns ok for api_key_format when key starts with AIza', function (): void {
            Config::set('maps.google.api_key', 'AIzaFakeKeyForTest12345678');

            $results = (new MapProviderResolver)->validateCapabilities();
            $byCapability = collect($results)->keyBy('capability');

            expect($byCapability->get('api_key_format')['status'])->toBe('ok');
        });

        it('returns fail for api_key_format when key does not start with AIza', function (): void {
            Config::set('maps.google.api_key', 'bad-key-value');

            $results = (new MapProviderResolver)->validateCapabilities();
            $byCapability = collect($results)->keyBy('capability');

            expect($byCapability->get('api_key_format')['status'])->toBe('fail');
        });

    });

    describe('validateCapabilities — Free', function (): void {

        beforeEach(function (): void {
            Config::set('maps.google.api_key', null);
        });

        it('returns ok for tile_style_url when configured', function (): void {
            Config::set('maps.free.tile_style_url', 'https://tiles.openfreemap.org/styles/liberty');

            $results = (new MapProviderResolver)->validateCapabilities();
            $byCapability = collect($results)->keyBy('capability');

            expect($byCapability->get('tile_style_url')['status'])->toBe('ok');
        });

        it('returns fail for tile_style_url when empty', function (): void {
            Config::set('maps.free.tile_style_url', '');

            $results = (new MapProviderResolver)->validateCapabilities();
            $byCapability = collect($results)->keyBy('capability');

            expect($byCapability->get('tile_style_url')['status'])->toBe('fail');
        });

        it('returns ok for geocoding_url when configured', function (): void {
            Config::set('maps.free.tile_style_url', 'https://tiles.openfreemap.org/styles/liberty');
            Config::set('maps.free.geocoding_base_url', 'https://nominatim.openstreetmap.org/search');

            $results = (new MapProviderResolver)->validateCapabilities();
            $byCapability = collect($results)->keyBy('capability');

            expect($byCapability->get('geocoding_url')['status'])->toBe('ok');
        });

    });

});
