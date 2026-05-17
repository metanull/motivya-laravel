<?php

declare(strict_types=1);

use App\Enums\BookingStatus;
use App\Enums\SessionStatus;
use App\Models\Booking;
use App\Models\User;
use App\Services\BookingService;
use App\Services\PaymentService;
use Stripe\Checkout\Session as StripeCheckoutSession;
use Stripe\PaymentIntent;

require_once __DIR__.'/Support.php';

describe('Stripe manual successful payment integration', function () {
    it('initiates checkout, records a real successful card payment, and reconciles the webhook receipt', function (): void {
        $stripe = requireLiveStripeIntegration();
        $qaRunId = manualStripeQaRunId('successful_checkout');

        $coach = manualStripeCoach($qaRunId, $stripe['connected_account_id']);
        $athlete = manualStripeAthlete($qaRunId);
        $session = manualStripeSession($qaRunId, $coach, [
            'price_per_person' => 3100,
        ]);

        $booking = app(BookingService::class)->book($session, $athlete);

        expect($booking->status)->toBe(BookingStatus::PendingPayment)
            ->and($session->fresh()->current_participants)->toBe(1);

        $checkoutSession = app(PaymentService::class)->createCheckoutSession($booking);

        expect($checkoutSession->id)->toStartWith('cs_')
            ->and($booking->fresh()->stripe_checkout_session_id)->toBe($checkoutSession->id);

        /** @var StripeCheckoutSession $retrievedCheckout */
        $retrievedCheckout = StripeCheckoutSession::retrieve($checkoutSession->id);

        expect($retrievedCheckout->mode)->toBe('payment')
            ->and($retrievedCheckout->currency)->toBe('eur')
            ->and($retrievedCheckout->payment_method_types)->toContain('bancontact')
            ->and($retrievedCheckout->payment_method_types)->toContain('card')
            ->and($retrievedCheckout->metadata?->session_id)->toBe((string) $session->id)
            ->and($retrievedCheckout->metadata?->athlete_id)->toBe((string) $athlete->id)
            ->and($retrievedCheckout->metadata?->coach_id)->toBe((string) $coach->id);

        /** @var PaymentIntent $paymentIntent */
        $paymentIntent = PaymentIntent::create([
            'amount' => 3100,
            'currency' => 'eur',
            'payment_method' => 'pm_card_visa',
            'payment_method_types' => ['card'],
            'confirm' => true,
            'transfer_data' => [
                'destination' => $stripe['connected_account_id'],
                'amount' => 3100,
            ],
            'metadata' => [
                'session_id' => (string) $session->id,
                'athlete_id' => (string) $athlete->id,
                'coach_id' => (string) $coach->id,
                'qa_run_id' => $qaRunId,
            ],
        ]);

        expect($paymentIntent->id)->toStartWith('pi_')
            ->and($paymentIntent->status)->toBe('succeeded')
            ->and($paymentIntent->amount_received)->toBe(3100);

        $webhookResponse = postManualStripeWebhook(
            $stripe['webhook_secret'],
            "evt_{$qaRunId}_payment_succeeded",
            'payment_intent.succeeded',
            [
                'id' => $paymentIntent->id,
                'amount' => 3100,
                'amount_received' => 3100,
                'metadata' => [
                    'session_id' => (string) $session->id,
                    'athlete_id' => (string) $athlete->id,
                    'coach_id' => (string) $coach->id,
                    'qa_run_id' => $qaRunId,
                ],
            ],
        );

        $webhookResponse->assertOk()->assertJson(['status' => 'processed']);

        $booking = $booking->fresh();

        expect($booking->status)->toBe(BookingStatus::Confirmed)
            ->and($booking->amount_paid)->toBe(3100)
            ->and($booking->stripe_payment_intent_id)->toBe($paymentIntent->id)
            ->and($session->fresh()->status)->toBe(SessionStatus::Confirmed)
            ->and($session->fresh()->current_participants)->toBe(1);

        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $admin = User::factory()->admin()->withTwoFactor()->create();

        test()->actingAs($athlete)->get(route('athlete.dashboard'))->assertOk();
        test()->actingAs($coach)->get(route('coach.dashboard'))->assertOk();
        test()->actingAs($accountant)->get(route('accountant.transactions.index'))->assertOk();
        test()->actingAs($admin)->get(route('admin.refunds.index'))->assertOk();

        expect(Booking::query()->where('stripe_payment_intent_id', $paymentIntent->id)->exists())->toBeTrue();
    });
});
