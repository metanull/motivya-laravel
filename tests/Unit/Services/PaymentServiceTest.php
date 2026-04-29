<?php

declare(strict_types=1);

use App\Models\Booking;
use App\Models\CoachProfile;
use App\Models\SportSession;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\Checkout\Session as CheckoutSession;

uses(RefreshDatabase::class);

describe('createCheckoutSession', function () {
    it('creates a checkout session and stores its identifier on the booking', function () {
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
            createCheckoutSessionUsing: function (array $payload) use ($session, $athlete, $coach, $sessionPrice, $expectedCoachPayout): CheckoutSession {
                expect($payload['mode'])->toBe('payment');
                expect($payload['payment_method_types'])->toBe(['bancontact', 'card']);
                expect($payload['line_items'][0]['price_data']['currency'])->toBe('eur');
                expect($payload['line_items'][0]['price_data']['unit_amount'])->toBe($sessionPrice);
                expect($payload['line_items'][0]['price_data']['product_data']['name'])->toBe($session->title);
                expect($payload['payment_intent_data']['metadata'])->toBe([
                    'session_id' => (string) $session->id,
                    'athlete_id' => (string) $athlete->id,
                    'coach_id' => (string) $coach->id,
                ]);
                expect($payload['payment_intent_data']['transfer_data'])->toBe([
                    'destination' => 'acct_coach_123',
                    'amount' => $expectedCoachPayout,
                ]);

                return CheckoutSession::constructFrom([
                    'id' => 'cs_test_123',
                    'url' => 'https://checkout.stripe.com/pay/cs_test_123',
                ]);
            },
            calculateCoachPayoutUsing: function (Booking $booking, int $amount) use ($session, $sessionPrice, $expectedCoachPayout): int {
                expect($booking->sportSession->is($session))->toBeTrue();
                expect($amount)->toBe($sessionPrice);

                return $expectedCoachPayout;
            },
        );

        $checkoutSession = $service->createCheckoutSession($booking);

        expect($checkoutSession->id)->toBe('cs_test_123');
        expect($booking->fresh()->stripe_checkout_session_id)->toBe('cs_test_123');
    });

    it('refuses to create a checkout session when the coach has no stripe account', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->for($coach)->create([
            'stripe_account_id' => null,
        ]);

        $session = SportSession::factory()->published()->for($coach, 'coach')->create();
        $booking = Booking::factory()->for($session, 'sportSession')->create();

        expect(fn () => (new PaymentService)->createCheckoutSession($booking))
            ->toThrow(InvalidArgumentException::class, 'Coach must have a Stripe account identifier before creating a checkout session.');
    });

    it('defaults the coach payout to the booking amount when the payout calculator is not configured', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->for($coach)->create([
            'stripe_account_id' => 'acct_coach_123',
            'stripe_onboarding_complete' => true,
        ]);

        $session = SportSession::factory()->published()->for($coach, 'coach')->create([
            'price_per_person' => 3200,
        ]);
        $booking = Booking::factory()->for($session, 'sportSession')->create();

        $service = new PaymentService(
            createCheckoutSessionUsing: function (array $payload): CheckoutSession {
                expect($payload['payment_intent_data']['transfer_data'])->toBe([
                    'destination' => 'acct_coach_123',
                    'amount' => 3200,
                ]);

                return CheckoutSession::constructFrom([
                    'id' => 'cs_default_payout',
                    'url' => 'https://checkout.stripe.com/pay/cs_default_payout',
                ]);
            },
        );

        $checkoutSession = $service->createCheckoutSession($booking);

        expect($checkoutSession->id)->toBe('cs_default_payout');
    });

    it('refuses to create a checkout session when the payout calculator returns a negative amount', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->for($coach)->create([
            'stripe_account_id' => 'acct_coach_123',
        ]);

        $session = SportSession::factory()->published()->for($coach, 'coach')->create([
            'price_per_person' => 2750,
        ]);
        $booking = Booking::factory()->for($session, 'sportSession')->create();

        $service = new PaymentService(
            calculateCoachPayoutUsing: fn (): int => -1,
        );

        expect(fn () => $service->createCheckoutSession($booking))
            ->toThrow(InvalidArgumentException::class, 'Coach payout must be between 0 and the booking amount.');
    });

    it('refuses to create a checkout session when the payout calculator exceeds the booking amount', function () {
        $coach = User::factory()->coach()->create();
        CoachProfile::factory()->approved()->for($coach)->create([
            'stripe_account_id' => 'acct_coach_123',
        ]);

        $session = SportSession::factory()->published()->for($coach, 'coach')->create([
            'price_per_person' => 2750,
        ]);
        $booking = Booking::factory()->for($session, 'sportSession')->create();

        $service = new PaymentService(
            calculateCoachPayoutUsing: fn (): int => 2751,
        );

        expect(fn () => $service->createCheckoutSession($booking))
            ->toThrow(InvalidArgumentException::class, 'Coach payout must be between 0 and the booking amount.');
    });
});
