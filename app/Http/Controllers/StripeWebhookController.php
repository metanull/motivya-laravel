<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Events\Stripe\AccountUpdated;
use App\Events\Stripe\ChargeRefunded;
use App\Events\Stripe\PaymentIntentFailed;
use App\Events\Stripe\PaymentIntentSucceeded;
use App\Models\ProcessedWebhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

final class StripeWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature', '');
        $secret = config('services.stripe.webhook.secret');

        try {
            $event = Webhook::constructEvent($payload, $signature, $secret);
        } catch (SignatureVerificationException $e) {
            Log::warning('Stripe webhook signature verification failed.', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Invalid signature'], 400);
        } catch (\UnexpectedValueException $e) {
            Log::warning('Stripe webhook payload invalid.', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Invalid payload'], 400);
        }

        // Idempotency check — skip duplicate events
        if (ProcessedWebhook::where('stripe_event_id', $event->id)->exists()) {
            return response()->json(['status' => 'already_processed']);
        }

        try {
            // Record the event to prevent reprocessing
            ProcessedWebhook::create([
                'stripe_event_id' => $event->id,
                'event_type' => $event->type,
            ]);

            // Dispatch internal Laravel event based on Stripe event type
            $this->dispatchStripeEvent($event);

            return response()->json(['status' => 'processed']);
        } catch (\Throwable $e) {
            Log::error('Stripe webhook processing error.', [
                'event_id' => $event->id,
                'event_type' => $event->type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Return 200 to prevent Stripe retries for non-transient errors
            return response()->json(['status' => 'error']);
        }
    }

    private function dispatchStripeEvent(Event $event): void
    {
        $eventMap = [
            'payment_intent.succeeded' => PaymentIntentSucceeded::class,
            'payment_intent.payment_failed' => PaymentIntentFailed::class,
            'account.updated' => AccountUpdated::class,
            'charge.refunded' => ChargeRefunded::class,
        ];

        $eventClass = $eventMap[$event->type] ?? null;

        if ($eventClass !== null) {
            event(new $eventClass($event));
        } else {
            Log::info('Unhandled Stripe webhook event type.', [
                'event_type' => $event->type,
                'event_id' => $event->id,
            ]);
        }
    }
}
