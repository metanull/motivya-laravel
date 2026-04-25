<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\SessionCompleted;
use App\Services\InvoiceService;
use Illuminate\Support\Facades\Log;

final class GenerateInvoiceOnSessionCompletion
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
    ) {}

    public function handle(SessionCompleted $event): void
    {
        $session = $event->session;

        // Coaches without a completed profile (e.g. still onboarding) cannot
        // have invoices generated for them.  Skip silently so that session
        // completion itself is never blocked by a missing coach profile.
        if ($session->coach->coachProfile === null) {
            Log::warning('GenerateInvoiceOnSessionCompletion: coach has no profile; skipping invoice generation.', [
                'session_id' => $session->id,
                'coach_id' => $session->coach_id,
            ]);

            return;
        }

        $this->invoiceService->generateForCompletedSession($session);
    }
}
