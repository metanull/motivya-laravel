<?php

declare(strict_types=1);

namespace App\Enums;

enum AuditSource: string
{
    case Web = 'web';
    case Console = 'console';
    case Queue = 'queue';
    case Webhook = 'webhook';
    case Scheduler = 'scheduler';
    case Test = 'test';
}
