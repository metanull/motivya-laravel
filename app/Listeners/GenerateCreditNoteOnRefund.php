<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\InvoiceType;
use App\Events\BookingRefunded;
use App\Models\Booking;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Support\Facades\Log;

final class GenerateCreditNoteOnRefund
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
    ) {}

    public function handle(BookingRefunded $event): void
    {
        $booking = Booking::find($event->bookingId);

        if ($booking === null) {
            Log::warning('GenerateCreditNoteOnRefund: booking not found; skipping credit note.', [
                'booking_id' => $event->bookingId,
            ]);

            return;
        }

        if ($booking->sport_session_id === null) {
            Log::warning('GenerateCreditNoteOnRefund: booking has no session; skipping credit note.', [
                'booking_id' => $booking->id,
            ]);

            return;
        }

        $originalInvoice = Invoice::where('sport_session_id', $booking->sport_session_id)
            ->where('type', InvoiceType::Invoice)
            ->first();

        if ($originalInvoice === null) {
            Log::warning('GenerateCreditNoteOnRefund: no original invoice found for session; skipping credit note.', [
                'booking_id' => $booking->id,
                'sport_session_id' => $booking->sport_session_id,
            ]);

            return;
        }

        $this->invoiceService->generateCreditNote($booking, $originalInvoice);
    }
}
