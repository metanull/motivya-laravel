<?php

declare(strict_types=1);

use App\Models\Booking;
use App\Models\CoachProfile;
use App\Models\SportSession;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\PaymentIntent;

uses(RefreshDatabase::class);

describe('createPaymentIntent', function () {
    it('creates a payment intent and stores its identifier on the booking', function () {
        $sessionPrice = 2750;
        $expectedCoachPayout = 1900;

        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->for($coach)->create([
            'stripe_account_id' => 'acct_coach_123',
        ]);

        $session = SportSession::factory()->published()->for($coach, 'coach')->create([
            'price_per_person' => $sessionPrice,
        ]);
        $athlete = User::factory()->athlete()->create();
        $booking = Booking::factory()
            ->for($session, 'sportSession')
            ->for($athlete, 'athlete')
            ->create();

        $service = new PaymentService(
            createPaymentIntentUsing: function (array $payload) use ($session, $athlete, $coach, $sessionPrice, $expectedCoachPayout): PaymentIntent {
                expect($payload['amount'])->toBe($sessionPrice);
                expect($payload['currency'])->toBe('eur');
                expect($payload['payment_method_types'])->toBe(['bancontact', 'card']);
                expect($payload['capture_method'])->toBe('automatic');
                expect($payload['metadata'])->toBe([
                    'session_id' => (string) $session->id,
                    'athlete_id' => (string) $athlete->id,
                    'coach_id' => (string) $coach->id,
                ]);
                expect($payload['transfer_data'])->toBe([
                    'destination' => 'acct_coach_123',
                    'amount' => $expectedCoachPayout,
                ]);

                return new PaymentIntent(['id' => 'pi_test_123']);
            },
            calculateCoachPayoutUsing: function (Booking $booking, int $amount) use ($session, $sessionPrice, $expectedCoachPayout): int {
                expect($booking->sportSession->is($session))->toBeTrue();
                expect($amount)->toBe($sessionPrice);

                return $expectedCoachPayout;
            },
        );

        $paymentIntent = $service->createPaymentIntent($booking);

        expect($paymentIntent->id)->toBe('pi_test_123');
        expect($booking->fresh()->stripe_payment_intent_id)->toBe('pi_test_123');
    });

    it('refuses to create a payment intent when the coach has no stripe account', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->for($coach)->create([
            'stripe_account_id' => null,
        ]);

        $session = SportSession::factory()->published()->for($coach, 'coach')->create();
        $booking = Booking::factory()->for($session, 'sportSession')->create();

        $service = new PaymentService(
            createPaymentIntentUsing: function (): PaymentIntent {
                throw new RuntimeException('Stripe should not be called when the coach is missing a Stripe account.');
            },
        );

        expect(fn () => $service->createPaymentIntent($booking))
            ->toThrow(InvalidArgumentException::class, 'Coach must have a Stripe account identifier before creating a payment intent.');
    });

    it('refuses to create a payment intent when the payout calculator is not configured', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->for($coach)->create([
            'stripe_account_id' => 'acct_coach_123',
        ]);

        $session = SportSession::factory()->published()->for($coach, 'coach')->create();
        $booking = Booking::factory()->for($session, 'sportSession')->create();

        $service = new PaymentService(
            createPaymentIntentUsing: function (): PaymentIntent {
                throw new RuntimeException('Stripe should not be called when the payout calculator is missing.');
            },
        );

        expect(fn () => $service->createPaymentIntent($booking))
            ->toThrow(RuntimeException::class, 'Coach payout calculation must be configured before creating a payment intent.');
    });
});
