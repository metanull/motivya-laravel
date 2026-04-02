---
description: "Use when implementing core business logic, model relationships, service classes, state transitions, financial calculations, or any code that touches the domain model. Covers the non-negotiable business rules that all code must respect."
applyTo: "app/Models/**,app/Services/**,app/Enums/**,database/migrations/**"
---

# Domain Concepts — Non-Negotiable Business Rules

These rules are derived from [doc/Glossary.md](../../doc/Glossary.md), [doc/Decisions.md](../../doc/Decisions.md), and [doc/Scope.md](../../doc/Scope.md). They are **locked** — do not deviate without updating the source documents.

## Terminology

Use the exact terms from [doc/Glossary.md](../../doc/Glossary.md). In particular:

- The end-user who books sessions is an **Athlete** — never "client", "customer", or "user" in domain code
- The service provider is a **Coach** — never "instructor", "trainer", or "provider"
- A scheduled sports activity is a **Session** — never "class", "event", or "course"
- A reservation is a **Booking** — never "reservation", "order", or "ticket"

## Four Roles — No More, No Less

The system has exactly four roles: `Coach`, `Athlete`, `Accountant`, `Admin`. Defined as a `UserRole` backed enum. Never add roles without updating [doc/Glossary.md](../../doc/Glossary.md) and [auth-roles.instructions.md](auth-roles.instructions.md).

## Money is Always Cents

All monetary amounts in the database, code, and API responses are **integers in cents** (EUR). `1250` = €12,50. Never use `float`, `decimal`, or formatted strings in business logic. Formatting to `€ XX,XX` happens only at the view layer via the `<x-money>` Blade component.

## Session Lifecycle: 5 States

```
Draft → Published → Confirmed → Completed
                  ↘ Cancelled ↙
```

- **Draft → Published**: Coach publishes; session becomes visible to athletes
- **Published → Confirmed**: `current_participants >= min_participants`
- **Published → Cancelled**: Deadline passed without reaching threshold, or coach/admin cancels
- **Confirmed → Completed**: `end_time` has passed
- **Confirmed → Cancelled**: Coach/admin cancels (triggers refunds for all)

Never skip states. Never move backwards (Confirmed → Published is forbidden).

## Booking Lifecycle: 4 States

```
PendingPayment → Confirmed → Cancelled
                           → Refunded
```

- **PendingPayment → Confirmed**: Stripe `payment_intent.succeeded` webhook
- **Confirmed → Cancelled**: Athlete cancels within window, or session cancelled
- **Confirmed → Refunded**: Stripe refund processed
- **Cancelled → Refunded**: Refund triggered after cancellation

## Atomic Booking — Non-Negotiable

Booking a session MUST use a database transaction with pessimistic locking (`lockForUpdate`). The check on `current_participants < max_participants` and the increment MUST happen in the same transaction. This prevents overbooking under concurrent requests.

## VAT Logic — Two Paths

1. **VAT-subject coach** (`is_vat_subject = true`): Invoices with VAT. Platform recovers input VAT from the coach's invoice.
2. **Non-subject coach** (`is_vat_subject = false`): Invoices without VAT. Platform cannot recover VAT. Payout is adjusted downward.

**Formula**: `payout = revenue_HTVA - target_margin_HTVA`

This formula applies to both VAT-subject and non-subject coaches. The difference is that for non-subject coaches, the payout is lower because the platform absorbs the unrecoverable VAT.

Never compute VAT in controllers, Livewire components, or Blade templates. All VAT logic lives in `app/Services/`.

## Subscription Auto-Best-Plan

The system must always apply the **most advantageous** tier for the coach:

| Tier | Monthly fee | Commission |
|------|-------------|-----------|
| Freemium | €0 | 30% |
| Active | €39 TVAC | 20% |
| Premium | €79 TVAC | 10% |

If a coach on Active would pay less on Freemium, the Freemium rate applies. The system calculates all three tiers and picks the lowest cost for the coach.

## Cancellation Windows

| Scenario | Window | Effect |
|----------|--------|--------|
| Athlete cancels confirmed session | ≥ 48h before | Full refund |
| Athlete cancels confirmed session | < 48h before | No refund |
| Athlete cancels pending session | ≥ 24h before | Full refund |
| Athlete cancels pending session | < 24h before | No refund |
| Session cancelled (threshold not met) | Automatic | All athletes refunded |
| Coach/admin cancels | Any time | All athletes refunded |

## Cover Images — Admin-Curated Only

Activity cover images are uploaded by admins. Coaches **select** from the admin-curated library. Coaches do not upload their own session images.

## Event-Driven Side Effects

All notifications, invoice generation, and payout processing are triggered by **Events dispatched from Service classes**. Never send notifications or create invoices directly from controllers, Livewire components, or middleware.

## Scope Enforcement

Before implementing a feature, check [doc/Scope.md](../../doc/Scope.md). If a feature is listed as Phase 2, Nice-to-have, or Out-of-scope, **ask the user before proceeding**. Do not silently implement deferred features.
