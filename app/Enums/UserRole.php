<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case Coach = 'coach';
    case Athlete = 'athlete';
    case Accountant = 'accountant';
    case Admin = 'admin';
}
