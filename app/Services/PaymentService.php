<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Booking;
use Closure;
use InvalidArgumentException;
use RuntimeException;
use Stripe\PaymentIntent;
use Stripe\Stripe;

final class PaymentService
{
    public function __construct(
        private readonly ?Closure $createPaymentIntentUsing = null,
        private readonly ?Closure $calculateCoachPayoutUsing = null,
    ) {}

    /**
     * Create a Stripe PaymentIntent for a booking and persist its identifier.
     */
    public function createPaymentIntent(Booking $booking): PaymentIntent
    {
        $booking->loadMissing('sportSession.coach.coachProfile', 'athlete');

        $session = $booking->sportSession;
        $athlete = $booking->athlete;
        $coach = $session?->coach;
        $coachProfile = $coach?->coachProfile;

        if ($session === null) {
            throw new InvalidArgumentException('Booking must belong to a session.');
        }

        if ($athlete === null) {
            throw new InvalidArgumentException('Booking must belong to an athlete.');
        }

        if ($coach === null) {
            throw new InvalidArgumentException('Session must belong to a coach.');
        }

        if (! $this->isNonEmptyString($coachProfile?->stripe_account_id)) {
            throw new InvalidArgumentException('Coach must have a Stripe account identifier before creating a payment intent.');
        }

        $amount = $session->price_per_person;
        $coachPayout = $this->calculateCoachPayout($booking, $amount);

        if ($coachPayout < 0 || $coachPayout > $amount) {
            throw new InvalidArgumentException('Coach payout must be between 0 and the booking amount.');
        }

        $paymentIntent = $this->createStripePaymentIntent([
            'amount' => $amount,
            'currency' => 'eur',
            'payment_method_types' => ['bancontact', 'card'],
            'capture_method' => 'automatic',
            'metadata' => [
                'session_id' => (string) $session->getKey(),
                'athlete_id' => (string) $athlete->getKey(),
                'coach_id' => (string) $coach->getKey(),
            ],
            'transfer_data' => [
                'destination' => $coachProfile->stripe_account_id,
                'amount' => $coachPayout,
            ],
        ]);

        if (! $this->isNonEmptyString($paymentIntent->id)) {
            throw new RuntimeException('Stripe did not return a payment intent identifier.');
        }

        $booking->forceFill([
            'stripe_payment_intent_id' => $paymentIntent->id,
        ])->save();

        return $paymentIntent;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function createStripePaymentIntent(array $payload): PaymentIntent
    {
        if ($this->createPaymentIntentUsing instanceof Closure) {
            return ($this->createPaymentIntentUsing)($payload);
        }

        Stripe::setApiKey((string) config('services.stripe.secret'));

        return PaymentIntent::create($payload);
    }

    protected function calculateCoachPayout(Booking $booking, int $amount): int
    {
        if ($this->calculateCoachPayoutUsing instanceof Closure) {
            return (int) ($this->calculateCoachPayoutUsing)($booking);
        }

        throw new RuntimeException('Coach payout calculation must be configured before creating a payment intent.');
    }

    private function isNonEmptyString(mixed $value): bool
    {
        return is_string($value) && $value !== '';
    }
}
