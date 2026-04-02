# Domain Glossary

This glossary defines the canonical terms used throughout the Motivya codebase. AI agents, documentation, code identifiers, and UI strings **must** use these terms consistently.

> **Rule**: If a term appears in this glossary, use the English term in code (class names, variables, DB columns) and the localized term only in user-facing strings via `lang/` files.

## Roles

| Term | Code identifier | Definition |
|------|----------------|-----------|
| **Coach** | `UserRole::Coach` | A sports professional who creates and leads sessions. Has an enterprise number (Belgian). May or may not be VAT-subject. Must be approved (KYC) by an admin before creating sessions. |
| **Athlete** | `UserRole::Athlete` | An end-user who discovers, books, and pays for sessions. Also referred to as "client" or "end user" in the original French use cases — in code, always **Athlete**. |
| **Accountant** | `UserRole::Accountant` | A financial reviewer with read-only access to transactions, invoices, and exports. No in-app dashboard — accesses data via exports and reports. |
| **Admin** | `UserRole::Admin` | A platform operator who approves coaches, manages content, resolves disputes, and monitors platform health. MFA is mandatory. |

## Session Lifecycle

| Term | Code identifier | Definition |
|------|----------------|-----------|
| **Session** | `Session` model | A scheduled sports activity created by a coach. Defined by activity type, level, location, date, time, price, and participant capacity (min/max). |
| **Draft** | `SessionStatus::Draft` | Session created but not yet visible to athletes. Coach is still editing. |
| **Published** | `SessionStatus::Published` | Session visible to athletes for booking. Bookings are accepted but the session is not yet confirmed (min participants not reached). |
| **Confirmed** | `SessionStatus::Confirmed` | Minimum participant threshold reached. Session will take place. All booked athletes are notified. |
| **Completed** | `SessionStatus::Completed` | Session has ended (past `end_time`). Triggers payout and invoice generation. |
| **Cancelled** | `SessionStatus::Cancelled` | Session will not take place — either deadline passed without reaching threshold, or coach/admin cancelled. Triggers refunds. |

## Booking Lifecycle

| Term | Code identifier | Definition |
|------|----------------|-----------|
| **Booking** | `Booking` model | A reservation linking an athlete to a session. Created when payment succeeds. |
| **Pending Payment** | `BookingStatus::PendingPayment` | Booking created, payment intent initiated but not yet captured. |
| **Confirmed** | `BookingStatus::Confirmed` | Payment succeeded. Athlete is registered for the session. |
| **Cancelled** | `BookingStatus::Cancelled` | Athlete cancelled within the allowed window, or session was cancelled. |
| **Refunded** | `BookingStatus::Refunded` | Stripe refund processed. Money returned to athlete. |

## Financial Terms

| Term | Code identifier | Definition |
|------|----------------|-----------|
| **TVAC** | — | *Toutes Taxes Comprises* — price including VAT (21% in Belgium). This is what the athlete pays. |
| **HTVA** | — | *Hors TVA* — price excluding VAT. All platform margin calculations happen in HTVA. |
| **VAT-subject coach** | `User.is_vat_subject = true` | Coach registered for Belgian VAT. Issues invoices with VAT. Platform can recover input VAT. |
| **Non-subject coach** | `User.is_vat_subject = false` | Coach under the *régime de franchise* (below the VAT threshold). Issues invoices without VAT. Platform cannot recover VAT — payout is adjusted to preserve margin. |
| **Enterprise number** | `User.enterprise_number` | Belgian *numéro d'entreprise* (10 digits, format `0XXX.XXX.XXX`). All coaches must have one. |
| **Payout** | — | Money transferred from the platform to the coach after session completion. Formula: `payout = revenue_HTVA - target_margin_HTVA`. |
| **Commission** | — | The platform's fee on each transaction. Rate depends on subscription tier. |
| **Cents** | — | All monetary amounts in the database and code are stored as **integers in cents** (EUR). `1250` = €12,50. Never use floats. |

## Subscription Tiers

| Tier | Code identifier | Monthly fee | Commission rate | Use case |
|------|----------------|-------------|----------------|----------|
| **Freemium** | `SubscriptionPlan::Freemium` | €0 | 30% | Casual coaches, low volume |
| **Active** | `SubscriptionPlan::Active` | €39 TVAC | 20% | Regular coaches with moderate volume |
| **Premium** | `SubscriptionPlan::Premium` | €79 TVAC | 10% | High-volume professional coaches |

> **Auto-best-plan rule**: The system must always calculate the most advantageous tier for the coach and apply it automatically. A coach on Active who would pay less on Freemium is charged the Freemium rate.

## Invoicing

| Term | Definition |
|------|-----------|
| **PEPPOL** | Pan-European Public Procurement OnLine. A standardized e-invoicing framework. Mandatory in Belgium since January 2026 for B2B invoices. |
| **PEPPOL BIS 3.0** | The specific PEPPOL profile used: Business Interoperability Specifications version 3.0. Defines the XML schema for invoices. |
| **Credit note** | A document reversing a previously issued invoice (e.g., after refund). Must also be PEPPOL-compliant. |
| **Billit** | Third-party service for PEPPOL invoice routing. Integrated via Stripe App. |

## Session Discovery

| Term | Definition |
|------|-----------|
| **Activity type** | The sport category (Yoga, Running, Boxing, etc.). Managed by admin. Coaches select from predefined list. |
| **Level** | Difficulty: `beginner`, `intermediate`, `advanced`. Set per session by the coach. |
| **Postal code search** | Primary discovery method — Belgian 4-digit postal codes (1000-9999). |
| **Geolocation search** | Optional — browser geolocation with 2 km radius. Only if user grants permission. |
| **Cover image** | Activity-specific images uploaded by admin. Coaches select from the admin-curated library — they do not upload their own session images. |

## Cancellation Policies

| Scenario | Window | Effect |
|----------|--------|--------|
| Athlete cancels a **confirmed** session | ≥ 48h before start | Full refund |
| Athlete cancels a **confirmed** session | < 48h before start | No refund |
| Athlete cancels a **pending** session | ≥ 24h before start | Full refund |
| Athlete cancels a **pending** session | < 24h before start | No refund |
| Session cancelled (threshold not met) | Automatic at deadline | All athletes refunded |
| Coach/admin cancels session | Any time | All athletes refunded |

## Notifications

| Term | Definition |
|------|-----------|
| **Dispatch** | Events are dispatched by service classes. Never from controllers or Livewire components. |
| **Listener** | Handles one event and sends notifications. One listener per event-notification pair. |
| **Locale** | Notifications are sent in the **recipient's** preferred locale, not the sender's. Default: `fr`. |

## Localization

| Locale code | Language | Region | Usage |
|-------------|----------|--------|-------|
| `fr` | French | fr-BE | Default. Primary locale for all users. |
| `en` | English | en-GB | Secondary. |
| `nl` | Dutch | nl-BE | Tertiary. |

> **Note**: The original use cases use `en-UK` — the correct IETF tag is `en-GB`. Code and config must use `en`.
