<?php

declare(strict_types=1);

use App\Livewire\Accountant\Dashboard;
use App\Models\PaymentAnomaly;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('accountant dashboard anomaly count', function () {
    it('shows zero anomaly count when no open anomalies', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();

        $component = Livewire::actingAs($accountant)
            ->test(Dashboard::class);

        expect($component->get('summaryOpenAnomalyCount'))->toBe(0);
    });

    it('shows open anomaly count', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        PaymentAnomaly::factory()->open()->count(3)->create();

        $component = Livewire::actingAs($accountant)
            ->test(Dashboard::class);

        expect($component->get('summaryOpenAnomalyCount'))->toBe(3);
    });

    it('does not count resolved anomalies', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        PaymentAnomaly::factory()->open()->count(2)->create();
        PaymentAnomaly::factory()->resolved()->create();
        PaymentAnomaly::factory()->ignored()->create();

        $component = Livewire::actingAs($accountant)
            ->test(Dashboard::class);

        expect($component->get('summaryOpenAnomalyCount'))->toBe(2);
    });

    it('renders the open anomaly summary label', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();

        Livewire::actingAs($accountant)
            ->test(Dashboard::class)
            ->assertSee(__('accountant.summary_open_anomalies'));
    });
});
