<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class AlreadyBookedException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Athlete already has a booking for this session.');
    }
}
