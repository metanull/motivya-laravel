# Epic 3: Athlete Experience & Payments

> **Milestone**: Epic 3: Athlete Experience & Payments
> **Depends on**: Epic 1 (auth, roles), Epic 2 (sessions, coach profiles)
> **Blocked by**: Stripe test API keys + Connect enabled (Issue #15)
> **Goal**: Athletes can discover, filter, and book sessions. Stripe payments (Card + Bancontact). Refund and cancellation flows. Athlete dashboard.

---

## E3-S01 · Booking model + migration

**Labels**: `booking`, `infrastructure`
**Size**: S
**Dependencies**: E2-S03

The Booking model linking athletes to sessions.

### Acceptance Criteria

- [ ] `app/Enums/BookingStatus.php` — backed string enum: `pending_payment`, `confirmed`, `cancelled`, `refunded`
- [ ] `bookings` table: `id`, `sport_session_id` (FK), `athlete_id` (FK to users), `status`, `stripe_payment_intent_id` (nullable string), `amount_paid` (integer cents), `cancelled_at` (nullable timestamp), `refunded_at` (nullable timestamp), `timestamps`
- [ ] `Booking` model with casts, relationships: `belongsTo(SportSession)`, `belongsTo(User, 'athlete_id')`
- [ ] `SportSession` model: `hasMany(Booking)` relationship
- [ ] `User` model: `hasMany(Booking, 'athlete_id')` relationship
- [ ] `BookingFactory`
- [ ] Unique constraint: `(sport_session_id, athlete_id)` — no duplicate bookings
- [ ] Unit test for model, casts, relationships

### Files to create/modify

- `app/Enums/BookingStatus.php`
- `app/Models/Booking.php`
- `database/migrations/xxxx_create_bookings_table.php`
- `database/factories/BookingFactory.php`
- `app/Models/SportSession.php` (add relationship)
- `app/Models/User.php` (add relationship)
- `tests/Unit/Models/BookingTest.php`
- `tests/Unit/Enums/BookingStatusTest.php`

---

## E3-S02 · BookingPolicy

**Labels**: `auth`, `booking`
**Size**: S
**Dependencies**: E3-S01

Authorization rules for booking operations.

### Acceptance Criteria

- [ ] `app/Policies/BookingPolicy.php`
- [ ] `create`: athlete only; cannot book own session (if also a coach)
- [ ] `view`: own booking (athlete) or session owner (coach) or admin
- [ ] `cancel`: own booking (athlete, within cancellation window) or admin
- [ ] `refund`: admin only (manual refunds)
- [ ] `before()`: admin bypass
- [ ] Feature test: all roles × scenarios

### Files to create/modify

- `app/Policies/BookingPolicy.php`
- `tests/Feature/Policies/BookingPolicyTest.php`

---

## E3-S03 · Install Laravel Cashier + Stripe config

**Labels**: `payments`, `infrastructure`
**Size**: S
**Dependencies**: none (infrastructure)
**Blocked by**: Stripe API keys (Issue #15)

Install Cashier and configure Stripe credentials.

### Acceptance Criteria

- [ ] `laravel/cashier` installed via Composer
- [ ] `config/cashier.php` published
- [ ] `config/services.php` updated with Stripe config block (`key`, `secret`, `webhook.secret`)
- [ ] `.env.example` updated with `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`
- [ ] Cashier migrations run (Cashier handles its own tables)
- [ ] `User` model uses `Billable` trait (Cashier)
- [ ] Verify Stripe connection works in test mode (artisan command or tinker)

### Files to create/modify

- `composer.json`
- `config/cashier.php`
- `config/services.php`
- `.env.example`
- `app/Models/User.php`

---

## E3-S04 · Stripe Connect — coach Express account creation

**Labels**: `payments`, `coach`
**Size**: M
**Dependencies**: E3-S03, E2-S02

Service to create a Stripe Express account for an approved coach and generate the onboarding link.

### Acceptance Criteria

- [ ] `app/Services/StripeConnectService.php`
- [ ] `createExpressAccount(CoachProfile $coach): string` — creates Express account, returns `acct_...` ID
- [ ] Sets `country: 'BE'`, `mcc: '7941'`, `capabilities: transfers + bancontact_payments`
- [ ] Stores `stripe_account_id` on `coach_profiles` table
- [ ] `generateOnboardingLink(CoachProfile $coach): string` — generates Account Link URL for Stripe-hosted onboarding
- [ ] Return URL: `/coach/stripe/return`, Refresh URL: `/coach/stripe/refresh`
- [ ] Routes for return/refresh handling
- [ ] Unit test with mocked Stripe API

### Files to create/modify

- `app/Services/StripeConnectService.php`
- `routes/web.php`
- `tests/Unit/Services/StripeConnectServiceTest.php`

---

## E3-S05 · Stripe webhook controller + signature verification

**Labels**: `payments`, `infrastructure`
**Size**: M
**Dependencies**: E3-S03

Webhook endpoint that verifies Stripe signatures and dispatches events.

### Acceptance Criteria

- [ ] `app/Http/Controllers/StripeWebhookController.php`
- [ ] Route: `POST /stripe/webhook` — excluded from CSRF
- [ ] Verifies webhook signature using `STRIPE_WEBHOOK_SECRET`
- [ ] `processed_webhooks` table for idempotency (stores `stripe_event_id`)
- [ ] Skips duplicate events
- [ ] Returns HTTP 200 even on processing errors (logs the error)
- [ ] Returns HTTP 500 only for transient failures (triggers Stripe retry)
- [ ] Dispatches internal Laravel events based on Stripe event type
- [ ] Feature test: valid signature processed; invalid signature rejected; duplicate event skipped

### Files to create/modify

- `app/Http/Controllers/StripeWebhookController.php`
- `database/migrations/xxxx_create_processed_webhooks_table.php`
- `routes/web.php` (webhook route, CSRF excluded)
- `tests/Feature/Webhooks/StripeWebhookTest.php`

---

## E3-S06 · BookingService — atomic booking logic

**Labels**: `booking`
**Size**: M
**Dependencies**: E3-S01, E2-S03

Core booking service with atomic overbooking prevention.

### Acceptance Criteria

- [ ] `app/Services/BookingService.php`
- [ ] `book(SportSession $session, User $athlete): Booking` — atomic DB transaction with `lockForUpdate`
- [ ] Checks: session is `published` or `confirmed`; `current_participants < max_participants`; athlete not already booked
- [ ] Creates `Booking` with status `pending_payment`
- [ ] Increments `current_participants`
- [ ] If `current_participants >= min_participants` and session is `published` → transitions to `confirmed`; dispatches `SessionConfirmed` event
- [ ] Throws `SessionFullException`, `SessionNotBookableException`, `AlreadyBookedException` as appropriate
- [ ] Unit test: successful booking; full session; duplicate prevention; concurrent booking simulation

### Files to create/modify

- `app/Services/BookingService.php`
- `app/Exceptions/SessionFullException.php`
- `app/Exceptions/SessionNotBookableException.php`
- `app/Exceptions/AlreadyBookedException.php`
- `app/Events/SessionConfirmed.php`
- `tests/Unit/Services/BookingServiceTest.php`

---

## E3-S07 · Payment flow — PaymentIntent creation

**Labels**: `payments`, `booking`
**Size**: M
**Dependencies**: E3-S03, E3-S04, E3-S06

Create Stripe PaymentIntents when athletes book sessions.

### Acceptance Criteria

- [ ] `app/Services/PaymentService.php`
- [ ] `createPaymentIntent(Booking $booking): PaymentIntent` — creates PI with amount in cents, EUR, metadata (session_id, athlete_id, coach_id)
- [ ] `payment_method_types: ['bancontact', 'card']`
- [ ] `transfer_data.destination` set to coach's `stripe_account_id`
- [ ] `transfer_data.amount` computed as coach payout (delegates to payout service, basic version for now)
- [ ] `stripe_payment_intent_id` stored on booking
- [ ] Unit test with mocked Stripe API

### Files to create/modify

- `app/Services/PaymentService.php`
- `tests/Unit/Services/PaymentServiceTest.php`

---

## E3-S08 · Booking Livewire components (book + payment)

**Labels**: `booking`, `ui`, `athlete`
**Size**: M
**Dependencies**: E3-S06, E3-S07, E2-S14

Athlete-facing booking UI on the session detail page.

### Acceptance Criteria

- [ ] `app/Livewire/Booking/Book.php` — "Book this session" button on session show page
- [ ] On click: calls `BookingService::book()` then `PaymentService::createPaymentIntent()`
- [ ] Redirects to Stripe Checkout or embedded payment form (Stripe.js)
- [ ] Shows spots remaining, price, and booking confirmation status
- [ ] Only authenticated athletes can book (policy check)
- [ ] Error handling: session full, already booked, session not bookable
- [ ] All strings localized
- [ ] Livewire component test

### Files to create/modify

- `app/Livewire/Booking/Book.php`
- `resources/views/livewire/booking/book.blade.php`
- `tests/Feature/Livewire/Booking/BookTest.php`

---

## E3-S09 · Webhook handler — payment_intent.succeeded

**Labels**: `payments`, `booking`
**Size**: S
**Dependencies**: E3-S05, E3-S06

Handle successful payments: confirm booking, dispatch events.

### Acceptance Criteria

- [ ] Listener in webhook controller for `payment_intent.succeeded`
- [ ] Finds booking by `stripe_payment_intent_id` from metadata
- [ ] Transitions booking from `pending_payment` to `confirmed`
- [ ] Dispatches `BookingCreated` event
- [ ] Feature test with mocked webhook payload

### Files to create/modify

- `app/Http/Controllers/StripeWebhookController.php` (add handler)
- `app/Events/BookingCreated.php`
- `tests/Feature/Webhooks/PaymentSucceededTest.php`

---

## E3-S10 · Webhook handler — payment_intent.payment_failed

**Labels**: `payments`, `booking`
**Size**: S
**Dependencies**: E3-S05, E3-S06

Handle failed payments: release booking slot, notify athlete.

### Acceptance Criteria

- [ ] Listener for `payment_intent.payment_failed`
- [ ] Finds booking by `stripe_payment_intent_id`
- [ ] Cancels booking, decrements `current_participants`
- [ ] Dispatches `BookingCancelled` event with reason `payment_failed`
- [ ] Feature test

### Files to create/modify

- `app/Http/Controllers/StripeWebhookController.php`
- `app/Events/BookingCancelled.php`
- `tests/Feature/Webhooks/PaymentFailedTest.php`

---

## E3-S11 · Webhook handler — account.updated (coach onboarding)

**Labels**: `payments`, `coach`
**Size**: S
**Dependencies**: E3-S05, E3-S04

Track coach Stripe onboarding completion.

### Acceptance Criteria

- [ ] Listener for `account.updated`
- [ ] Checks `details_submitted` and `charges_enabled`
- [ ] Sets `stripe_onboarding_complete = true` on `coach_profiles`
- [ ] Dispatches `CoachStripeOnboardingComplete` event
- [ ] Feature test

### Files to create/modify

- `app/Http/Controllers/StripeWebhookController.php`
- `app/Events/CoachStripeOnboardingComplete.php`
- `tests/Feature/Webhooks/AccountUpdatedTest.php`

---

## E3-S12 · Booking cancellation service

**Labels**: `booking`
**Size**: M
**Dependencies**: E3-S06

Athlete cancellation with time-window enforcement.

### Acceptance Criteria

- [ ] `BookingService::cancel(Booking $booking, User $athlete): void`
- [ ] Enforces cancellation windows:
  - Confirmed session: ≥ 48h before → refund eligible; < 48h → no refund
  - Published (pending) session: ≥ 24h before → refund eligible; < 24h → no refund
- [ ] Decrements `current_participants`
- [ ] If session drops below `min_participants` and session is `confirmed` → transitions back is **not allowed** (per domain rules); session remains confirmed
- [ ] Sets `cancelled_at` on booking, transitions to `cancelled`
- [ ] Dispatches `BookingCancelled` event (with refund eligibility flag)
- [ ] Unit test: within window → eligible; outside window → not eligible

### Files to create/modify

- `app/Services/BookingService.php` (cancel method)
- `tests/Unit/Services/BookingCancellationTest.php`

---

## E3-S13 · Refund service + charge.refunded webhook

**Labels**: `payments`, `booking`
**Size**: M
**Dependencies**: E3-S12, E3-S05

Process Stripe refunds and handle the webhook callback.

### Acceptance Criteria

- [ ] `app/Services/RefundService.php`
- [ ] `refund(Booking $booking): void` — creates Stripe refund for the PaymentIntent
- [ ] Transitions booking to `refunded`, sets `refunded_at`
- [ ] Webhook handler for `charge.refunded` — confirms refund processed
- [ ] Dispatches `BookingRefunded` event
- [ ] Unit test + webhook feature test

### Files to create/modify

- `app/Services/RefundService.php`
- `app/Events/BookingRefunded.php`
- `app/Http/Controllers/StripeWebhookController.php`
- `tests/Unit/Services/RefundServiceTest.php`
- `tests/Feature/Webhooks/ChargeRefundedTest.php`

---

## E3-S14 · Session cancellation → mass refund

**Labels**: `booking`, `payments`
**Size**: S
**Dependencies**: E3-S13, E2-S07

When a session is cancelled (threshold not met or coach cancels), refund all confirmed bookings.

### Acceptance Criteria

- [ ] `app/Listeners/RefundAllBookingsOnSessionCancellation.php` — listens to `SessionCancelled`
- [ ] Iterates all `confirmed` bookings for the session and calls `RefundService::refund()`
- [ ] Listener registered in `EventServiceProvider`
- [ ] Feature test: cancelling session with 3 bookings triggers 3 refunds

### Files to create/modify

- `app/Listeners/RefundAllBookingsOnSessionCancellation.php`
- `app/Providers/EventServiceProvider.php`
- `tests/Feature/Listeners/RefundAllBookingsTest.php`

---

## E3-S15 · Booking cancellation UI

**Labels**: `booking`, `ui`, `athlete`
**Size**: S
**Dependencies**: E3-S12

Livewire component for athletes to cancel their booking.

### Acceptance Criteria

- [ ] `app/Livewire/Booking/Cancel.php`
- [ ] Shows cancellation window info (eligible or not, with explanation)
- [ ] Confirmation modal before cancelling
- [ ] Calls `BookingService::cancel()`
- [ ] Displays success/error toast
- [ ] All strings localized
- [ ] Livewire component test

### Files to create/modify

- `app/Livewire/Booking/Cancel.php`
- `resources/views/livewire/booking/cancel.blade.php`
- `tests/Feature/Livewire/Booking/CancelTest.php`

---

## E3-S16 · Session discovery — list + filters

**Labels**: `athlete`, `ui`
**Size**: M
**Dependencies**: E2-S03, E1-S08

Session discovery page with search and filters.

### Acceptance Criteria

- [ ] `app/Livewire/Session/Index.php`
- [ ] Shows only `published` and `confirmed` sessions (future dates)
- [ ] Filters: activity type, level, date range, time range, postal code
- [ ] Search by postal code: sessions within same code or nearby codes
- [ ] Pagination (12 per page)
- [ ] Each card: title, activity, level, coach name, date, time, price, spots remaining
- [ ] Route: `GET /sessions`
- [ ] All strings localized
- [ ] Livewire component test with various filter combinations

### Files to create/modify

- `app/Livewire/Session/Index.php`
- `resources/views/livewire/session/index.blade.php`
- `routes/web.php`
- `tests/Feature/Livewire/Session/IndexTest.php`

---

## E3-S17 · Geolocation search (browser + distance)

**Labels**: `athlete`, `ui`
**Size**: M
**Dependencies**: E3-S16

Optional browser geolocation with 2 km radius search using MySQL distance queries.

### Acceptance Criteria

- [ ] JavaScript: request browser geolocation (with user consent)
- [ ] Send lat/lng to Livewire component
- [ ] `SessionQueryService` or scope on `SportSession` model: Haversine formula query for sessions within radius (default 2 km, adjustable)
- [ ] Falls back to postal code search if geolocation denied
- [ ] Feature test: spatial query returns correct sessions

### Files to create/modify

- `app/Services/SessionQueryService.php` (or model scope)
- `app/Livewire/Session/Index.php` (add geolocation handling)
- `resources/views/livewire/session/index.blade.php` (JS geolocation)
- `tests/Feature/Session/GeolocationSearchTest.php`

---

## E3-S18 · Interactive map with session markers

**Labels**: `athlete`, `ui`
**Size**: M
**Dependencies**: E3-S16

Map component showing session locations with clustering.

### Acceptance Criteria

- [ ] Map library integrated (Leaflet.js with OpenStreetMap tiles — free, no API key)
- [ ] Sessions with lat/lng displayed as markers
- [ ] Marker clusters for dense areas
- [ ] Clicking marker shows session summary popup (title, coach, date, price, link to detail)
- [ ] Map syncs with current filters
- [ ] Alpine.js component for map interactivity
- [ ] Feature test: map renders with markers from DB data

### Files to create/modify

- `resources/views/components/session-map.blade.php` (or Alpine component)
- `resources/js/session-map.js`
- `app/Livewire/Session/Index.php` (pass marker data)
- `package.json` (leaflet dependency)
- `tests/Feature/Livewire/Session/MapTest.php`

---

## E3-S19 · Athlete dashboard

**Labels**: `athlete`, `ui`
**Size**: M
**Dependencies**: E3-S01, E1-S08

Athlete's personal dashboard showing bookings.

### Acceptance Criteria

- [ ] `app/Livewire/Athlete/Dashboard.php`
- [ ] Sections: Upcoming bookings, Past bookings
- [ ] Each booking card: session title, coach, date, time, status badge, price
- [ ] Quick links: Explore sessions, Favourites, Profile
- [ ] Cancel button on upcoming bookings (links to cancellation flow)
- [ ] Protected by `role:athlete` middleware
- [ ] All strings localized
- [ ] Livewire component test

### Files to create/modify

- `app/Livewire/Athlete/Dashboard.php`
- `resources/views/livewire/athlete/dashboard.blade.php`
- `routes/web.php`
- `tests/Feature/Livewire/Athlete/DashboardTest.php`

---

## E3-S20 · Favourites (save sessions)

**Labels**: `athlete`, `ui`
**Size**: S
**Dependencies**: E3-S16

Athletes can favourite sessions and view their favourites list.

### Acceptance Criteria

- [ ] `favourites` pivot table: `user_id`, `sport_session_id`, `created_at`
- [ ] `User` model: `belongsToMany(SportSession, 'favourites')` relationship
- [ ] Toggle favourite button on session cards and detail page (heart icon)
- [ ] `app/Livewire/Athlete/Favourites.php` — list of favourited sessions
- [ ] Route: `GET /favourites`
- [ ] All strings localized
- [ ] Feature test: toggle favourite, list favourites

### Files to create/modify

- `database/migrations/xxxx_create_favourites_table.php`
- `app/Models/User.php` (relationship)
- `app/Livewire/Athlete/Favourites.php`
- `resources/views/livewire/athlete/favourites.blade.php`
- `routes/web.php`
- `tests/Feature/Livewire/Athlete/FavouritesTest.php`

---

## E3-S21 · Session reminder emails (24h before)

**Labels**: `messaging`, `athlete`
**Size**: S
**Dependencies**: E3-S01

Scheduled email reminders sent 24h before session start.

### Acceptance Criteria

- [ ] `app/Notifications/SessionReminderNotification.php` — email with session details
- [ ] Scheduled command: runs daily (or hourly), finds sessions starting in 24h with confirmed bookings
- [ ] Sends reminder to each booked athlete
- [ ] Notification content localized
- [ ] Feature test: command sends reminders for qualifying sessions; does not double-send

### Files to create/modify

- `app/Notifications/SessionReminderNotification.php`
- `app/Console/Commands/SendSessionReminders.php` (or schedule in `routes/console.php`)
- `lang/fr/notifications.php`, `lang/en/notifications.php`, `lang/nl/notifications.php`
- `tests/Feature/Commands/SendSessionRemindersTest.php`

---

## E3-S22 · Booking confirmation + cancellation email notifications

**Labels**: `messaging`, `booking`
**Size**: S
**Dependencies**: E3-S09, E3-S10

Email notifications for booking lifecycle events.

### Acceptance Criteria

- [ ] `app/Notifications/BookingConfirmedNotification.php` — email to athlete
- [ ] `app/Notifications/BookingCancelledNotification.php` — email to athlete (with refund info if applicable)
- [ ] `app/Listeners/SendBookingConfirmedNotification.php` — listens to `BookingCreated`
- [ ] `app/Listeners/SendBookingCancelledNotification.php` — listens to `BookingCancelled`
- [ ] Notification content localized
- [ ] Feature test: events trigger correct notifications

### Files to create/modify

- `app/Notifications/BookingConfirmedNotification.php`
- `app/Notifications/BookingCancelledNotification.php`
- `app/Listeners/SendBookingConfirmedNotification.php`
- `app/Listeners/SendBookingCancelledNotification.php`
- `app/Providers/EventServiceProvider.php`
- `tests/Feature/Notifications/BookingNotificationsTest.php`

---

## E3-S23 · Session confirmation + cancellation email notifications

**Labels**: `messaging`, `coach`
**Size**: S
**Dependencies**: E3-S06, E2-S07

Notify coach and athletes when a session is confirmed or cancelled.

### Acceptance Criteria

- [ ] `app/Notifications/SessionConfirmedNotification.php` — to coach and all booked athletes
- [ ] `app/Notifications/SessionCancelledNotification.php` — to coach and all booked athletes
- [ ] Listeners for `SessionConfirmed` and `SessionCancelled` events
- [ ] Notification content localized
- [ ] Feature test

### Files to create/modify

- `app/Notifications/SessionConfirmedNotification.php`
- `app/Notifications/SessionCancelledNotification.php`
- `app/Listeners/SendSessionConfirmedNotification.php`
- `app/Listeners/SendSessionCancelledNotification.php`
- `app/Providers/EventServiceProvider.php`
- `tests/Feature/Notifications/SessionNotificationsTest.php`

---

## E3-S24 · Auto-cancel sessions past deadline (scheduled command)

**Labels**: `booking`
**Size**: S
**Dependencies**: E2-S07, E3-S14

Scheduled command to cancel published sessions that haven't reached their minimum threshold.

### Acceptance Criteria

- [ ] Scheduled command: runs hourly
- [ ] Finds `published` sessions where `date` has passed (or a configurable deadline before the date, e.g., 24h before)
- [ ] Transitions to `cancelled` via `SessionService::cancel()`
- [ ] Triggers `SessionCancelled` event (which triggers mass refunds via E3-S14)
- [ ] Feature test: command cancels qualifying sessions, skips confirmed/completed/draft

### Files to create/modify

- `app/Console/Commands/CancelExpiredSessions.php` (or schedule in `routes/console.php`)
- `tests/Feature/Commands/CancelExpiredSessionsTest.php`

---

## E3-S25 · Auto-complete sessions past end time (scheduled command)

**Labels**: `booking`
**Size**: S
**Dependencies**: E2-S03

Scheduled command to mark confirmed sessions as completed after they end.

### Acceptance Criteria

- [ ] Scheduled command: runs hourly
- [ ] Finds `confirmed` sessions where `date + end_time` has passed
- [ ] Transitions to `completed` via `SessionService::complete()`
- [ ] Dispatches `SessionCompleted` event (used by Epic 4 for invoicing)
- [ ] Feature test

### Files to create/modify

- `app/Console/Commands/CompleteFinishedSessions.php`
- `app/Events/SessionCompleted.php`
- `app/Services/SessionService.php` (complete method)
- `tests/Feature/Commands/CompleteFinishedSessionsTest.php`

---

## E3-S26 · WhatsApp share + copy-link on session detail

**Labels**: `messaging`, `ui`
**Size**: XS
**Dependencies**: E2-S14

Share buttons on the session detail page. No API needed — just URLs and clipboard.

### Acceptance Criteria

- [ ] WhatsApp share button: `https://wa.me/?text={encoded session URL + title}`
- [ ] Copy-link button: copies session URL to clipboard (JS)
- [ ] Buttons visible on `session/show.blade.php`
- [ ] Works on mobile and desktop
- [ ] Feature test: buttons render with correct URLs

### Files to create/modify

- `resources/views/livewire/session/show.blade.php` (add buttons)
- `resources/js/clipboard.js` (tiny helper, or inline Alpine)

---

## Dependency Graph

```
E3-S01 (Booking model)
├── E3-S02 (BookingPolicy)
├── E3-S06 (BookingService) ──→ E3-S08 (Booking UI)
│   ├── E3-S12 (Cancellation service) ──→ E3-S15 (Cancel UI)
│   │                                  ──→ E3-S13 (Refund)
│   │                                       └── E3-S14 (Mass refund)
│   └── E3-S23 (Session notifications)
├── E3-S19 (Athlete dashboard)
├── E3-S20 (Favourites)
├── E3-S21 (Reminders)
└── E3-S22 (Booking notifications)

E3-S03 (Cashier install)
├── E3-S04 (Stripe Connect)
├── E3-S05 (Webhook controller)
│   ├── E3-S09 (payment succeeded)
│   ├── E3-S10 (payment failed)
│   ├── E3-S11 (account updated)
│   └── E3-S13 (charge refunded)
└── E3-S07 (PaymentIntent)

E3-S16 (Session discovery)
├── E3-S17 (Geolocation)
└── E3-S18 (Map)

E3-S24 (Auto-cancel)
E3-S25 (Auto-complete)
E3-S26 (Share buttons)
```

## Suggested Implementation Order

1. **E3-S01**, **E3-S03** — booking model + Cashier install (parallel)
2. **E3-S02**, **E3-S05** — policies + webhook infrastructure
3. **E3-S04**, **E3-S06** — Stripe Connect + booking service
4. **E3-S07** — payment intent creation
5. **E3-S08**, **E3-S09**, **E3-S10**, **E3-S11** — booking UI + webhook handlers
6. **E3-S12**, **E3-S13** — cancellation + refund
7. **E3-S14**, **E3-S15** — mass refund + cancel UI
8. **E3-S16** — session discovery
9. **E3-S17**, **E3-S18** — geo + map
10. **E3-S19**, **E3-S20** — athlete dashboard + favourites
11. **E3-S21**, **E3-S22**, **E3-S23** — notifications
12. **E3-S24**, **E3-S25**, **E3-S26** — scheduled commands + share buttons
