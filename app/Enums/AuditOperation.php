<?php

declare(strict_types=1);

namespace App\Enums;

enum AuditOperation: string
{
    case Create = 'create';
    case Update = 'update';
    case StateChange = 'state_change';
    case Delete = 'delete';
    case Payment = 'payment';
    case Refund = 'refund';
    case Export = 'export';
    case Security = 'security';
}
