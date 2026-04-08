<?php

declare(strict_types=1);

namespace App\Enums;

enum SessionStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Confirmed = 'confirmed';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __('sessions.status_'.$this->value);
    }
}
