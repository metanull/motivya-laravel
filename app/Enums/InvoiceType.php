<?php

declare(strict_types=1);

namespace App\Enums;

enum InvoiceType: string
{
    case Invoice    = 'invoice';
    case CreditNote = 'credit_note';
}
