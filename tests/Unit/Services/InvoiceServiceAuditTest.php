<?php

declare(strict_types=1);

use App\Enums\AuditEventType;
use App\Enums\BookingStatus;
use App\Enums\SessionStatus;
use App\Models\AuditEvent;
use App\Models\Booking;
use App\Models\CoachProfile;
use App\Models\SportSession;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('InvoiceService audit', function () {
    it('records an invoice.generated event for a completed session', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->for($coach)->create(['is_vat_subject' => false]);
        $session = SportSession::factory()->for($coach, 'coach')->create(['status' => SessionStatus::Completed]);
        Booking::factory()->for($session, 'sportSession')->create([
            'status' => BookingStatus::Confirmed,
            'amount_paid' => 2000,
        ]);

        $service = app(InvoiceService::class);
        $service->generateForCompletedSession($session);

        expect(
            AuditEvent::where('event_type', AuditEventType::InvoiceGenerated->value)->exists()
        )->toBeTrue();
    });

    it('records an invoice.credit_note_generated event for a refunded booking', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->for($coach)->create(['is_vat_subject' => false]);
        $session = SportSession::factory()->for($coach, 'coach')->create(['status' => SessionStatus::Completed]);
        $booking = Booking::factory()->for($session, 'sportSession')->create([
            'status' => BookingStatus::Refunded,
            'amount_paid' => 1500,
        ]);

        $service = app(InvoiceService::class);
        $originalInvoice = $service->generateForCompletedSession($session);

        // Reset audit count to test only the credit note audit
        AuditEvent::truncate();

        $service->generateCreditNote($booking, $originalInvoice);

        expect(
            AuditEvent::where('event_type', AuditEventType::InvoiceCreditNoteGenerated->value)->exists()
        )->toBeTrue();
    });

    it('does not create duplicate audit events on idempotent calls', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->for($coach)->create(['is_vat_subject' => false]);
        $session = SportSession::factory()->for($coach, 'coach')->create(['status' => SessionStatus::Completed]);
        Booking::factory()->for($session, 'sportSession')->create([
            'status' => BookingStatus::Confirmed,
            'amount_paid' => 2000,
        ]);

        $service = app(InvoiceService::class);
        $service->generateForCompletedSession($session);
        $service->generateForCompletedSession($session); // idempotent — should not create a second audit

        expect(
            AuditEvent::where('event_type', AuditEventType::InvoiceGenerated->value)->count()
        )->toBe(1);
    });
});
