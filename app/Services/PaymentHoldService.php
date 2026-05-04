<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\BookingServiceContract;
use App\Contracts\PaymentServiceContract;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use InvalidArgumentException;
use Stripe\Checkout\Session as CheckoutSession;

final class PaymentHoldService
{
    public function __construct(
        private readonly BookingServiceContract $bookingService,
        private readonly PaymentServiceContract $paymentService,
    ) {}

    /**
     * Retry payment for a pending booking.
     *
     * Guards:
     *  - booking must belong to the given athlete
     *  - booking must have PendingPayment status
     *  - booking must not be expired
     *
     * Creates and returns a new Stripe Checkout Session.
     *
     * @throws AuthorizationException if booking does not belong to athlete
     * @throws InvalidArgumentException if booking is not in PendingPayment status
     * @throws InvalidArgumentException if payment hold has expired
     */
    public function retryPayment(Booking $booking, User $athlete): CheckoutSession
    {
        if ($booking->athlete_id !== $athlete->getKey()) {
            throw new AuthorizationException;
        }

        if ($booking->status !== BookingStatus::PendingPayment) {
            throw new InvalidArgumentException(__('bookings.error_payment_hold_not_pending'));
        }

        if ($booking->isPaymentExpired()) {
            throw new InvalidArgumentException(__('bookings.error_payment_hold_expired'));
        }

        return $this->paymentService->createCheckoutSession($booking);
    }

    /**
     * Cancel a pending payment hold.
     *
     * Releases the participant slot and marks the booking as cancelled.
     * Delegates to BookingService::cancel().
     *
     * @throws AuthorizationException if booking does not belong to athlete
     * @throws InvalidArgumentException if booking cannot be cancelled
     */
    public function cancelHold(Booking $booking, User $athlete): void
    {
        $this->bookingService->cancel($booking, $athlete);
    }
}
