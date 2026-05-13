<?php

declare(strict_types=1);

use App\Contracts\Maps\HealthProbeProviderContract;
use App\Enums\MapProvider;
use App\Services\Maps\MapProviderHealthService;
use App\Services\Maps\MapProviderResolver;
use Illuminate\Support\Facades\Config;

describe('MapProviderHealthService', function (): void {
    /**
     * Create a mock HealthProbeProviderContract that returns the given probes.
     *
     * @param  array<int, array{capability: string, status: 'ok'|'fail', message: string}>  $probes
     */
    function makeProbe(array $probes): HealthProbeProviderContract
    {
        $mock = Mockery::mock(HealthProbeProviderContract::class);
        $mock->shouldReceive('probe')->andReturn($probes);

        return $mock;
    }

    /**
     * Build a MapProviderHealthService with a real resolver using Config::set().
     *
     * @param  array<int, array{capability: string, status: 'ok'|'fail', message: string}>  $probes
     */
    function makeService(MapProvider $provider, array $probes): MapProviderHealthService
    {
        // Drive the real resolver via config — no mock needed since MapProviderResolver is final.
        if ($provider === MapProvider::Google) {
            Config::set('maps.google.api_key', 'AIzaFakeTestKey');
        } else {
            Config::set('maps.google.api_key', null);
        }

        // Forget the singleton so it re-resolves with the new config.
        app()->forgetInstance(MapProviderResolver::class);
        $resolver = app(MapProviderResolver::class);

        return new MapProviderHealthService($resolver, makeProbe($probes));
    }

    it('returns ok status when all probes pass for Free provider', function (): void {
        $service = makeService(MapProvider::Free, [
            ['capability' => 'tile_style_url', 'status' => 'ok', 'message' => 'OK'],
            ['capability' => 'geocoding_url', 'status' => 'ok', 'message' => 'OK'],
        ]);

        $summary = $service->summary();

        expect($summary['status'])->toBe('ok')
            ->and($summary['message'])->toContain('Free stack');
    });

    it('returns ok with Google Maps Platform message when Google provider is active and all probes pass', function (): void {
        $service = makeService(MapProvider::Google, [
            ['capability' => 'api_key_present', 'status' => 'ok', 'message' => 'OK'],
            ['capability' => 'api_key_format', 'status' => 'ok', 'message' => 'OK'],
        ]);

        $summary = $service->summary();

        expect($summary['status'])->toBe('ok')
            ->and($summary['message'])->toContain('Google Maps Platform');
    });

    it('returns fail status when Google api_key_present probe fails', function (): void {
        $service = makeService(MapProvider::Google, [
            ['capability' => 'api_key_present', 'status' => 'fail', 'message' => 'Google API key is not set'],
            ['capability' => 'api_key_format', 'status' => 'fail', 'message' => 'Key format invalid'],
        ]);

        $summary = $service->summary();

        expect($summary['status'])->toBe('fail')
            ->and($summary['message'])->toContain('Google API key is not set');
    });

    it('returns warn status when a non-api_key_present probe fails for Google', function (): void {
        $service = makeService(MapProvider::Google, [
            ['capability' => 'api_key_present', 'status' => 'ok', 'message' => 'OK'],
            ['capability' => 'api_key_format', 'status' => 'fail', 'message' => 'Key does not start with AIza'],
        ]);

        $summary = $service->summary();

        expect($summary['status'])->toBe('warn')
            ->and($summary['message'])->toContain('Key does not start with AIza');
    });

    it('returns warn status when a Free probe fails', function (): void {
        $service = makeService(MapProvider::Free, [
            ['capability' => 'tile_style_url', 'status' => 'fail', 'message' => 'Tile style URL is not configured'],
            ['capability' => 'geocoding_url', 'status' => 'ok', 'message' => 'OK'],
        ]);

        $summary = $service->summary();

        expect($summary['status'])->toBe('warn')
            ->and($summary['message'])->toContain('Tile style URL is not configured');
    });

    it('activeProviderName() returns google when api key is set', function (): void {
        $service = makeService(MapProvider::Google, []);

        expect($service->activeProviderName())->toBe('google');
    });

    it('activeProviderName() returns free when api key is absent', function (): void {
        $service = makeService(MapProvider::Free, []);

        expect($service->activeProviderName())->toBe('free');
    });

    it('probe() delegates to the HealthProbeProviderContract', function (): void {
        $expectedProbes = [
            ['capability' => 'tile_style_url', 'status' => 'ok', 'message' => 'OK'],
        ];
        $service = makeService(MapProvider::Free, $expectedProbes);

        expect($service->probe())->toBe($expectedProbes);
    });
});
