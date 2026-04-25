<?php

declare(strict_types=1);

namespace App\Enums;

enum SubscriptionPlan: string
{
    case Freemium = 'freemium';
    case Active = 'active';
    case Premium = 'premium';
}
