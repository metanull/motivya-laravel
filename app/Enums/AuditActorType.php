<?php

declare(strict_types=1);

namespace App\Enums;

enum AuditActorType: string
{
    case User = 'user';
    case System = 'system';
    case Stripe = 'stripe';
    case Scheduler = 'scheduler';
    case Console = 'console';
    case Queue = 'queue';
}
