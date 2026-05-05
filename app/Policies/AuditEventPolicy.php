<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\AuditEventType;
use App\Enums\UserRole;
use App\Models\AuditEvent;
use App\Models\User;

/**
 * Read-only policy for audit events.
 *
 * Admins can view all audit events.
 * Accountants can view financial/audit-relevant event types only.
 * No role can create, update, delete, force-delete, restore, or bulk-delete.
 */
final class AuditEventPolicy
{
    /**
     * Financial event types visible to accountants.
     *
     * @var list<AuditEventType>
     */
    private const FINANCIAL_TYPES = [
        AuditEventType::BookingCreated,
        AuditEventType::BookingPaymentStarted,
        AuditEventType::BookingPaymentConfirmed,
        AuditEventType::BookingPaymentFailed,
        AuditEventType::BookingCancelled,
        AuditEventType::BookingExpired,
        AuditEventType::RefundRequested,
        AuditEventType::RefundCompleted,
        AuditEventType::RefundFailed,
        AuditEventType::InvoiceGenerated,
        AuditEventType::InvoiceCreditNoteGenerated,
        AuditEventType::InvoiceXmlDownloaded,
        AuditEventType::PayoutStatementGenerated,
        AuditEventType::PayoutStatementSubmitted,
        AuditEventType::PayoutStatementApproved,
        AuditEventType::PayoutStatementBlocked,
        AuditEventType::PayoutStatementPaid,
        AuditEventType::AnomalyResolved,
        AuditEventType::AnomalyIgnored,
    ];

    /**
     * Return the list of event types visible to accountants.
     *
     * @return list<AuditEventType>
     */
    public static function financialTypes(): array
    {
        return self::FINANCIAL_TYPES;
    }

    /**
     * Determine whether the user can list audit events.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Admin || $user->role === UserRole::Accountant;
    }

    /**
     * Determine whether the user can view a specific audit event.
     *
     * Accountants are restricted to financial event types only.
     */
    public function view(User $user, AuditEvent $auditEvent): bool
    {
        if ($user->role === UserRole::Admin) {
            return true;
        }

        if ($user->role === UserRole::Accountant) {
            return in_array($auditEvent->event_type, self::FINANCIAL_TYPES, true);
        }

        return false;
    }

    /**
     * Audit events are immutable — no role may create them via the UI.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Audit events are immutable — no role may update them.
     */
    public function update(User $user, AuditEvent $auditEvent): bool
    {
        return false;
    }

    /**
     * Audit events are immutable — no role may delete them.
     */
    public function delete(User $user, AuditEvent $auditEvent): bool
    {
        return false;
    }

    /**
     * Audit events are immutable — no role may force-delete them.
     */
    public function forceDelete(User $user, AuditEvent $auditEvent): bool
    {
        return false;
    }

    /**
     * Audit events are immutable — no role may restore them.
     */
    public function restore(User $user, AuditEvent $auditEvent): bool
    {
        return false;
    }
}
