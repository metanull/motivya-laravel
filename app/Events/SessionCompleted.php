<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\SportSession;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SessionCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly SportSession $session,
    ) {}
}
