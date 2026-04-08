<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CoachRejected
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $coachProfileId,
        public readonly string $reason,
    ) {}
}
