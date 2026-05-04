<?php

declare(strict_types=1);

namespace App\Enums;

enum RefundAuditStatus: string
{
    case Attempted = 'attempted';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
}
