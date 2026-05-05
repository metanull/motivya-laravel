<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\AuditEventType;
use App\Enums\AuditOperation;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Services\Audit\AuditContextResolver;
use App\Services\Audit\AuditService;
use App\Services\Audit\AuditSubject;
use Closure;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Stripe\Checkout\Session as StripeCheckoutSession;
use Stripe\Stripe;

final class ReconcileBookings extends Command
{
    protected $signature = 'payments:reconcile-bookings
                            {--repair : Apply repairs; without this flag the command runs in dry-run mode}';

    protected $description = 'Find confirmed bookings missing a Stripe payment intent and optionally repair them';

    public function __construct(
        private readonly AuditService $auditService,
        private readonly AuditContextResolver $contextResolver,
        private readonly ?Closure $retrieveCheckoutSessionUsing = null,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $repair = (bool) $this->option('repair');

        if ($repair) {
            $this->info('Running in REPAIR mode — bookings with a recoverable Stripe checkout session will be updated.');
        } else {
            $this->info('Running in DRY-RUN mode — no changes will be made.');
        }

        $unreconciled = Booking::query()
            ->where('status', BookingStatus::Confirmed->value)
            ->where('amount_paid', '>', 0)
            ->whereNull('stripe_payment_intent_id')
            ->orderBy('id')
            ->get();

        if ($unreconciled->isEmpty()) {
            $this->info('No bookings require reconciliation.');

            return self::SUCCESS;
        }

        $this->warn("Found {$unreconciled->count()} confirmed booking(s) missing a Stripe payment intent.");

        $headers = ['Booking ID', 'Athlete', 'Session', 'Amount (€)', 'Stripe CS ID', 'Status'];
        $rows = $unreconciled->map(fn (Booking $b): array => [
            $b->id,
            $b->athlete?->email ?? '—',
            $b->sport_session_id,
            number_format($b->amount_paid / 100, 2),
            $b->stripe_checkout_session_id ?? '—',
            $repair ? 'pending repair' : 'dry-run',
        ])->toArray();

        $this->table($headers, $rows);

        if (! $repair) {
            $this->info('Re-run with --repair to attempt automatic reconciliation for bookings that have a Stripe checkout session ID.');

            return self::SUCCESS;
        }

        $auditContext = $this->contextResolver->forScheduler('payments:reconcile-bookings');
        $repaired = 0;
        $skipped = 0;

        foreach ($unreconciled as $booking) {
            if (empty($booking->stripe_checkout_session_id)) {
                $this->warn("  Booking #{$booking->id}: no checkout session ID — manual review required.");
                $skipped++;

                continue;
            }

            try {
                $stripeSession = $this->retrieveCheckoutSession($booking->stripe_checkout_session_id);
            } catch (\Throwable $e) {
                $this->error("  Booking #{$booking->id}: Stripe retrieval failed — {$e->getMessage()}");
                $skipped++;

                continue;
            }

            $paymentIntentId = is_string($stripeSession->payment_intent) && $stripeSession->payment_intent !== ''
                ? $stripeSession->payment_intent
                : null;

            if ($paymentIntentId === null) {
                $this->warn("  Booking #{$booking->id}: checkout session has no payment intent — manual review required.");
                $skipped++;

                continue;
            }

            // Guard: ensure no other booking already holds this payment intent
            $collision = Booking::where('stripe_payment_intent_id', $paymentIntentId)
                ->where('id', '!=', $booking->id)
                ->exists();

            if ($collision) {
                $this->warn("  Booking #{$booking->id}: payment intent {$paymentIntentId} already belongs to another booking — skipped.");
                $skipped++;

                continue;
            }

            DB::transaction(function () use ($booking, $paymentIntentId, $auditContext): void {
                $locked = Booking::query()->lockForUpdate()->find($booking->getKey());

                if ($locked === null
                    || $locked->status !== BookingStatus::Confirmed
                    || $locked->stripe_payment_intent_id !== null) {
                    return;
                }

                $locked->forceFill(['stripe_payment_intent_id' => $paymentIntentId])->save();

                $this->auditService->record(
                    AuditEventType::BookingPaymentReconciled,
                    AuditOperation::Payment,
                    $locked,
                    subjects: [AuditSubject::primary($locked)],
                    oldValues: ['stripe_payment_intent_id' => null],
                    newValues: ['stripe_payment_intent_id' => $paymentIntentId],
                    context: $auditContext,
                );
            });

            $this->info("  Booking #{$booking->id}: repaired with payment intent {$paymentIntentId}.");
            $repaired++;
        }

        $this->info("Done. Repaired: {$repaired}, Skipped: {$skipped}.");

        return self::SUCCESS;
    }

    private function retrieveCheckoutSession(string $checkoutSessionId): StripeCheckoutSession
    {
        if ($this->retrieveCheckoutSessionUsing instanceof Closure) {
            return ($this->retrieveCheckoutSessionUsing)($checkoutSessionId);
        }

        Stripe::setApiKey((string) config('services.stripe.secret'));

        return StripeCheckoutSession::retrieve([
            'id' => $checkoutSessionId,
            'expand' => ['payment_intent'],
        ]);
    }
}
