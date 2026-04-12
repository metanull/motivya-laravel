<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\SportSession;
use App\Models\User;

final class BookingPolicy
{
    /**
     * Admin bypass — grants all abilities.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->role === UserRole::Admin) {
            return true;
        }

        return null;
    }

    /**
     * Only athletes can create bookings.
     * Athletes cannot book their own sessions (if they are also a coach).
     */
    public function create(User $user, SportSession $session): bool
    {
        if ($user->role !== UserRole::Athlete) {
            return false;
        }

        // Prevent booking own session (edge case: user is athlete but also owns sessions as coach)
        return $user->id !== $session->coach_id;
    }

    /**
     * Own booking (athlete), session owner (coach), or admin.
     */
    public function view(User $user, Booking $booking): bool
    {
        // Athlete can view own booking
        if ($user->id === $booking->athlete_id) {
            return true;
        }

        // Coach can view bookings for their sessions
        if ($user->role === UserRole::Coach) {
            return $user->id === $booking->sportSession->coach_id;
        }

        return false;
    }

    /**
     * Own booking (athlete) or admin.
     */
    public function cancel(User $user, Booking $booking): bool
    {
        return $user->id === $booking->athlete_id;
    }

    /**
     * Admin only (manual refunds). Handled by before().
     */
    public function refund(User $user, Booking $booking): bool
    {
        return false;
    }
}
