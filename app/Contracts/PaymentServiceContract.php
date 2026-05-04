<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Booking;
use Stripe\Checkout\Session as CheckoutSession;

/**
 * Contract for the Stripe payment service.
 *
 * Defines the public API consumed by services and controllers.
 * Enables interface-based mocking in tests for classes that depend on the service.
 */
interface PaymentServiceContract
{
    /**
     * Create a Stripe Checkout Session for a booking and persist its identifier.
     */
    public function createCheckoutSession(Booking $booking): CheckoutSession;
}
