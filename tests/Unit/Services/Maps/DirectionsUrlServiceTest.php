<?php

declare(strict_types=1);

use App\Models\SportSession;
use App\Services\Maps\Free\FreeDirectionsUrlProvider;
use App\Services\Maps\Google\GoogleDirectionsUrlProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;

uses(RefreshDatabase::class);

describe('GoogleDirectionsUrlProvider', function (): void {

    beforeEach(function (): void {
        Config::set('maps.google.directions_base_url', 'https://www.google.com/maps/dir/');
    });

    it('builds a coordinate-based URL when session has GPS coordinates', function (): void {
        $session = SportSession::factory()->published()->create([
            'latitude' => 50.8451,
            'longitude' => 4.3569,
        ]);

        $provider = new GoogleDirectionsUrlProvider;
        $url = $provider->getUrl($session);

        expect($url)->toStartWith('https://www.google.com/maps/dir/?api=1&destination=')
            ->and($url)->toContain('50.8451')
            ->and($url)->toContain('4.3569');
    });

    it('falls back to formatted_address text when no coordinates', function (): void {
        $session = SportSession::factory()->published()->create([
            'latitude' => null,
            'longitude' => null,
            'formatted_address' => 'Rue de la Loi 16, 1000 Bruxelles',
        ]);

        $provider = new GoogleDirectionsUrlProvider;
        $url = $provider->getUrl($session);

        expect($url)->toContain('destination=')
            ->and($url)->toContain(urlencode('Rue de la Loi 16, 1000 Bruxelles'));
    });

    it('falls back to location+postal_code text when no coordinates and no formatted_address', function (): void {
        $session = SportSession::factory()->published()->create([
            'latitude' => null,
            'longitude' => null,
            'formatted_address' => null,
            'location' => 'Grand-Place',
            'postal_code' => '1000',
        ]);

        $provider = new GoogleDirectionsUrlProvider;
        $url = $provider->getUrl($session);

        expect($url)->toContain('Belgium');
    });

});

describe('FreeDirectionsUrlProvider', function (): void {

    beforeEach(function (): void {
        Config::set('maps.free.directions_base_url', 'https://www.openstreetmap.org/directions');
    });

    it('builds a coordinate-based URL when session has GPS coordinates', function (): void {
        $session = SportSession::factory()->published()->create([
            'latitude' => 50.8451,
            'longitude' => 4.3569,
        ]);

        $provider = new FreeDirectionsUrlProvider;
        $url = $provider->getUrl($session);

        expect($url)->toStartWith('https://www.openstreetmap.org/directions?route=')
            ->and($url)->toContain('50.8451')
            ->and($url)->toContain('4.3569');
    });

    it('falls back to formatted_address text when no coordinates', function (): void {
        $session = SportSession::factory()->published()->create([
            'latitude' => null,
            'longitude' => null,
            'formatted_address' => 'Rue de la Loi 16, 1000 Bruxelles',
        ]);

        $provider = new FreeDirectionsUrlProvider;
        $url = $provider->getUrl($session);

        expect($url)->toContain('?to=')
            ->and($url)->toContain(urlencode('Rue de la Loi 16, 1000 Bruxelles'));
    });

});
