<?php

declare(strict_types=1);

namespace App\Events\Stripe;

use Illuminate\Foundation\Events\Dispatchable;
use Stripe\Event;

final class PaymentIntentSucceeded
{
    use Dispatchable;

    public function __construct(
        public readonly Event $stripeEvent,
    ) {}
}
