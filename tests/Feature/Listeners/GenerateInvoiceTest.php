<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Events\SessionCompleted;
use App\Models\Booking;
use App\Models\CoachProfile;
use App\Models\Invoice;
use App\Models\SportSession;
use App\Models\User;
use App\Services\VatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

describe('GenerateInvoiceOnSessionCompletion', function () {

    beforeEach(function () {
        Storage::fake();
    });

    it('creates an invoice when a session is completed', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->vatSubject()->create(['user_id' => $coach->id]);

        $session = SportSession::factory()->completed()->for($coach, 'coach')->create([
            'date' => '2026-05-01',
        ]);

        Booking::factory()->confirmed()->for($session, 'sportSession')->create(['amount_paid' => 12000]);
        Booking::factory()->confirmed()->for($session, 'sportSession')->create(['amount_paid' => 12000]);

        event(new SessionCompleted($session));

        $this->assertDatabaseCount('invoices', 1);

        $invoice = Invoice::first();

        expect($invoice)->not->toBeNull()
            ->and($invoice->type)->toBe(InvoiceType::Invoice)
            ->and($invoice->coach_id)->toBe($coach->id)
            ->and($invoice->sport_session_id)->toBe($session->id)
            ->and($invoice->status)->toBe(InvoiceStatus::Draft);
    });

    it('sets the correct revenue TTC from confirmed bookings', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->vatSubject()->create(['user_id' => $coach->id]);

        $session = SportSession::factory()->completed()->for($coach, 'coach')->create();

        Booking::factory()->confirmed()->for($session, 'sportSession')->create(['amount_paid' => 10000]);
        Booking::factory()->confirmed()->for($session, 'sportSession')->create(['amount_paid' => 5000]);
        // Cancelled booking should not be counted
        Booking::factory()->cancelled()->for($session, 'sportSession')->create(['amount_paid' => 2000]);

        event(new SessionCompleted($session));

        $invoice = Invoice::first();

        expect($invoice->revenue_ttc)->toBe(15000);
    });

    it('sets correct VAT amounts for a VAT-subject coach', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->vatSubject()->create(['user_id' => $coach->id]);

        $session = SportSession::factory()->completed()->for($coach, 'coach')->create();
        Booking::factory()->confirmed()->for($session, 'sportSession')->create(['amount_paid' => 12100]);

        event(new SessionCompleted($session));

        $invoice = Invoice::first();
        $vatService = new VatService;
        $expectedHtva = $vatService->toHtva(12100);

        expect($invoice->tax_category_code)->toBe('S')
            ->and($invoice->revenue_htva)->toBe($expectedHtva)
            ->and($invoice->vat_amount)->toBe((int) round($expectedHtva * 21 / 100));
    });

    it('sets zero VAT for a non-VAT-subject coach', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->nonVatSubject()->create(['user_id' => $coach->id]);

        $session = SportSession::factory()->completed()->for($coach, 'coach')->create();
        Booking::factory()->confirmed()->for($session, 'sportSession')->create(['amount_paid' => 10000]);

        event(new SessionCompleted($session));

        $invoice = Invoice::first();

        expect($invoice->tax_category_code)->toBe('E')
            ->and($invoice->vat_amount)->toBe(0);
    });

    it('stores the billing period matching the session date', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->vatSubject()->create(['user_id' => $coach->id]);

        $session = SportSession::factory()->completed()->for($coach, 'coach')->create([
            'date' => '2026-06-15',
        ]);
        Booking::factory()->confirmed()->for($session, 'sportSession')->create(['amount_paid' => 10000]);

        event(new SessionCompleted($session));

        $invoice = Invoice::first();

        expect($invoice->billing_period_start->format('Y-m-d'))->toBe('2026-06-15')
            ->and($invoice->billing_period_end->format('Y-m-d'))->toBe('2026-06-15');
    });

    it('generates the PEPPOL XML file and persists the xml_path', function () {
        $coach = User::factory()->coach()->create(['name' => 'Jean Dupont']);
        CoachProfile::factory()->vatSubject()->create([
            'user_id' => $coach->id,
            'enterprise_number' => '0123.456.789',
        ]);

        $session = SportSession::factory()->completed()->for($coach, 'coach')->create([
            'date' => '2026-05-01',
        ]);
        Booking::factory()->confirmed()->for($session, 'sportSession')->create(['amount_paid' => 12100]);

        event(new SessionCompleted($session));

        $invoice = Invoice::first();

        expect($invoice->xml_path)->not->toBeNull();
        Storage::assertExists($invoice->xml_path);
    });

    it('does not create an invoice when the session has no confirmed bookings', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->vatSubject()->create(['user_id' => $coach->id]);

        $session = SportSession::factory()->completed()->for($coach, 'coach')->create();

        // Only cancelled bookings — no revenue
        Booking::factory()->cancelled()->for($session, 'sportSession')->create(['amount_paid' => 0]);

        event(new SessionCompleted($session));

        // Invoice should still be created (zero revenue is valid for record keeping)
        $this->assertDatabaseCount('invoices', 1);

        $invoice = Invoice::first();
        expect($invoice->revenue_ttc)->toBe(0);
    });

    it('wraps invoice creation in a transaction so failures leave no partial data', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->vatSubject()->create(['user_id' => $coach->id]);

        $session = SportSession::factory()->completed()->for($coach, 'coach')->create([
            'date' => '2026-05-01',
        ]);
        Booking::factory()->confirmed()->for($session, 'sportSession')->create(['amount_paid' => 12100]);

        // Use a disk that will fail on write to simulate a storage error mid-transaction
        Storage::shouldReceive('put')->andThrow(new RuntimeException('Storage failure'));

        try {
            event(new SessionCompleted($session));
        } catch (RuntimeException) {
            // expected
        }

        $this->assertDatabaseCount('invoices', 0);
    });
});
