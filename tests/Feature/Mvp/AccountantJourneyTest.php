<?php

declare(strict_types=1);

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('MVP Accountant Journey', function () {

    it('can reach the accountant dashboard with 2FA', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();

        $this->actingAs($accountant)
            ->get(route('accountant.dashboard'))
            ->assertOk();
    });

    it('can reach the transactions page', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();

        $this->actingAs($accountant)
            ->get(route('accountant.transactions.index'))
            ->assertOk();
    });

    it('can reach the payout statements page', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();

        $this->actingAs($accountant)
            ->get(route('accountant.payout-statements.index'))
            ->assertOk();
    });

    it('can reach the anomalies page', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();

        $this->actingAs($accountant)
            ->get(route('accountant.anomalies.index'))
            ->assertOk();
    });

    it('can view an invoice detail', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();
        $invoice = Invoice::factory()->draft()->create(['coach_id' => $coach->id]);

        $this->actingAs($accountant)
            ->get(route('accountant.invoices.show', $invoice))
            ->assertOk();
    });

    it('cannot access the admin dashboard', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();

        $this->actingAs($accountant)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    });

    it('cannot access the coach dashboard', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();

        $this->actingAs($accountant)
            ->get(route('coach.dashboard'))
            ->assertForbidden();
    });

    it('cannot access the athlete dashboard', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();

        $this->actingAs($accountant)
            ->get(route('athlete.dashboard'))
            ->assertForbidden();
    });

    it('redirects accountant without 2FA to profile page', function () {
        $accountant = User::factory()->accountant()->create();

        $this->actingAs($accountant)
            ->get(route('accountant.dashboard'))
            ->assertRedirect(route('profile.edit'));
    });

});
