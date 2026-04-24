<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class SessionConfirmed implements ShouldQueue
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $sessionId,
    ) {}
}
