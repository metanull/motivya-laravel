<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Invoice;
use App\Models\SportSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Invoice model', function () {

    describe('auto-numbering on create', function () {

        it('auto-generates an INV invoice number when none is provided', function () {
            $invoice = Invoice::factory()->invoice()->create();

            expect($invoice->invoice_number)->toMatch('/^INV-\d{4}-\d{6}$/');
        });

        it('auto-generates a CN invoice number for credit notes', function () {
            $invoice = Invoice::factory()->creditNote()->create();

            expect($invoice->invoice_number)->toMatch('/^CN-\d{4}-\d{6}$/');
        });

        it('increments the sequence for each new invoice in the same year', function () {
            $first = Invoice::factory()->invoice()->create();
            $second = Invoice::factory()->invoice()->create();

            $firstSeq = (int) substr($first->invoice_number, -6);
            $secondSeq = (int) substr($second->invoice_number, -6);

            expect($secondSeq)->toBe($firstSeq + 1);
        });

        it('uses independent sequences for invoices and credit notes', function () {
            Invoice::factory()->invoice()->create();
            $creditNote = Invoice::factory()->creditNote()->create();

            expect($creditNote->invoice_number)->toMatch('/^CN-\d{4}-000001$/');
        });

        it('preserves a manually supplied invoice number', function () {
            $invoice = Invoice::factory()->invoice()->create([
                'invoice_number' => 'INV-2099-099999',
            ]);

            expect($invoice->invoice_number)->toBe('INV-2099-099999');
        });

        it('includes the current year in the generated number', function () {
            $year = now()->format('Y');
            $invoice = Invoice::factory()->invoice()->create();

            expect($invoice->invoice_number)->toContain("-{$year}-");
        });

    });

    describe('generateInvoiceNumber', function () {

        it('returns INV-{year}-000001 for the first invoice of the year', function () {
            $year = now()->format('Y');

            expect(Invoice::generateInvoiceNumber(InvoiceType::Invoice))
                ->toBe("INV-{$year}-000001");
        });

        it('returns CN-{year}-000001 for the first credit note of the year', function () {
            $year = now()->format('Y');

            expect(Invoice::generateInvoiceNumber(InvoiceType::CreditNote))
                ->toBe("CN-{$year}-000001");
        });

        it('accepts a string type value', function () {
            $year = now()->format('Y');

            expect(Invoice::generateInvoiceNumber('invoice'))
                ->toBe("INV-{$year}-000001");

            expect(Invoice::generateInvoiceNumber('credit_note'))
                ->toBe("CN-{$year}-000001");
        });

    });

    describe('casts', function () {

        it('casts type to InvoiceType enum', function () {
            $invoice = Invoice::factory()->invoice()->create();

            expect($invoice->type)->toBeInstanceOf(InvoiceType::class);
            expect($invoice->type)->toBe(InvoiceType::Invoice);
        });

        it('casts status to InvoiceStatus enum', function () {
            $invoice = Invoice::factory()->draft()->create();

            expect($invoice->status)->toBeInstanceOf(InvoiceStatus::class);
            expect($invoice->status)->toBe(InvoiceStatus::Draft);
        });

        it('casts billing_period_start and _end as dates', function () {
            $invoice = Invoice::factory()->create();

            expect($invoice->billing_period_start)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
            expect($invoice->billing_period_end)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
        });

        it('casts issued_at as a datetime', function () {
            $invoice = Invoice::factory()->issued()->create();

            expect($invoice->issued_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
        });

    });

    describe('relationships', function () {

        it('belongs to a coach (User)', function () {
            $coach = User::factory()->coach()->create();
            $invoice = Invoice::factory()->create(['coach_id' => $coach->id]);

            expect($invoice->coach)->toBeInstanceOf(User::class);
            expect($invoice->coach->id)->toBe($coach->id);
        });

        it('optionally belongs to a SportSession', function () {
            $session = SportSession::factory()->create();
            $invoice = Invoice::factory()->create(['sport_session_id' => $session->id]);

            expect($invoice->sportSession)->toBeInstanceOf(SportSession::class);
            expect($invoice->sportSession->id)->toBe($session->id);
        });

        it('sport_session_id can be null', function () {
            $invoice = Invoice::factory()->create(['sport_session_id' => null]);

            expect($invoice->sport_session_id)->toBeNull();
            expect($invoice->sportSession)->toBeNull();
        });

        it('optionally references a related invoice (credit note)', function () {
            $original = Invoice::factory()->invoice()->create();
            $creditNote = Invoice::factory()->creditNote()->create([
                'related_invoice_id' => $original->id,
            ]);

            expect($creditNote->relatedInvoice)->toBeInstanceOf(Invoice::class);
            expect($creditNote->relatedInvoice->id)->toBe($original->id);
        });

    });

    describe('factory states', function () {

        it('creates an invoice with the invoice type', function () {
            $invoice = Invoice::factory()->invoice()->create();

            expect($invoice->type)->toBe(InvoiceType::Invoice);
        });

        it('creates a credit note', function () {
            $invoice = Invoice::factory()->creditNote()->create();

            expect($invoice->type)->toBe(InvoiceType::CreditNote);
        });

        it('creates an invoice in draft status', function () {
            $invoice = Invoice::factory()->draft()->create();

            expect($invoice->status)->toBe(InvoiceStatus::Draft);
        });

        it('creates an issued invoice with issued_at set', function () {
            $invoice = Invoice::factory()->issued()->create();

            expect($invoice->status)->toBe(InvoiceStatus::Issued);
            expect($invoice->issued_at)->not->toBeNull();
        });

        it('creates a paid invoice', function () {
            $invoice = Invoice::factory()->paid()->create();

            expect($invoice->status)->toBe(InvoiceStatus::Paid);
        });

    });

});
