<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class SessionNotBookableException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(__('bookings.error_session_not_bookable'));
    }
}
