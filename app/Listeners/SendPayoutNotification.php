<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\CoachPayoutProcessed;
use App\Models\Invoice;
use App\Notifications\PayoutProcessedNotification;

final class SendPayoutNotification
{
    public function handle(CoachPayoutProcessed $event): void
    {
        $invoice = Invoice::with('coach')->findOrFail($event->invoiceId);

        $invoice->coach->notify(
            (new PayoutProcessedNotification($event->invoiceId))
                ->locale($invoice->coach->locale ?? 'fr'),
        );
    }
}
