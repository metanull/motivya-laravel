<?php

declare(strict_types=1);

use App\Enums\InvoiceType;
use App\Events\BookingRefunded;
use App\Models\Booking;
use App\Models\CoachProfile;
use App\Models\Invoice;
use App\Models\SportSession;
use App\Models\User;
use App\Services\VatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

describe('GenerateCreditNoteOnRefund', function () {

    beforeEach(function () {
        Storage::fake();
    });

    it('creates a credit note when a booking is refunded', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->vatSubject()->create(['user_id' => $coach->id]);

        $session = SportSession::factory()->completed()->for($coach, 'coach')->create([
            'date' => '2026-05-01',
        ]);

        $originalInvoice = Invoice::factory()->invoice()->issued()->create([
            'coach_id' => $coach->id,
            'sport_session_id' => $session->id,
            'billing_period_start' => '2026-05-01',
            'billing_period_end' => '2026-05-01',
            'revenue_ttc' => 24200,
            'revenue_htva' => 20000,
            'vat_amount' => 4200,
            'tax_category_code' => 'S',
        ]);

        $booking = Booking::factory()->refunded()->create([
            'sport_session_id' => $session->id,
            'amount_paid' => 12100,
        ]);

        event(new BookingRefunded($booking->id));

        $this->assertDatabaseCount('invoices', 2);

        $creditNote = Invoice::where('type', InvoiceType::CreditNote)->first();

        expect($creditNote)->not->toBeNull()
            ->and($creditNote->type)->toBe(InvoiceType::CreditNote)
            ->and($creditNote->related_invoice_id)->toBe($originalInvoice->id)
            ->and($creditNote->coach_id)->toBe($coach->id)
            ->and($creditNote->sport_session_id)->toBe($session->id)
            ->and($creditNote->revenue_ttc)->toBe(12100);
    });

    it('sets correct VAT amounts for a VAT-subject coach', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->vatSubject()->create(['user_id' => $coach->id]);

        $session = SportSession::factory()->completed()->for($coach, 'coach')->create();

        Invoice::factory()->invoice()->issued()->create([
            'coach_id' => $coach->id,
            'sport_session_id' => $session->id,
        ]);

        $booking = Booking::factory()->refunded()->create([
            'sport_session_id' => $session->id,
            'amount_paid' => 12100,
        ]);

        event(new BookingRefunded($booking->id));

        $creditNote = Invoice::where('type', InvoiceType::CreditNote)->first();
        $vatService = new VatService;
        $expectedHtva = $vatService->toHtva(12100);

        expect($creditNote->tax_category_code)->toBe('S')
            ->and($creditNote->revenue_htva)->toBe($expectedHtva)
            ->and($creditNote->vat_amount)->toBe((int) round($expectedHtva * 21 / 100));
    });

    it('sets zero VAT for a non-VAT-subject coach', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->nonVatSubject()->create(['user_id' => $coach->id]);

        $session = SportSession::factory()->completed()->for($coach, 'coach')->create();

        Invoice::factory()->invoice()->issued()->create([
            'coach_id' => $coach->id,
            'sport_session_id' => $session->id,
        ]);

        $booking = Booking::factory()->refunded()->create([
            'sport_session_id' => $session->id,
            'amount_paid' => 10000,
        ]);

        event(new BookingRefunded($booking->id));

        $creditNote = Invoice::where('type', InvoiceType::CreditNote)->first();

        expect($creditNote->tax_category_code)->toBe('E')
            ->and($creditNote->vat_amount)->toBe(0);
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

        $originalInvoice = Invoice::factory()->invoice()->issued()->create([
            'coach_id' => $coach->id,
            'sport_session_id' => $session->id,
            'billing_period_start' => '2026-05-01',
            'billing_period_end' => '2026-05-01',
        ]);

        $booking = Booking::factory()->refunded()->create([
            'sport_session_id' => $session->id,
            'amount_paid' => 12100,
        ]);

        event(new BookingRefunded($booking->id));

        $creditNote = Invoice::where('type', InvoiceType::CreditNote)->first();

        expect($creditNote->xml_path)->not->toBeNull();
        Storage::assertExists($creditNote->xml_path);

        $xml = Storage::get($creditNote->xml_path);
        expect($xml)
            ->toContain('<cbc:InvoiceTypeCode>381</cbc:InvoiceTypeCode>')
            ->toContain('<cac:BillingReference>')
            ->toContain($originalInvoice->invoice_number)
            ->toContain('<cbc:ID>S</cbc:ID>');
    });

    it('preserves the billing period from the original invoice', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->vatSubject()->create(['user_id' => $coach->id]);

        $session = SportSession::factory()->completed()->for($coach, 'coach')->create([
            'date' => '2026-06-15',
        ]);

        $originalInvoice = Invoice::factory()->invoice()->issued()->create([
            'coach_id' => $coach->id,
            'sport_session_id' => $session->id,
            'billing_period_start' => '2026-06-15',
            'billing_period_end' => '2026-06-15',
        ]);

        $booking = Booking::factory()->refunded()->create([
            'sport_session_id' => $session->id,
            'amount_paid' => 10000,
        ]);

        event(new BookingRefunded($booking->id));

        $creditNote = Invoice::where('type', InvoiceType::CreditNote)->first();

        expect($creditNote->billing_period_start->format('Y-m-d'))->toBe('2026-06-15')
            ->and($creditNote->billing_period_end->format('Y-m-d'))->toBe('2026-06-15');
    });

    it('skips credit note generation when no original invoice exists', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->vatSubject()->create(['user_id' => $coach->id]);

        $session = SportSession::factory()->completed()->for($coach, 'coach')->create();

        // No invoice created for this session

        $booking = Booking::factory()->refunded()->create([
            'sport_session_id' => $session->id,
            'amount_paid' => 10000,
        ]);

        event(new BookingRefunded($booking->id));

        $this->assertDatabaseCount('invoices', 0);
    });

    it('creates separate credit notes for multiple refunded bookings', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->vatSubject()->create(['user_id' => $coach->id]);

        $session = SportSession::factory()->completed()->for($coach, 'coach')->create();

        $originalInvoice = Invoice::factory()->invoice()->issued()->create([
            'coach_id' => $coach->id,
            'sport_session_id' => $session->id,
        ]);

        $bookingA = Booking::factory()->refunded()->create([
            'sport_session_id' => $session->id,
            'amount_paid' => 12100,
        ]);

        $bookingB = Booking::factory()->refunded()->create([
            'sport_session_id' => $session->id,
            'amount_paid' => 9900,
        ]);

        event(new BookingRefunded($bookingA->id));
        event(new BookingRefunded($bookingB->id));

        $this->assertDatabaseCount('invoices', 3); // 1 original + 2 credit notes

        $creditNotes = Invoice::where('type', InvoiceType::CreditNote)->get();

        expect($creditNotes)->toHaveCount(2);

        $amounts = $creditNotes->pluck('revenue_ttc')->sort()->values();
        expect($amounts[0])->toBe(9900)
            ->and($amounts[1])->toBe(12100);
    });
});
