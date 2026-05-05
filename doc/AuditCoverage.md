# Audit Coverage Checklist

This document is the canonical list of every MVP audit event type that must be recorded during normal platform operation. Each entry references the `AuditEventType` enum value and the service layer that records it.

Run the full audit test suite with:

```bash
php artisan test --filter=Audit
```

---

## Coach Events

| Event Type | Enum Case | Covered By | Service |
|---|---|---|---|
| `coach.application_submitted` | `CoachApplicationSubmitted` | `tests/Unit/Services/CoachApplicationServiceAuditTest.php` | `CoachApplicationService` |
| `coach.approved` | `CoachApproved` | `tests/Unit/Services/CoachApplicationServiceAuditTest.php` | `CoachApplicationService` |
| `coach.rejected` | `CoachRejected` | `tests/Unit/Services/CoachApplicationServiceAuditTest.php` | `CoachApplicationService` |

## User Administration Events

| Event Type | Enum Case | Covered By | Service |
|---|---|---|---|
| `user.created_by_admin` | `UserCreatedByAdmin` | `tests/Unit/Services/AdminServiceAuditTest.php` | `UserAdminService` |
| `user.role_changed` | `UserRoleChanged` | `tests/Unit/Services/AdminServiceAuditTest.php` | `UserAdminService` |
| `user.suspended` | `UserSuspended` | `tests/Unit/Services/AdminServiceAuditTest.php` | `UserAdminService` |
| `user.reactivated` | `UserReactivated` | `tests/Unit/Services/AdminServiceAuditTest.php` | `UserAdminService` |

## Session Events

| Event Type | Enum Case | Covered By | Service |
|---|---|---|---|
| `session.created` | `SessionCreated` | `tests/Unit/Services/SessionServiceAuditTest.php` | `SessionService` |
| `session.updated` | `SessionUpdated` | `tests/Unit/Services/SessionServiceAuditTest.php` | `SessionService` |
| `session.published` | `SessionPublished` | `tests/Unit/Services/SessionServiceAuditTest.php` | `SessionService` |
| `session.cancelled` | `SessionCancelled` | `tests/Unit/Services/SessionServiceAuditTest.php` | `SessionService` |
| `session.completed` | `SessionCompleted` | `tests/Unit/Services/SessionServiceAuditTest.php` | `SessionService` |
| `session.deleted` | `SessionDeleted` | `tests/Unit/Services/SessionServiceAuditTest.php` | `SessionService` |

## Booking Events

| Event Type | Enum Case | Covered By | Service |
|---|---|---|---|
| `booking.created` | `BookingCreated` | `tests/Unit/Services/BookingServiceAuditTest.php` | `BookingService` |
| `booking.payment_started` | `BookingPaymentStarted` | `tests/Unit/Services/PaymentServiceAuditTest.php` | `PaymentService` |
| `booking.payment_confirmed` | `BookingPaymentConfirmed` | `tests/Feature/Webhooks/StripeWebhookAuditTest.php` | `StripeWebhookController` / webhook flow |
| `booking.payment_failed` | `BookingPaymentFailed` | `tests/Feature/Webhooks/StripeWebhookAuditTest.php` | `StripeWebhookController` / webhook flow |
| `booking.cancelled` | `BookingCancelled` | `tests/Unit/Services/BookingServiceAuditTest.php` | `BookingService` |
| `booking.expired` | `BookingExpired` | `tests/Unit/Commands/ExpireUnpaidBookingsAuditTest.php` | `ExpireUnpaidBookings` command |

## Refund Events

| Event Type | Enum Case | Covered By | Service |
|---|---|---|---|
| `refund.requested` | `RefundRequested` | `tests/Unit/Services/RefundServiceAuditTest.php` | `RefundService` |
| `refund.completed` | `RefundCompleted` | `tests/Unit/Services/RefundServiceAuditTest.php` | `RefundService` |
| `refund.failed` | `RefundFailed` | `tests/Unit/Services/RefundServiceAuditTest.php` | `RefundService` |

## Invoice Events

| Event Type | Enum Case | Covered By | Service |
|---|---|---|---|
| `invoice.generated` | `InvoiceGenerated` | `tests/Unit/Services/InvoiceServiceAuditTest.php` | `InvoiceService` |
| `invoice.credit_note_generated` | `InvoiceCreditNoteGenerated` | `tests/Unit/Services/InvoiceServiceAuditTest.php` | `InvoiceService` |
| `invoice.xml_downloaded` | `InvoiceXmlDownloaded` | `tests/Feature/Controllers/Accountant/InvoiceXmlControllerAuditTest.php` | `InvoiceXmlController` |

## Payout Statement Events

| Event Type | Enum Case | Covered By | Service |
|---|---|---|---|
| `payout_statement.generated` | `PayoutStatementGenerated` | `tests/Unit/Services/CoachPayoutStatementServiceAuditTest.php` | `CoachPayoutStatementService` |
| `payout_statement.submitted` | `PayoutStatementSubmitted` | `tests/Unit/Services/CoachPayoutStatementServiceAuditTest.php` | `CoachPayoutStatementService` |
| `payout_statement.approved` | `PayoutStatementApproved` | `tests/Unit/Services/CoachPayoutStatementServiceAuditTest.php` | `CoachPayoutStatementService` |
| `payout_statement.blocked` | `PayoutStatementBlocked` | `tests/Unit/Services/CoachPayoutStatementServiceAuditTest.php` | `CoachPayoutStatementService` |
| `payout_statement.paid` | `PayoutStatementPaid` | `tests/Unit/Services/CoachPayoutStatementServiceAuditTest.php` | `CoachPayoutStatementService` |

## Anomaly Events

| Event Type | Enum Case | Covered By | Service |
|---|---|---|---|
| `anomaly.resolved` | `AnomalyResolved` | `tests/Unit/Services/AnomalyDetectorServiceAuditTest.php` | `AnomalyDetectorService` |
| `anomaly.ignored` | `AnomalyIgnored` | `tests/Unit/Services/AnomalyDetectorServiceAuditTest.php` | `AnomalyDetectorService` |

---

## Rules

1. **Service layer only**: All `AuditService::record()` calls must originate from `app/Services/` — never from controllers, Livewire components, or Blade views.
2. **Enum values only**: Event type strings must come from `AuditEventType` enum values — never hardcoded strings.
3. **Transaction safety**: Audit rows must be written inside the same `DB::transaction()` as the business write they describe.
4. **Structured log**: Every `AuditService::record()` call writes both a database row (`audit_events`) and a structured log entry to the `audit` channel.
5. **Read-only viewer**: The audit viewer UI (`admin.audit-events.*`, `accountant.audit-events.*`) must never expose create, edit, delete, force-delete, restore, or bulk-delete actions.

---

## Audit Viewer Access Matrix

| Role | admin routes | accountant routes | Event types visible |
|------|-------------|-------------------|---------------------|
| Admin | ✅ All | ✅ All | All event types |
| Accountant | ❌ | ✅ | Financial only (booking, refund, invoice, payout, anomaly) |
| Coach | ❌ | ❌ | None |
| Athlete | ❌ | ❌ | None |
| Guest | ❌ | ❌ | None |
