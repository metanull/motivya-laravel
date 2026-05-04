<?php

declare(strict_types=1);

namespace App\Enums;

enum AuditEventType: string
{
    case CoachApplicationSubmitted = 'coach.application_submitted';
    case CoachApproved = 'coach.approved';
    case CoachRejected = 'coach.rejected';

    case UserCreatedByAdmin = 'user.created_by_admin';
    case UserRoleChanged = 'user.role_changed';
    case UserSuspended = 'user.suspended';
    case UserReactivated = 'user.reactivated';

    case SessionCreated = 'session.created';
    case SessionUpdated = 'session.updated';
    case SessionPublished = 'session.published';
    case SessionCancelled = 'session.cancelled';
    case SessionCompleted = 'session.completed';
    case SessionDeleted = 'session.deleted';

    case BookingCreated = 'booking.created';
    case BookingPaymentStarted = 'booking.payment_started';
    case BookingPaymentConfirmed = 'booking.payment_confirmed';
    case BookingPaymentFailed = 'booking.payment_failed';
    case BookingCancelled = 'booking.cancelled';
    case BookingExpired = 'booking.expired';

    case RefundRequested = 'refund.requested';
    case RefundCompleted = 'refund.completed';
    case RefundFailed = 'refund.failed';

    case InvoiceGenerated = 'invoice.generated';
    case InvoiceCreditNoteGenerated = 'invoice.credit_note_generated';
    case InvoiceXmlDownloaded = 'invoice.xml_downloaded';

    case PayoutStatementGenerated = 'payout_statement.generated';
    case PayoutStatementSubmitted = 'payout_statement.submitted';
    case PayoutStatementApproved = 'payout_statement.approved';
    case PayoutStatementBlocked = 'payout_statement.blocked';
    case PayoutStatementPaid = 'payout_statement.paid';

    case AnomalyResolved = 'anomaly.resolved';
    case AnomalyIgnored = 'anomaly.ignored';
}
