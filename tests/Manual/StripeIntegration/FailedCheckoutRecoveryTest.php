<?php

declare(strict_types=1);

use App\Enums\BookingStatus;
use App\Services\BookingService;
use Stripe\Exception\CardException;
use Stripe\PaymentIntent;

require_once __DIR__.'/Support.php';

describe('Stripe manual failed payment integration', function () {
    it('keeps recovery available and releases capacity exactly once after payment failure', function (): void {
        $stripe = requireLiveStripeIntegration();
        $qaRunId = manualStripeQaRunId('failed_payment');
        $connectedAccountId = manualStripeConnectedAccountId($qaRunId);

        $coach = manualStripeCoach($qaRunId, $connectedAccountId);
        $athlete = manualStripeAthlete($qaRunId);
        $session = manualStripeSession($qaRunId, $coach);
        $booking = app(BookingService::class)->book($session, $athlete);

        test()->actingAs($athlete)
            ->get(route('bookings.payment-return', ['booking' => $booking->id, 'status' => 'failed']))
            ->assertOk()
            ->assertSee(__('bookings.payment_return_retry_action'))
            ->assertSee(__('bookings.payment_return_cancel_hold_action'));

        $failedPaymentIntentId = null;

        try {
            PaymentIntent::create([
                'amount' => $session->price_per_person,
                'currency' => 'eur',
                'payment_method' => 'pm_card_chargeDeclined',
                'confirm' => true,
                'automatic_payment_methods' => [
                    'enabled' => true,
                    'allow_redirects' => 'never',
                ],
                'metadata' => [
                    'qa_run_id' => $qaRunId,
                    'session_id' => (string) $session->id,
                    'athlete_id' => (string) $athlete->id,
                    'coach_id' => (string) $coach->id,
                ],
            ]);
        } catch (CardException $exception) {
            $failedPaymentIntentId = $exception->getError()->payment_intent?->id;
        }

        expect($failedPaymentIntentId)->toBeString()->toStartWith('pi_');

        $booking->forceFill(['stripe_payment_intent_id' => $failedPaymentIntentId])->save();

        postManualStripeWebhook(
            $stripe['webhook_secret'],
            "evt_{$qaRunId}_payment_failed",
            'payment_intent.payment_failed',
            [
                'id' => $failedPaymentIntentId,
                'metadata' => [
                    'qa_run_id' => $qaRunId,
                    'session_id' => (string) $session->id,
                    'athlete_id' => (string) $athlete->id,
                    'coach_id' => (string) $coach->id,
                ],
            ],
        )->assertOk()->assertJson(['status' => 'processed']);

        expect($booking->fresh()->status)->toBe(BookingStatus::Cancelled)
            ->and($booking->fresh()->cancelled_at)->not->toBeNull()
            ->and($session->fresh()->current_participants)->toBe(0);

        postManualStripeWebhook(
            $stripe['webhook_secret'],
            "evt_{$qaRunId}_payment_failed",
            'payment_intent.payment_failed',
            ['id' => $failedPaymentIntentId],
        )->assertOk()->assertJson(['status' => 'already_processed']);

        expect($session->fresh()->current_participants)->toBe(0);
    });
});
