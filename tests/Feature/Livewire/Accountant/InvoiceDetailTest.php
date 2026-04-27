<?php

declare(strict_types=1);

use App\Livewire\Accountant\InvoiceDetail;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('accountant invoice detail', function () {
    it('renders for an accountant with 2FA', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $invoice = Invoice::factory()->create();

        $this->actingAs($accountant)
            ->get(route('accountant.invoices.show', $invoice))
            ->assertOk()
            ->assertSeeLivewire(InvoiceDetail::class);
    });

    it('redirects accountant without 2FA', function () {
        $accountant = User::factory()->accountant()->create();
        $invoice = Invoice::factory()->create();

        $this->actingAs($accountant)
            ->get(route('accountant.invoices.show', $invoice))
            ->assertRedirect(route('profile.edit'));
    });

    it('renders for an admin with 2FA', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $invoice = Invoice::factory()->create();

        $this->actingAs($admin)
            ->get(route('accountant.invoices.show', $invoice))
            ->assertOk()
            ->assertSeeLivewire(InvoiceDetail::class);
    });

    it('denies access to coaches', function () {
        $coach = User::factory()->coach()->create();
        $invoice = Invoice::factory()->create();

        $this->actingAs($coach)
            ->get(route('accountant.invoices.show', $invoice))
            ->assertForbidden();
    });

    it('denies access to athletes', function () {
        $athlete = User::factory()->athlete()->create();
        $invoice = Invoice::factory()->create();

        $this->actingAs($athlete)
            ->get(route('accountant.invoices.show', $invoice))
            ->assertForbidden();
    });

    it('denies access to guests', function () {
        $invoice = Invoice::factory()->create();

        $this->get(route('accountant.invoices.show', $invoice))
            ->assertRedirect(route('login'));
    });

    it('shows the invoice number', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $invoice = Invoice::factory()->create(['invoice_number' => 'INV-2026-000001']);

        Livewire::actingAs($accountant)
            ->test(InvoiceDetail::class, ['invoice' => $invoice])
            ->assertSee('INV-2026-000001');
    });

    it('shows the coach name', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create(['name' => 'Alice Coach']);
        $invoice = Invoice::factory()->create(['coach_id' => $coach->id]);

        Livewire::actingAs($accountant)
            ->test(InvoiceDetail::class, ['invoice' => $invoice])
            ->assertSee('Alice Coach');
    });

    it('shows all breakdown fields', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $invoice = Invoice::factory()->create([
            'revenue_ttc' => 30000,
            'revenue_htva' => 24793,
            'stripe_fee' => 450,
            'subscription_fee' => 0,
            'commission_amount' => 7438,
            'coach_payout' => 16905,
            'platform_margin' => 7438,
            'plan_applied' => 'freemium',
        ]);

        Livewire::actingAs($accountant)
            ->test(InvoiceDetail::class, ['invoice' => $invoice])
            ->assertSeeHtml('300,00')   // revenue TTC formatted
            ->assertSee('freemium');
    });

    it('has no discrepancies for a correctly calculated invoice', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();

        $revenueTtc = 30000;
        $revenueHtva = intdiv($revenueTtc * 100 + 60, 121); // 24793
        $stripeFee = (int) round($revenueTtc * 15 / 1000); // 450
        $commissionAmount = (int) round($revenueHtva * 30 / 100); // 7438
        $coachPayout = $revenueHtva - $commissionAmount - $stripeFee; // 16905

        $invoice = Invoice::factory()->create([
            'revenue_ttc' => $revenueTtc,
            'revenue_htva' => $revenueHtva,
            'stripe_fee' => $stripeFee,
            'subscription_fee' => 0,
            'commission_amount' => $commissionAmount,
            'coach_payout' => $coachPayout,
            'platform_margin' => $commissionAmount,
            'plan_applied' => 'freemium',
        ]);

        $component = Livewire::actingAs($accountant)
            ->test(InvoiceDetail::class, ['invoice' => $invoice]);

        expect($component->instance()->hasDiscrepancies)->toBeFalse();
        $component->assertDontSee(__('accountant.detail_discrepancy_title'));
    });

    it('flags a discrepancy when stripe_fee is wrong', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();

        $invoice = Invoice::factory()->create([
            'revenue_ttc' => 30000,
            'stripe_fee' => 999,   // correct would be 450
            'plan_applied' => 'freemium',
        ]);

        $component = Livewire::actingAs($accountant)
            ->test(InvoiceDetail::class, ['invoice' => $invoice]);

        expect($component->instance()->hasDiscrepancies)->toBeTrue();
        $component->assertSee(__('accountant.detail_discrepancy_title'));
    });

    it('flags a discrepancy when commission_amount is wrong', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();

        $revenueTtc = 30000;
        $revenueHtva = intdiv($revenueTtc * 100 + 60, 121);

        $invoice = Invoice::factory()->create([
            'revenue_ttc' => $revenueTtc,
            'revenue_htva' => $revenueHtva,
            'commission_amount' => 9999,  // wrong value
            'plan_applied' => 'freemium',
        ]);

        $component = Livewire::actingAs($accountant)
            ->test(InvoiceDetail::class, ['invoice' => $invoice]);

        expect($component->instance()->discrepancies['commission_amount'])->toBeTrue();
    });

    it('reports no discrepancies for active plan with correct values', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();

        $revenueTtc = 50000;
        $revenueHtva = intdiv($revenueTtc * 100 + 60, 121);
        $stripeFee = (int) round($revenueTtc * 15 / 1000);
        $subscriptionFee = 3900;
        $commissionAmount = (int) round($revenueHtva * 20 / 100);
        $coachPayout = $revenueHtva - $commissionAmount - $stripeFee - $subscriptionFee;

        $invoice = Invoice::factory()->create([
            'revenue_ttc' => $revenueTtc,
            'revenue_htva' => $revenueHtva,
            'stripe_fee' => $stripeFee,
            'subscription_fee' => $subscriptionFee,
            'commission_amount' => $commissionAmount,
            'coach_payout' => $coachPayout,
            'platform_margin' => $commissionAmount,
            'plan_applied' => 'active',
        ]);

        $component = Livewire::actingAs($accountant)
            ->test(InvoiceDetail::class, ['invoice' => $invoice]);

        expect($component->instance()->hasDiscrepancies)->toBeFalse();
    });

    it('is read-only (has no editable inputs)', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $invoice = Invoice::factory()->create();

        Livewire::actingAs($accountant)
            ->test(InvoiceDetail::class, ['invoice' => $invoice])
            ->assertDontSeeHtml('<input')
            ->assertDontSeeHtml('<textarea');
    });
});
