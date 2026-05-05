<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentAnomalyType: string
{
    case ConfirmedBookingMissingPayment = 'confirmed_booking_missing_payment';
    case PaidBookingCancelledWithoutRefund = 'paid_booking_cancelled_without_refund';
    case CompletedSessionWithoutInvoice = 'completed_session_without_invoice';
    case InvoiceTotalMismatch = 'invoice_total_mismatch';
    case CoachStripeIncomplete = 'coach_stripe_incomplete';

    public function label(): string
    {
        return __('accountant.anomalies_type_'.$this->value);
    }
}
