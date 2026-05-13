<?php

declare(strict_types=1);

use App\Livewire\Session\Show;
use App\Models\SportSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('session directions button', function () {

    beforeEach(function (): void {
        // Force Google provider so URL assertions match google.com/maps/dir patterns.
        Config::set('maps.google.api_key', 'AIzaFakeKeyForTest12345678');
    });

    it('shows coordinate-based directions URL when session has coordinates but no validated address', function (): void {
        $athlete = User::factory()->athlete()->create();
        // withCoordinates() sets lat/lng without formatted_address/geocoding_provider,
        // so hasValidatedAddress() is false — the second branch is taken.
        $session = SportSession::factory()->published()->withCoordinates()->create();

        Livewire::actingAs($athlete)
            ->test(Show::class, ['sportSession' => $session])
            ->assertSeeHtml('google.com/maps/dir/?api=1&amp;destination='.$session->latitude.','.$session->longitude);
    });

    it('shows precise coordinate-based directions URL when session has a validated address', function (): void {
        $athlete = User::factory()->athlete()->create();
        // withValidatedAddress() sets formatted_address + geocoding_provider + lat/lng,
        // so hasValidatedAddress() is true — the first (most-precise) branch is taken.
        $session = SportSession::factory()->published()->withValidatedAddress()->create();

        Livewire::actingAs($athlete)
            ->test(Show::class, ['sportSession' => $session])
            ->assertSeeHtml('google.com/maps/dir/?api=1&amp;destination='.$session->latitude.','.$session->longitude);
    });

    it('shows address-based directions URL when session has no coordinates', function (): void {
        $athlete = User::factory()->athlete()->create();
        $session = SportSession::factory()->published()->create([
            'latitude' => null,
            'longitude' => null,
            'location' => 'Parc du Cinquantenaire',
            'postal_code' => '1000',
        ]);

        Livewire::actingAs($athlete)
            ->test(Show::class, ['sportSession' => $session])
            ->assertSeeHtml('google.com/maps/dir/?api=1&amp;destination=')
            ->assertSeeHtml('Belgium');
    });

    it('uses formatted_address as text destination when present but no coordinates', function (): void {
        $athlete = User::factory()->athlete()->create();
        $formattedAddress = 'Rue de la Loi 16, 1000 Bruxelles, Belgique';
        $session = SportSession::factory()->published()->create([
            'latitude' => null,
            'longitude' => null,
            'formatted_address' => $formattedAddress,
            'location' => 'Rue de la Loi',
            'postal_code' => '1000',
        ]);

        // Blade uses urlencode() (spaces → +, commas → %2C) in the href attribute.
        Livewire::actingAs($athlete)
            ->test(Show::class, ['sportSession' => $session])
            ->assertSeeHtml('destination='.urlencode($formattedAddress));
    });
});
