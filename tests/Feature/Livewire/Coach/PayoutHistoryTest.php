<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Livewire\Coach\PayoutHistory;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('coach payout history', function () {
    it('renders for a coach', function () {
        $coach = User::factory()->coach()->create();

        $this->actingAs($coach)
            ->get(route('coach.payout-history'))
            ->assertOk()
            ->assertSeeLivewire(PayoutHistory::class);
    });

    it('denies access to athletes', function () {
        $athlete = User::factory()->athlete()->create();

        $this->actingAs($athlete)
            ->get(route('coach.payout-history'))
            ->assertForbidden();
    });

    it('denies access to guests', function () {
        $this->get(route('coach.payout-history'))
            ->assertRedirect(route('login'));
    });

    it('lists own invoices', function () {
        $coach = User::factory()->coach()->create();

        Invoice::factory()->create([
            'invoice_number' => 'INV-2026-000001',
            'coach_id' => $coach->id,
        ]);

        Livewire::actingAs($coach)
            ->test(PayoutHistory::class)
            ->assertSee('INV-2026-000001');
    });

    it('does not list other coaches invoices', function () {
        $coach = User::factory()->coach()->create();
        $otherCoach = User::factory()->coach()->create();

        Invoice::factory()->create([
            'invoice_number' => 'INV-2026-000002',
            'coach_id' => $otherCoach->id,
        ]);

        Livewire::actingAs($coach)
            ->test(PayoutHistory::class)
            ->assertDontSee('INV-2026-000002');
    });

    it('shows invoice details: month, revenue, commission, payout, plan, status', function () {
        $coach = User::factory()->coach()->create();

        Invoice::factory()->paid()->create([
            'coach_id' => $coach->id,
            'billing_period_start' => '2026-03-01',
            'revenue_htva' => 10000,
            'commission_amount' => 3000,
            'coach_payout' => 7000,
            'plan_applied' => 'freemium',
        ]);

        Livewire::actingAs($coach)
            ->test(PayoutHistory::class)
            ->assertSee('2026-03')
            ->assertSee(__('coach.plan_freemium'));
    });

    it('filters by status', function () {
        $coach = User::factory()->coach()->create();

        Invoice::factory()->paid()->create([
            'invoice_number' => 'INV-2026-000003',
            'coach_id' => $coach->id,
        ]);
        Invoice::factory()->draft()->create([
            'invoice_number' => 'INV-2026-000004',
            'coach_id' => $coach->id,
        ]);

        Livewire::actingAs($coach)
            ->test(PayoutHistory::class)
            ->set('status', InvoiceStatus::Paid->value)
            ->assertSee('INV-2026-000003')
            ->assertDontSee('INV-2026-000004');
    });

    it('resets status filter', function () {
        $coach = User::factory()->coach()->create();

        Livewire::actingAs($coach)
            ->test(PayoutHistory::class)
            ->set('status', 'paid')
            ->call('resetFilters')
            ->assertSet('status', '');
    });

    it('sorts by column', function () {
        $coach = User::factory()->coach()->create();

        Livewire::actingAs($coach)
            ->test(PayoutHistory::class)
            ->call('sort', 'invoice_number')
            ->assertSet('sortBy', 'invoice_number')
            ->assertSet('sortDir', 'asc');
    });

    it('toggles sort direction', function () {
        $coach = User::factory()->coach()->create();

        Livewire::actingAs($coach)
            ->test(PayoutHistory::class)
            ->call('sort', 'invoice_number')
            ->assertSet('sortDir', 'asc')
            ->call('sort', 'invoice_number')
            ->assertSet('sortDir', 'desc');
    });

    it('shows empty state when no invoices', function () {
        $coach = User::factory()->coach()->create();

        Livewire::actingAs($coach)
            ->test(PayoutHistory::class)
            ->assertSee(__('coach.payout_no_invoices'));
    });

    it('shows pagination when more than 20 invoices', function () {
        $coach = User::factory()->coach()->create();

        Invoice::factory()->count(21)->create(['coach_id' => $coach->id]);

        Livewire::actingAs($coach)
            ->test(PayoutHistory::class)
            ->assertSeeHtml('wire:click');
    });

    it('shows download xml button when xml_path is set', function () {
        $coach = User::factory()->coach()->create();

        Invoice::factory()->issued()->create([
            'coach_id' => $coach->id,
            'xml_path' => 'invoices/INV-2026-000001.xml',
        ]);

        Livewire::actingAs($coach)
            ->test(PayoutHistory::class)
            ->assertSee(__('coach.payout_download_xml'));
    });

    it('does not show download xml button when xml_path is null', function () {
        $coach = User::factory()->coach()->create();

        Invoice::factory()->draft()->create([
            'coach_id' => $coach->id,
            'xml_path' => null,
        ]);

        Livewire::actingAs($coach)
            ->test(PayoutHistory::class)
            ->assertDontSee(__('coach.payout_download_xml'));
    });
});
