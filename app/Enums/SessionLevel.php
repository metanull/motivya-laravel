<?php

declare(strict_types=1);

namespace App\Enums;

enum SessionLevel: string
{
    case Beginner = 'beginner';
    case Intermediate = 'intermediate';
    case Advanced = 'advanced';

    public function label(): string
    {
        return __('sessions.level_'.$this->value);
    }
}
