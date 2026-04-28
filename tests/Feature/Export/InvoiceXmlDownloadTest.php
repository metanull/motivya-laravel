<?php

declare(strict_types=1);

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

describe('InvoiceXmlController', function () {

    beforeEach(function () {
        Storage::fake();
    });

    it('downloads the XML file for an accountant with 2FA', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();

        $invoice = Invoice::factory()->create([
            'coach_id' => $coach->id,
            'invoice_number' => 'INV-2026-000001',
            'xml_path' => 'invoices/2026/INV-2026-000001.xml',
        ]);

        Storage::put('invoices/2026/INV-2026-000001.xml', '<xml>test</xml>');

        $this->actingAs($accountant)
            ->get(route('accountant.invoices.xml', $invoice))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml');
    });

    it('downloads the XML file for an admin with 2FA', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();

        $invoice = Invoice::factory()->create([
            'coach_id' => $coach->id,
            'invoice_number' => 'INV-2026-000002',
            'xml_path' => 'invoices/2026/INV-2026-000002.xml',
        ]);

        Storage::put('invoices/2026/INV-2026-000002.xml', '<xml>test</xml>');

        $this->actingAs($admin)
            ->get(route('accountant.invoices.xml', $invoice))
            ->assertOk();
    });

    it('returns 404 when the invoice has no xml_path', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();

        $invoice = Invoice::factory()->create([
            'coach_id' => $coach->id,
            'xml_path' => null,
        ]);

        $this->actingAs($accountant)
            ->get(route('accountant.invoices.xml', $invoice))
            ->assertNotFound();
    });

    it('returns 404 when the XML file does not exist on storage', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();

        $invoice = Invoice::factory()->create([
            'coach_id' => $coach->id,
            'xml_path' => 'invoices/2026/MISSING.xml',
        ]);

        $this->actingAs($accountant)
            ->get(route('accountant.invoices.xml', $invoice))
            ->assertNotFound();
    });

    it('denies access to coaches for another coach invoice', function () {
        $coach = User::factory()->coach()->create();
        $otherCoach = User::factory()->coach()->create();

        $invoice = Invoice::factory()->create([
            'coach_id' => $otherCoach->id,
            'xml_path' => 'invoices/2026/INV-OTHER.xml',
        ]);
        Storage::put('invoices/2026/INV-OTHER.xml', '<xml>test</xml>');

        $this->actingAs($coach)
            ->get(route('accountant.invoices.xml', $invoice))
            ->assertForbidden();
    });

    it('denies access to athletes', function () {
        $athlete = User::factory()->athlete()->create();
        $coach = User::factory()->coach()->create();

        $invoice = Invoice::factory()->create([
            'coach_id' => $coach->id,
            'xml_path' => 'invoices/2026/INV-2026-000003.xml',
        ]);
        Storage::put('invoices/2026/INV-2026-000003.xml', '<xml>test</xml>');

        $this->actingAs($athlete)
            ->get(route('accountant.invoices.xml', $invoice))
            ->assertForbidden();
    });

    it('redirects guests to login', function () {
        $coach = User::factory()->coach()->create();
        $invoice = Invoice::factory()->create(['coach_id' => $coach->id]);

        $this->get(route('accountant.invoices.xml', $invoice))
            ->assertRedirect(route('login'));
    });
});
