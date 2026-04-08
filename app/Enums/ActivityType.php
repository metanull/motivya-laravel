<?php

declare(strict_types=1);

namespace App\Enums;

enum ActivityType: string
{
    case Yoga = 'yoga';
    case Strength = 'strength';
    case Running = 'running';
    case Cardio = 'cardio';
    case Pilates = 'pilates';
    case Outdoor = 'outdoor';
    case Boxing = 'boxing';
    case Dance = 'dance';
    case Padel = 'padel';
    case Tennis = 'tennis';

    public function label(): string
    {
        return __('sessions.activity_'.$this->value);
    }
}
