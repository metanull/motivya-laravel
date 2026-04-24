<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Events\BookingCancelled;
use App\Events\BookingCreated;
use App\Events\BookingRefunded;
use App\Events\CoachStripeOnboardingComplete;
use App\Events\Stripe\AccountUpdated;
use App\Events\Stripe\ChargeRefunded;
use App\Events\Stripe\PaymentIntentFailed;
use App\Events\Stripe\PaymentIntentSucceeded;
use App\Models\Booking;
use App\Models\CoachProfile;
use App\Models\ProcessedWebhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        try {
            $status = DB::transaction(function () use ($event): string {
                $processedWebhook = ProcessedWebhook::query()->firstOrCreate(
                    ['stripe_event_id' => $event->id],
                    ['event_type' => $event->type],
                );

                if (! $processedWebhook->wasRecentlyCreated) {
                    return 'already_processed';
                }

                $this->processStripeEvent($event);
                $this->dispatchStripeEvent($event);

                return 'processed';
            });

            return response()->json(['status' => $status]);
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

    private function processStripeEvent(Event $event): void
    {
        match ($event->type) {
            'payment_intent.succeeded' => $this->handlePaymentIntentSucceeded($event),
            'payment_intent.payment_failed' => $this->handlePaymentIntentFailed($event),
            'charge.refunded' => $this->handleChargeRefunded($event),
            'account.updated' => $this->handleAccountUpdated($event),
            default => null,
        };
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

    private function handlePaymentIntentSucceeded(Event $event): void
    {
        $booking = $this->findBookingForPaymentIntent($event);

        if ($booking === null) {
            return;
        }

        $lockedBooking = Booking::query()
            ->lockForUpdate()
            ->find($booking->getKey());

        if ($lockedBooking === null || $lockedBooking->status !== BookingStatus::PendingPayment) {
            return;
        }

        $paymentIntent = $event->data->object;
        $amountPaid = $this->integerValue($paymentIntent->amount_received ?? $paymentIntent->amount ?? null);

        $lockedBooking->forceFill([
            'status' => BookingStatus::Confirmed,
            'amount_paid' => $amountPaid ?? $lockedBooking->amount_paid,
        ])->save();

        BookingCreated::dispatch($lockedBooking->getKey());
    }

    private function handlePaymentIntentFailed(Event $event): void
    {
        $booking = $this->findBookingForPaymentIntent($event);

        if ($booking === null) {
            return;
        }

        $lockedBooking = Booking::query()
            ->lockForUpdate()
            ->find($booking->getKey());

        if ($lockedBooking === null || $lockedBooking->status !== BookingStatus::PendingPayment) {
            return;
        }

        $lockedSession = $lockedBooking->sportSession()
            ->lockForUpdate()
            ->first();

        if ($lockedSession !== null) {
            $lockedSession->forceFill([
                'current_participants' => max($lockedSession->current_participants - 1, 0),
            ])->save();
        }

        $lockedBooking->forceFill([
            'status' => BookingStatus::Cancelled,
            'cancelled_at' => now(),
        ])->save();

        BookingCancelled::dispatch($lockedBooking->getKey(), 'payment_failed', false);
    }

    private function handleChargeRefunded(Event $event): void
    {
        $booking = $this->findBookingForCharge($event);

        if ($booking === null) {
            return;
        }

        $lockedBooking = Booking::query()
            ->lockForUpdate()
            ->find($booking->getKey());

        if ($lockedBooking === null || $lockedBooking->status === BookingStatus::Refunded) {
            return;
        }

        $lockedBooking->forceFill([
            'status' => BookingStatus::Refunded,
            'refunded_at' => now(),
        ])->save();

        BookingRefunded::dispatch($lockedBooking->getKey());
    }

    private function handleAccountUpdated(Event $event): void
    {
        $account = $event->data->object;
        $stripeAccountId = $this->stringValue($account->id ?? null);

        if ($stripeAccountId === null) {
            return;
        }

        $coachProfile = CoachProfile::query()
            ->where('stripe_account_id', $stripeAccountId)
            ->first();

        if ($coachProfile === null) {
            return;
        }

        $detailsSubmitted = (bool) ($account->details_submitted ?? false);
        $chargesEnabled = (bool) ($account->charges_enabled ?? false);

        if (! $detailsSubmitted || ! $chargesEnabled || $coachProfile->stripe_onboarding_complete) {
            return;
        }

        $coachProfile->forceFill([
            'stripe_onboarding_complete' => true,
        ])->save();

        CoachStripeOnboardingComplete::dispatch($coachProfile->getKey());
    }

    private function findBookingForPaymentIntent(Event $event): ?Booking
    {
        $paymentIntent = $event->data->object;

        return $this->findBookingByPaymentIntentOrMetadata(
            $this->stringValue($paymentIntent->id ?? null),
            $paymentIntent->metadata ?? null,
        );
    }

    private function findBookingForCharge(Event $event): ?Booking
    {
        $charge = $event->data->object;

        return $this->findBookingByPaymentIntentOrMetadata(
            $this->stringValue($charge->payment_intent ?? null),
            $charge->metadata ?? null,
        );
    }

    private function findBookingByPaymentIntentOrMetadata(?string $paymentIntentId, mixed $metadata): ?Booking
    {
        if ($paymentIntentId !== null) {
            $booking = Booking::query()
                ->where('stripe_payment_intent_id', $paymentIntentId)
                ->first();

            if ($booking !== null) {
                return $booking;
            }
        }

        $sessionId = $this->integerValue($metadata?->session_id ?? null);
        $athleteId = $this->integerValue($metadata?->athlete_id ?? null);

        if ($sessionId === null || $athleteId === null) {
            return null;
        }

        return Booking::query()
            ->where('sport_session_id', $sessionId)
            ->where('athlete_id', $athleteId)
            ->latest('id')
            ->first();
    }

    /**
     * Coerce Stripe payload values that may arrive as integers or numeric strings.
     */
    private function integerValue(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }

    /**
     * Normalize optional Stripe identifiers and metadata values to non-empty strings.
     */
    private function stringValue(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
