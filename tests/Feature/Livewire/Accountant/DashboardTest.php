<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Livewire\Accountant\Dashboard;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('accountant dashboard', function () {
    it('renders for an accountant with 2FA', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();

        $this->actingAs($accountant)
            ->get(route('accountant.dashboard'))
            ->assertOk()
            ->assertSeeLivewire(Dashboard::class);
    });

    it('redirects accountant without 2FA', function () {
        $accountant = User::factory()->accountant()->create();

        $this->actingAs($accountant)
            ->get(route('accountant.dashboard'))
            ->assertRedirect(route('profile.edit'));
    });

    it('renders for an admin with 2FA', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        $this->actingAs($admin)
            ->get(route('accountant.dashboard'))
            ->assertOk()
            ->assertSeeLivewire(Dashboard::class);
    });

    it('denies access to coaches', function () {
        $coach = User::factory()->coach()->create();

        $this->actingAs($coach)
            ->get(route('accountant.dashboard'))
            ->assertForbidden();
    });

    it('denies access to athletes', function () {
        $athlete = User::factory()->athlete()->create();

        $this->actingAs($athlete)
            ->get(route('accountant.dashboard'))
            ->assertForbidden();
    });

    it('denies access to guests', function () {
        $this->get(route('accountant.dashboard'))
            ->assertRedirect(route('login'));
    });

    it('lists invoices and credit notes', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();

        Invoice::factory()->create([
            'invoice_number' => 'INV-2026-000001',
            'coach_id' => $coach->id,
        ]);

        Livewire::actingAs($accountant)
            ->test(Dashboard::class)
            ->assertSee('INV-2026-000001');
    });

    it('filters by type invoice', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();

        Invoice::factory()->invoice()->create([
            'invoice_number' => 'INV-2026-000001',
            'coach_id' => $coach->id,
        ]);
        Invoice::factory()->creditNote()->create([
            'invoice_number' => 'CN-2026-000001',
            'coach_id' => $coach->id,
        ]);

        Livewire::actingAs($accountant)
            ->test(Dashboard::class)
            ->set('type', InvoiceType::Invoice->value)
            ->assertSee('INV-2026-000001')
            ->assertDontSee('CN-2026-000001');
    });

    it('filters by type credit note', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();

        Invoice::factory()->invoice()->create([
            'invoice_number' => 'INV-2026-000002',
            'coach_id' => $coach->id,
        ]);
        Invoice::factory()->creditNote()->create([
            'invoice_number' => 'CN-2026-000002',
            'coach_id' => $coach->id,
        ]);

        Livewire::actingAs($accountant)
            ->test(Dashboard::class)
            ->set('type', InvoiceType::CreditNote->value)
            ->assertSee('CN-2026-000002')
            ->assertDontSee('INV-2026-000002');
    });

    it('filters by status', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();

        Invoice::factory()->paid()->create([
            'invoice_number' => 'INV-2026-000003',
            'coach_id' => $coach->id,
        ]);
        Invoice::factory()->draft()->create([
            'invoice_number' => 'INV-2026-000004',
            'coach_id' => $coach->id,
        ]);

        Livewire::actingAs($accountant)
            ->test(Dashboard::class)
            ->set('status', InvoiceStatus::Paid->value)
            ->assertSee('INV-2026-000003')
            ->assertDontSee('INV-2026-000004');
    });

    it('filters by coach', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $coachA = User::factory()->coach()->create();
        $coachB = User::factory()->coach()->create();

        Invoice::factory()->create([
            'invoice_number' => 'INV-2026-000005',
            'coach_id' => $coachA->id,
        ]);
        Invoice::factory()->create([
            'invoice_number' => 'INV-2026-000006',
            'coach_id' => $coachB->id,
        ]);

        Livewire::actingAs($accountant)
            ->test(Dashboard::class)
            ->set('coachId', (string) $coachA->id)
            ->assertSee('INV-2026-000005')
            ->assertDontSee('INV-2026-000006');
    });

    it('filters by date range', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();

        Invoice::factory()->create([
            'invoice_number' => 'INV-2026-000007',
            'coach_id' => $coach->id,
            'billing_period_start' => '2026-03-01',
            'billing_period_end' => '2026-03-31',
        ]);
        Invoice::factory()->create([
            'invoice_number' => 'INV-2026-000008',
            'coach_id' => $coach->id,
            'billing_period_start' => '2026-06-01',
            'billing_period_end' => '2026-06-30',
        ]);

        Livewire::actingAs($accountant)
            ->test(Dashboard::class)
            ->set('dateFrom', '2026-03-01')
            ->set('dateTo', '2026-04-30')
            ->assertSee('INV-2026-000007')
            ->assertDontSee('INV-2026-000008');
    });

    it('resets filters', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();

        $component = Livewire::actingAs($accountant)
            ->test(Dashboard::class)
            ->set('dateFrom', '2026-01-01')
            ->set('dateTo', '2026-12-31')
            ->set('coachId', '99')
            ->set('type', 'invoice')
            ->set('status', 'paid')
            ->call('resetFilters');

        $component
            ->assertSet('dateFrom', '')
            ->assertSet('dateTo', '')
            ->assertSet('coachId', '')
            ->assertSet('type', '')
            ->assertSet('status', '');
    });

    it('sorts by column', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();

        Livewire::actingAs($accountant)
            ->test(Dashboard::class)
            ->call('sort', 'invoice_number')
            ->assertSet('sortBy', 'invoice_number')
            ->assertSet('sortDir', 'asc');
    });

    it('toggles sort direction', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();

        Livewire::actingAs($accountant)
            ->test(Dashboard::class)
            ->call('sort', 'invoice_number')
            ->assertSet('sortDir', 'asc')
            ->call('sort', 'invoice_number')
            ->assertSet('sortDir', 'desc');
    });

    it('shows no transactions empty state', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();

        Livewire::actingAs($accountant)
            ->test(Dashboard::class)
            ->assertSee(__('accountant.no_transactions'));
    });

    it('shows pagination', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();

        Invoice::factory()->count(21)->create(['coach_id' => $coach->id]);

        Livewire::actingAs($accountant)
            ->test(Dashboard::class)
            ->assertSeeHtml('wire:click');
    });
});
