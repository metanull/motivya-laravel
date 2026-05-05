<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\PaymentServiceContract;
use App\Enums\AuditEventType;
use App\Enums\AuditOperation;
use App\Models\Booking;
use App\Services\Audit\AuditService;
use App\Services\Audit\AuditSubject;
use Closure;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use Stripe\Checkout\Session as CheckoutSession;
use Stripe\Stripe;

final class PaymentService implements PaymentServiceContract
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly ?Closure $createCheckoutSessionUsing = null,
        private readonly ?Closure $calculateCoachPayoutUsing = null,
    ) {}

    /**
     * Create a Stripe Checkout Session for a booking and persist its identifier.
     */
    public function createCheckoutSession(Booking $booking): CheckoutSession
    {
        $booking->loadMissing('sportSession.coach.coachProfile', 'athlete');

        $session = $booking->sportSession;
        $athlete = $booking->athlete;
        $coach = $session->coach;
        $coachProfile = $coach?->coachProfile;

        if (! $this->isNonEmptyString($coachProfile?->stripe_account_id)) {
            throw new InvalidArgumentException('Coach must have a Stripe account identifier before creating a checkout session.');
        }

        $amount = $session->price_per_person;
        $coachPayout = $this->calculateCoachPayout($booking, $amount);

        if ($coachPayout < 0 || $coachPayout > $amount) {
            throw new InvalidArgumentException('Coach payout must be between 0 and the booking amount.');
        }

        $checkoutSession = $this->createStripeCheckoutSession([
            'mode' => 'payment',
            'payment_method_types' => ['bancontact', 'card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => $amount,
                    'product_data' => ['name' => $session->title],
                ],
                'quantity' => 1,
            ]],
            'payment_intent_data' => [
                'transfer_data' => [
                    'destination' => $coachProfile->stripe_account_id,
                    'amount' => $coachPayout,
                ],
                'metadata' => [
                    'session_id' => (string) $session->getKey(),
                    'athlete_id' => (string) $athlete->getKey(),
                    'coach_id' => (string) $coach->getKey(),
                ],
            ],
            'metadata' => [
                'session_id' => (string) $session->getKey(),
                'athlete_id' => (string) $athlete->getKey(),
                'coach_id' => (string) $coach->getKey(),
            ],
            'success_url' => route('bookings.payment-return', ['status' => 'success', 'booking' => $booking->getKey()]),
            'cancel_url' => route('bookings.payment-return', ['status' => 'cancel', 'booking' => $booking->getKey()]),
        ]);

        if (! $this->isNonEmptyString($checkoutSession->id)) {
            throw new RuntimeException('Stripe did not return a checkout session identifier.');
        }

        DB::transaction(function () use ($booking, $checkoutSession): void {
            $booking->forceFill([
                'stripe_checkout_session_id' => $checkoutSession->id,
            ])->save();

            $this->auditService->record(
                AuditEventType::BookingPaymentStarted,
                AuditOperation::Payment,
                $booking,
                subjects: [AuditSubject::primary($booking)],
                newValues: ['stripe_checkout_session_id' => $checkoutSession->id],
            );
        });

        return $checkoutSession;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function createStripeCheckoutSession(array $payload): CheckoutSession
    {
        if ($this->createCheckoutSessionUsing instanceof Closure) {
            return ($this->createCheckoutSessionUsing)($payload);
        }

        Stripe::setApiKey((string) config('services.stripe.secret'));

        return CheckoutSession::create($payload);
    }

    protected function calculateCoachPayout(Booking $booking, int $amount): int
    {
        if ($this->calculateCoachPayoutUsing instanceof Closure) {
            return (int) ($this->calculateCoachPayoutUsing)($booking, $amount);
        }

        return $amount;
    }

    private function isNonEmptyString(mixed $value): bool
    {
        return is_string($value) && $value !== '';
    }
}
