<?php

declare(strict_types=1);

use App\Livewire\Session\Show;
use App\Models\SportSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('session directions button', function () {
    it('shows coordinate-based directions URL when session has coordinates', function (): void {
        $athlete = User::factory()->athlete()->create();
        $session = SportSession::factory()->published()->withCoordinates()->create();

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
});
