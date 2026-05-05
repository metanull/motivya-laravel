<?php

declare(strict_types=1);

use App\Enums\AuditEventType;
use App\Enums\InvoiceType;
use App\Models\AuditEvent;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

describe('InvoiceXmlController audit', function () {
    beforeEach(function (): void {
        Storage::fake();
    });

    it('records an invoice.xml_downloaded event when accountant downloads XML', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();

        $xmlPath = 'invoices/audit_test_invoice.xml';
        Storage::put($xmlPath, '<?xml version="1.0"?><Invoice/>');

        $invoice = Invoice::factory()->create([
            'coach_id' => $coach->id,
            'type' => InvoiceType::Invoice,
            'xml_path' => $xmlPath,
        ]);

        $this->actingAs($accountant)
            ->get(route('accountant.invoices.xml', $invoice));

        expect(
            AuditEvent::where('event_type', AuditEventType::InvoiceXmlDownloaded->value)->exists()
        )->toBeTrue();
    });

    it('records the xml filename and actor_id in audit metadata', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();

        $xmlPath = 'invoices/meta_check.xml';
        Storage::put($xmlPath, '<?xml version="1.0"?><Invoice/>');

        $invoice = Invoice::factory()->create([
            'coach_id' => $coach->id,
            'type' => InvoiceType::Invoice,
            'xml_path' => $xmlPath,
        ]);

        $this->actingAs($accountant)
            ->get(route('accountant.invoices.xml', $invoice));

        $audit = AuditEvent::where('event_type', AuditEventType::InvoiceXmlDownloaded->value)->firstOrFail();

        expect($audit->metadata['filename'])->toBe('meta_check.xml')
            ->and($audit->metadata['actor_id'])->toBe($accountant->id);
    });

    it('returns 404 when the XML file is missing without creating an audit event', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $coach = User::factory()->coach()->create();

        $invoice = Invoice::factory()->create([
            'coach_id' => $coach->id,
            'type' => InvoiceType::Invoice,
            'xml_path' => 'invoices/nonexistent.xml',
        ]);

        $this->actingAs($accountant)
            ->get(route('accountant.invoices.xml', $invoice))
            ->assertNotFound();

        expect(
            AuditEvent::where('event_type', AuditEventType::InvoiceXmlDownloaded->value)->exists()
        )->toBeFalse();
    });
});
