<?php

declare(strict_types=1);

use App\Models\PostalCodeCoordinate;
use App\Services\PostalCodeCoordinateService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('PostalCodeCoordinateService', function () {

    it('returns coordinates for a known postal code', function (): void {
        PostalCodeCoordinate::create([
            'postal_code' => '1000',
            'municipality' => 'Bruxelles/Brussel',
            'latitude' => 50.8503000,
            'longitude' => 4.3517000,
        ]);

        $service = app(PostalCodeCoordinateService::class);
        $result = $service->resolveCoordinates('1000');

        expect($result)->toBeArray()
            ->and($result[0])->toEqual(50.8503)
            ->and($result[1])->toEqual(4.3517);
    });

    it('returns null for an unknown postal code', function (): void {
        $service = app(PostalCodeCoordinateService::class);
        $result = $service->resolveCoordinates('9999');

        expect($result)->toBeNull();
    });

});
