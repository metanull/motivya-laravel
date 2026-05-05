<?php

declare(strict_types=1);

namespace App\Enums;

enum CoachPayoutStatementStatus: string
{
    case Draft = 'draft';
    case ReadyForInvoice = 'ready_for_invoice';
    case InvoiceSubmitted = 'invoice_submitted';
    case Approved = 'approved';
    case Paid = 'paid';
    case Blocked = 'blocked';

    public function label(): string
    {
        return __('coach.payout_statement_status_'.$this->value);
    }
}
