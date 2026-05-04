<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Booking;
use App\Models\SportSession;
use App\Models\User;

/**
 * Contract for the booking lifecycle service.
 *
 * Defines the public API consumed by other services and Livewire components.
 * Enables interface-based mocking in tests for classes that depend on the service.
 */
interface BookingServiceContract
{
    /**
     * Create a booking atomically and update session capacity.
     */
    public function book(SportSession $session, User $athlete): Booking;

    /**
     * Cancel a booking for the given athlete and release the participant slot.
     */
    public function cancel(Booking $booking, User $athlete): void;
}
