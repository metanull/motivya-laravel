<?php

declare(strict_types=1);

use App\Livewire\Admin\Configuration\Billing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('Admin — Billing Configuration', function () {

    it('renders for admin users', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        Livewire::actingAs($admin)
            ->test(Billing::class)
            ->assertOk()
            ->assertSee(__('admin.billing_config_heading'));
    });

    it('is forbidden for athletes', function () {
        $athlete = User::factory()->athlete()->create();

        Livewire::actingAs($athlete)
            ->test(Billing::class)
            ->assertForbidden();
    });

    it('is forbidden for coaches', function () {
        $coach = User::factory()->coach()->create();

        Livewire::actingAs($coach)
            ->test(Billing::class)
            ->assertForbidden();
    });

    it('shows plan names', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        Livewire::actingAs($admin)
            ->test(Billing::class)
            ->assertSee('Freemium')
            ->assertSee('Active')
            ->assertSee('Premium');
    });

    it('shows commission rates', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        Livewire::actingAs($admin)
            ->test(Billing::class)
            ->assertSee('30%')
            ->assertSee('20%')
            ->assertSee('10%');
    });

    it('shows subscription fees', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        // freemium is 0 (displayed as "Free")
        // active is 3900 cents = €39,00
        // premium is 7900 cents = €79,00
        Livewire::actingAs($admin)
            ->test(Billing::class)
            ->assertSee(__('admin.billing_config_free'))
            ->assertSee('39,00')
            ->assertSee('79,00');
    });

    it('does not expose Stripe secret key', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        // Temporarily seed a fake Stripe secret in config so we can assert it is absent.
        config(['services.stripe.secret' => 'sk_test_FAKESECRET_MUSTNOTAPPEAR']);

        $html = Livewire::actingAs($admin)
            ->test(Billing::class)
            ->html();

        expect($html)->not->toContain('sk_test_FAKESECRET_MUSTNOTAPPEAR');
    });

    it('is accessible via route admin.configuration.billing', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        $this->actingAs($admin)
            ->get(route('admin.configuration.billing'))
            ->assertOk();
    });

});
