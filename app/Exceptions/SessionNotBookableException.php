<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class SessionNotBookableException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Only published or confirmed sessions can be booked.');
    }
}
