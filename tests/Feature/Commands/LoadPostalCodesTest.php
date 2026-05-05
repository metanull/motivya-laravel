<?php

declare(strict_types=1);

use App\Models\PostalCodeCoordinate;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('geo:load-postal-codes', function () {
    it('loads postal code coordinates', function (): void {
        expect(PostalCodeCoordinate::count())->toBe(0);

        $this->artisan('geo:load-postal-codes')->assertSuccessful();

        expect(PostalCodeCoordinate::count())->toBeGreaterThan(0);
    });

    it('is idempotent (safe to run twice)', function (): void {
        $this->artisan('geo:load-postal-codes')->assertSuccessful();

        $countAfterFirst = PostalCodeCoordinate::count();

        $this->artisan('geo:load-postal-codes')->assertSuccessful();

        expect(PostalCodeCoordinate::count())->toBe($countAfterFirst);
    });
});
