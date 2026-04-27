<?php

declare(strict_types=1);

use App\Enums\InvoiceType;
use App\Livewire\Accountant\Dashboard;
use App\Models\Invoice;
use App\Models\User;
use App\Services\FinancialExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('FinancialExportService', function () {
    it('builds query with no filters and returns all invoices', function () {
        $coach = User::factory()->coach()->create();
        Invoice::factory()->count(3)->for($coach, 'coach')->create();

        $service = app(FinancialExportService::class);
        $results = $service->buildQuery()->get();

        expect($results)->toHaveCount(3);
    });

    it('filters by date range', function () {
        $coach = User::factory()->coach()->create();
        Invoice::factory()->for($coach, 'coach')->create([
            'billing_period_start' => '2026-03-01',
            'billing_period_end' => '2026-03-31',
        ]);
        Invoice::factory()->for($coach, 'coach')->create([
            'billing_period_start' => '2026-06-01',
            'billing_period_end' => '2026-06-30',
        ]);

        $service = app(FinancialExportService::class);
        $results = $service->buildQuery('2026-03-01', '2026-04-30')->get();

        expect($results)->toHaveCount(1);
        expect($results->first()->billing_period_start->format('Y-m-d'))->toBe('2026-03-01');
    });

    it('filters by coach id', function () {
        $coachA = User::factory()->coach()->create();
        $coachB = User::factory()->coach()->create();
        Invoice::factory()->for($coachA, 'coach')->create();
        Invoice::factory()->for($coachB, 'coach')->create();

        $service = app(FinancialExportService::class);
        $results = $service->buildQuery('', '', (string) $coachA->id)->get();

        expect($results)->toHaveCount(1);
        expect($results->first()->coach_id)->toBe($coachA->id);
    });

    it('filters by invoice type', function () {
        $coach = User::factory()->coach()->create();
        Invoice::factory()->invoice()->for($coach, 'coach')->create(['invoice_number' => 'INV-2026-000001']);
        Invoice::factory()->creditNote()->for($coach, 'coach')->create(['invoice_number' => 'CN-2026-000001']);

        $service = app(FinancialExportService::class);
        $results = $service->buildQuery('', '', '', InvoiceType::Invoice->value)->get();

        expect($results)->toHaveCount(1);
        expect($results->first()->invoice_number)->toBe('INV-2026-000001');
    });

    it('exports invoices as CSV with correct headers and data', function () {
        $coach = User::factory()->coach()->create(['name' => 'Test Coach']);
        Invoice::factory()->for($coach, 'coach')->create([
            'invoice_number' => 'INV-2026-000001',
            'type' => InvoiceType::Invoice->value,
            'revenue_ttc' => 10000,
            'revenue_htva' => 8264,
            'vat_amount' => 1736,
            'stripe_fee' => 150,
            'subscription_fee' => 0,
            'commission_amount' => 2479,
            'coach_payout' => 5785,
            'platform_margin' => 2479,
            'plan_applied' => 'freemium',
            'tax_category_code' => 'S',
            'billing_period_start' => '2026-03-01',
            'billing_period_end' => '2026-03-31',
        ]);

        $service = app(FinancialExportService::class);
        $response = $service->exportCsv();

        expect($response->headers->get('Content-Type'))->toContain('text/csv');
        expect($response->headers->get('Content-Disposition'))->toContain('attachment');
        expect($response->headers->get('Content-Disposition'))->toContain('.csv');

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        expect($content)->toContain('invoice_number');
        expect($content)->toContain('INV-2026-000001');
        expect($content)->toContain('Test Coach');
        // revenue_ttc = 10000 cents = 100.00 EUR
        expect($content)->toContain('100');
        // coach_payout = 5785 cents = 57.85 EUR
        expect($content)->toContain('57.85');
    });

    it('exports invoices as Excel with correct headers', function () {
        $coach = User::factory()->coach()->create(['name' => 'Excel Coach']);
        Invoice::factory()->for($coach, 'coach')->create([
            'invoice_number' => 'INV-2026-000002',
        ]);

        $service = app(FinancialExportService::class);
        $response = $service->exportExcel();

        expect($response->headers->get('Content-Type'))->toContain(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
        expect($response->headers->get('Content-Disposition'))->toContain('attachment');
        expect($response->headers->get('Content-Disposition'))->toContain('.xlsx');

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        // XLSX is a ZIP archive — assert it has valid ZIP magic bytes (PK\x03\x04)
        expect(substr($content, 0, 4))->toBe("PK\x03\x04");
    });

    it('CSV export includes only invoices matching the type filter', function () {
        $coach = User::factory()->coach()->create();
        Invoice::factory()->invoice()->for($coach, 'coach')->create(['invoice_number' => 'INV-2026-000003']);
        Invoice::factory()->creditNote()->for($coach, 'coach')->create(['invoice_number' => 'CN-2026-000003']);

        $service = app(FinancialExportService::class);

        ob_start();
        $service->exportCsv('', '', '', InvoiceType::Invoice->value)->sendContent();
        $content = ob_get_clean();

        expect($content)->toContain('INV-2026-000003');
        expect($content)->not->toContain('CN-2026-000003');
    });

    it('CSV export includes only invoices in the date range', function () {
        $coach = User::factory()->coach()->create();
        Invoice::factory()->for($coach, 'coach')->create([
            'invoice_number' => 'INV-2026-000004',
            'billing_period_start' => '2026-01-01',
            'billing_period_end' => '2026-01-31',
        ]);
        Invoice::factory()->for($coach, 'coach')->create([
            'invoice_number' => 'INV-2026-000005',
            'billing_period_start' => '2026-06-01',
            'billing_period_end' => '2026-06-30',
        ]);

        $service = app(FinancialExportService::class);

        ob_start();
        $service->exportCsv('2026-01-01', '2026-03-31')->sendContent();
        $content = ob_get_clean();

        expect($content)->toContain('INV-2026-000004');
        expect($content)->not->toContain('INV-2026-000005');
    });
});

describe('FinancialExportController', function () {
    it('downloads CSV for accountant with 2FA', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();
        Invoice::factory()->for($coach, 'coach')->create(['invoice_number' => 'INV-2026-000010']);

        $this->actingAs($accountant)
            ->get(route('accountant.export', ['format' => 'csv']))
            ->assertOk()
            ->assertHeader('Content-Disposition');
    });

    it('downloads Excel for accountant with 2FA', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();

        $this->actingAs($accountant)
            ->get(route('accountant.export', ['format' => 'excel']))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    });

    it('defaults to CSV when format is not specified', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();

        $this->actingAs($accountant)
            ->get(route('accountant.export'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    });

    it('denies access to coaches', function () {
        $coach = User::factory()->coach()->create();

        $this->actingAs($coach)
            ->get(route('accountant.export'))
            ->assertForbidden();
    });

    it('denies access to athletes', function () {
        $athlete = User::factory()->athlete()->create();

        $this->actingAs($athlete)
            ->get(route('accountant.export'))
            ->assertForbidden();
    });

    it('denies access to guests', function () {
        $this->get(route('accountant.export'))
            ->assertRedirect(route('login'));
    });

    it('redirects accountant without 2FA', function () {
        $accountant = User::factory()->accountant()->create();

        $this->actingAs($accountant)
            ->get(route('accountant.export'))
            ->assertRedirect(route('profile.edit'));
    });

    it('applies filters from query parameters', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $coachA = User::factory()->coach()->create();
        $coachB = User::factory()->coach()->create();

        Invoice::factory()->for($coachA, 'coach')->create(['invoice_number' => 'INV-2026-000020']);
        Invoice::factory()->for($coachB, 'coach')->create(['invoice_number' => 'INV-2026-000021']);

        ob_start();
        $this->actingAs($accountant)
            ->get(route('accountant.export', ['format' => 'csv', 'coachId' => $coachA->id]))
            ->sendContent();
        $content = ob_get_clean();

        expect($content)->toContain('INV-2026-000020');
        expect($content)->not->toContain('INV-2026-000021');
    });
});

describe('Dashboard export action', function () {
    it('export action redirects to export route with filters', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();

        Livewire::actingAs($accountant)
            ->test(Dashboard::class)
            ->set('dateFrom', '2026-01-01')
            ->set('dateTo', '2026-12-31')
            ->set('type', 'invoice')
            ->call('export', 'csv')
            ->assertRedirect();
    });

    it('export action redirects to Excel route', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();

        Livewire::actingAs($accountant)
            ->test(Dashboard::class)
            ->call('export', 'excel')
            ->assertRedirect();
    });
});
