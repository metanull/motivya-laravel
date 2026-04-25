<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\User;

final class InvoicePolicy
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
     * Coaches can list their own invoices; accountants can list all.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserRole::Coach, UserRole::Accountant], true);
    }

    /**
     * Coaches can view their own invoices; athletes can view invoices linked to their bookings'
     * sessions; accountants can view all invoices.
     */
    public function view(User $user, Invoice $invoice): bool
    {
        if ($user->role === UserRole::Accountant) {
            return true;
        }

        if ($user->role === UserRole::Coach) {
            return $user->id === $invoice->coach_id;
        }

        if ($user->role === UserRole::Athlete) {
            // Athlete may view invoices attached to sessions they have booked
            if ($invoice->sport_session_id === null) {
                return false;
            }

            return $invoice->sportSession
                ->bookings()
                ->where('athlete_id', $user->id)
                ->exists();
        }

        return false;
    }

    /**
     * Invoices are created by the system only — no manual creation by any user.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Only accountants (and admins, via before()) may export invoices.
     */
    public function export(User $user): bool
    {
        return $user->role === UserRole::Accountant;
    }
}
