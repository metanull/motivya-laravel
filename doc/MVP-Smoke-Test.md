# MVP Smoke Test — Manual QA Checklist

> **Purpose**: This document is a practical manual QA checklist for the primary Motivya MVP journey. It is _not_ a replacement for automated tests. Use it before each significant release or milestone to validate the end-to-end user experience with real-like seeded data.

---

## Table of Contents

1. [Prerequisites & Local Setup](#1-prerequisites--local-setup)
2. [Stripe Test Mode Setup](#2-stripe-test-mode-setup)
3. [Seeding the MVP Scenario](#3-seeding-the-mvp-scenario)
4. [Test Accounts Reference](#4-test-accounts-reference)
5. [Phase A — Admin: Coach KYC Approval](#5-phase-a--admin-coach-kyc-approval)
6. [Phase B — Coach: Stripe Onboarding & Session Creation](#6-phase-b--coach-stripe-onboarding--session-creation)
7. [Phase C — Athlete: Discovery, Registration, Booking & Payment](#7-phase-c--athlete-discovery-registration-booking--payment)
8. [Phase D — Threshold Reached: Session Confirmed](#8-phase-d--threshold-reached-session-confirmed)
9. [Phase E — Reminders, Completion & Invoices](#9-phase-e--reminders-completion--invoices)
10. [Phase F — Accountant: Financial Verification](#10-phase-f--accountant-financial-verification)
11. [Phase G — Cancellation & Refund Flow](#11-phase-g--cancellation--refund-flow)
12. [Phase H — Recovery Queue (Admin)](#12-phase-h--recovery-queue-admin)
13. [Notes & Screenshot Placeholders](#13-notes--screenshot-placeholders)

---

## 1. Prerequisites & Local Setup

Follow these steps on a fresh clone before starting any test phase.

```bash
# Clone and install dependencies
composer install
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate

# Seed the MVP journey scenario
# Note: MvpJourneySeeder automatically loads Belgian postal-code coordinates
# (via PostalCodeCoordinatesSeeder) before creating demo users — no separate step needed.
php artisan db:seed --class=MvpJourneySeeder

# Build assets
npm install && npm run build

# Start the development server
php artisan serve
# Optional: start Vite dev server for hot-reloading
npm run dev
```

- [ ] App is reachable at `http://localhost:8000`
- [ ] No errors in `storage/logs/laravel.log`
- [ ] Mailpit (or another mail catcher) running on `http://localhost:8025`
- [ ] The seed completed without errors

---

## 2. Stripe Test Mode Setup

> **Security rule**: Never commit real Stripe credentials. Always use Stripe test keys.

### Required `.env` values

```dotenv
STRIPE_KEY=pk_test_XXXXXXXXXXXXXXXXXXXX
STRIPE_SECRET=sk_test_XXXXXXXXXXXXXXXXXXXX
STRIPE_WEBHOOK_SECRET=whsec_XXXXXXXXXXXXXXXXXXXX
```

### Steps

1. Log in to [https://dashboard.stripe.com](https://dashboard.stripe.com) and activate **Test mode**.
2. Go to **Developers → API keys** and copy your **Publishable key** (`pk_test_...`) and **Secret key** (`sk_test_...`).
3. Paste both into your `.env` file.
4. Install the Stripe CLI: [https://stripe.com/docs/stripe-cli](https://stripe.com/docs/stripe-cli).
5. Forward webhooks to your local server:
   ```bash
   stripe listen --forward-to http://localhost:8000/stripe/webhook
   ```
6. Copy the **webhook signing secret** (`whsec_...`) printed by the CLI into `STRIPE_WEBHOOK_SECRET`.

### Test payment cards (Stripe)

| Scenario | Card number | Expiry | CVC |
|----------|-------------|--------|-----|
| Successful payment | `4242 4242 4242 4242` | Any future | Any |
| Insufficient funds | `4000 0000 0000 9995` | Any future | Any |
| 3D Secure required | `4000 0025 0000 3155` | Any future | Any |
| Bancontact (test) | Use Stripe Bancontact test redirect flow | | |

- [ ] `stripe listen` is running and forwarding to `localhost:8000/stripe/webhook`
- [ ] Test card payment works on a simple Stripe checkout flow

---

## 3. Seeding the MVP Scenario

The `MvpJourneySeeder` creates a realistic starting state for the MVP journey:

```bash
php artisan db:seed --class=MvpJourneySeeder
```

> **Note**: The seeder will **abort** if `APP_ENV=production` to prevent accidental seeding of demo
> credentials in production.

This creates:
- 1 **Admin** user (pre-existing platform admin)
- 1 **Coach** (`sophie.coach@motivya.test`) with a *pending* profile (needs admin approval)
- 1 **Coach** (`marc.coach@motivya.test`) with an *approved, Stripe-ready* profile and sessions across multiple Brussels postal codes
- 4 **Athletes**:
  - `alice@motivya.test` — confirmed bookings for Marc's sessions
  - `bob@motivya.test` — confirmed booking + one refunded booking (cancelled session)
  - `charlie@motivya.test` — fresh tester, no bookings yet
  - `diana@motivya.test` — pending-payment booking (for payment recovery testing)
- 1 **Accountant** (`accountant@motivya.test`)
- 1 **Suspended athlete** (`suspended@motivya.test`) — account suspended (for admin user management testing)
- 1 **Unverified athlete** (`unverified@motivya.test`) — unverified email (for admin testing)
- Sessions across 5 Brussels postal codes: **1000, 1020, 1030, 1040, 1050**
- 1 **draft invoice** for the previous month's completed session by Marc
- 1 **payout statement** (Draft) for Marc's previous billing month
- 1 **payment anomaly** (open, for accountant/admin anomaly queue review)

> All passwords are `password` (bcrypt-hashed — safe for local/dev only).
> Replace `stripe_account_id: acct_mvp_smoke_test` with a real Stripe Express test account ID.

---

## 4. Test Accounts Reference

| Role | Email | Password | Notes |
|------|-------|----------|-------|
| Admin | `admin@motivya.test` | `password` | Can approve coaches, manage users, review readiness |
| Pending Coach | `sophie.coach@motivya.test` | `password` | Profile awaiting admin approval |
| Approved Coach | `marc.coach@motivya.test` | `password` | Approved + Stripe-ready; sessions in multiple postal codes |
| Athlete 1 | `alice@motivya.test` | `password` | Has confirmed bookings for Marc's sessions |
| Athlete 2 | `bob@motivya.test` | `password` | Confirmed booking + refunded booking |
| Athlete (tester) | `charlie@motivya.test` | `password` | Fresh — use for the full booking journey |
| Athlete (pending payment) | `diana@motivya.test` | `password` | Pending-payment booking (payment recovery) |
| Athlete (suspended) | `suspended@motivya.test` | `password` | Suspended — for admin user management |
| Athlete (unverified) | `unverified@motivya.test` | `password` | Email not verified — for admin testing |
| Accountant | `accountant@motivya.test` | `password` | Read-only access to transactions, invoices, exports |

---

## 5. Phase A — Admin: Coach KYC Approval

**Goal**: Verify the admin can review and approve a coach's application.

**Actor**: Admin (`admin@motivya.test`)

### Steps

- [ ] **A1** — Log in as admin at `http://localhost:8000/login`
  - *Expected*: Redirected to admin dashboard.
  - 📸 _Screenshot placeholder: admin dashboard after login_

- [ ] **A2** — Navigate to `/admin/coach-approval`
  - *Expected*: List shows `Sophie Martin` (pending). Marc's profile should already appear as approved.
  - 📸 _Screenshot placeholder: coach approval list_

- [ ] **A3** — Click on Sophie's profile to review details (bio, specialties, enterprise number).
  - *Expected*: Profile detail page loads without errors.

- [ ] **A4** — Approve Sophie's coach application.
  - *Expected*: Sophie's status changes to **Approved**. List updates. Confirmation feedback shown.
  - 📸 _Screenshot placeholder: approval confirmation_

- [ ] **A5** — Verify Sophie can now start Stripe onboarding (log in as `sophie.coach@motivya.test` and navigate to `/coach/stripe/onboard`).
  - *Expected*: Stripe onboarding link is accessible (no "pending approval" gate).

---

## 6. Phase B — Coach: Stripe Onboarding & Session Creation

**Goal**: Verify a coach can complete Stripe onboarding and publish a session.

**Actor**: Approved Coach (`marc.coach@motivya.test` — already Stripe-ready for the primary flow; use Sophie for a clean onboarding test)

### B1 — Stripe Onboarding (Sophie's new account)

- [ ] **B1a** — Log in as `sophie.coach@motivya.test`.
- [ ] **B1b** — Navigate to `/coach/stripe/onboard`.
  - *Expected*: Redirected to Stripe Express onboarding flow.
  - 📸 _Screenshot placeholder: Stripe onboarding screen_

- [ ] **B1c** — Complete onboarding with Stripe test data:
  - Business type: Individual
  - Country: Belgium
  - Use test phone, test bank account (`BE89370400440532013000` for IBAN)
  - 📸 _Screenshot placeholder: Stripe onboarding completion_

- [ ] **B1d** — Return to the app (Stripe redirects to `/coach/stripe/return`).
  - *Expected*: Dashboard shows Stripe status as **active/ready**; coach can now create sessions.

### B2 — Session Creation

**Actor**: `marc.coach@motivya.test` (already Stripe-ready)

- [ ] **B2a** — Log in as Marc, navigate to `/coach/sessions/create`.
  - *Expected*: Session creation form loads.
  - 📸 _Screenshot placeholder: session creation form_

- [ ] **B2b** — Fill in session details:
  - Title: `Yoga matinal en plein air`
  - Activity: `Yoga`
  - Level: `Débutant`
  - Location: `Parc Léopold, Bruxelles`
  - Postal code: `1040`
  - Date: 2 weeks from today
  - Start time: `09:00`, End time: `10:00`
  - Price per person: `€15.00` (1500 cents)
  - Min participants: `3`, Max participants: `12`

- [ ] **B2c** — Save as draft.
  - *Expected*: Session appears on coach dashboard with status **Draft**.

- [ ] **B2d** — Publish the session.
  - *Expected*: Session status changes to **Published**. Session visible on public discovery page.
  - 📸 _Screenshot placeholder: published session on coach dashboard_

### B3 — Coach Dashboard Verification

- [ ] **B3a** — Navigate to `/coach/dashboard`.
  - *Expected*: Both sessions visible (Marc's existing session + the new yoga session). Participant counts displayed correctly.
  - 📸 _Screenshot placeholder: coach dashboard with sessions_

- [ ] **B3b** — Marc's existing "Cardio Débutant" session shows 2 confirmed bookings out of min=3.

---

## 7. Phase C — Athlete: Discovery, Registration, Booking & Payment

**Goal**: Verify that a new athlete can discover a session, register/log in, book, and pay.

**Actor**: Charlie (fresh athlete, `charlie@motivya.test`)

### C1 — Session Discovery (unauthenticated)

- [ ] **C1a** — Open a private/incognito browser window and navigate to `http://localhost:8000/sessions`.
  - *Expected*: Session index page loads without login. Marc's "Cardio Débutant" session is visible.
  - 📸 _Screenshot placeholder: public session list_

- [ ] **C1b** — Filter by activity type `Cardio` or search by postal code `1000`.
  - *Expected*: Marc's session appears in filtered results.

- [ ] **C1c** — Click on the session to open its detail page.
  - *Expected*: Session details visible (title, price, min/max participants, current count = 2, status = Published). Book button visible.
  - 📸 _Screenshot placeholder: session detail page_

### C2 — Registration / Login

- [ ] **C2a** — Click "Book this session" or equivalent.
  - *Expected*: Redirected to `/login` (guest users cannot book directly).

- [ ] **C2b** — Option A: Log in as `charlie@motivya.test` / `password`.
  - *Expected*: Redirected back to the session detail page after login.

- [ ] **C2b** — Option B: Register a brand-new account (e.g., `testuser+smoke@motivya.test`).
  - *Expected*: Registration form works, email verification sent, account created with role **Athlete**.
  - 📸 _Screenshot placeholder: registration form and confirmation_

### C3 — Booking

- [ ] **C3a** — On the session detail page, click "Book" / "Réserver".
  - *Expected*: Booking confirmation appears; Stripe payment form is shown (or redirected to Stripe Checkout).
  - 📸 _Screenshot placeholder: booking confirmation / Stripe form_

- [ ] **C3b** — Enter Stripe test card `4242 4242 4242 4242`, any future expiry, any CVC.
  - *Expected*: Payment succeeds.
  - 📸 _Screenshot placeholder: payment success screen_

- [ ] **C3c** — Verify booking status in athlete dashboard `/athlete/dashboard`.
  - *Expected*: Booking listed with status **Pending Payment** → **Confirmed** after payment.
  - *Note*: Status may update via Stripe webhook. Check `stripe listen` output.

- [ ] **C3d** — Verify session participant count is now 3 (`current_participants = 3`).

- [ ] **C3e** — Check Mailpit for booking confirmation email to Charlie.
  - *Expected*: Email received with session details.
  - 📸 _Screenshot placeholder: booking confirmation email_

---

## 8. Phase D — Threshold Reached: Session Confirmed

**Goal**: Verify the session auto-confirms when the minimum participant count is reached.

**Trigger**: Charlie's booking brings `current_participants` to 3, which equals `min_participants = 3`.

- [ ] **D1** — After Charlie's payment webhook fires, check session status.
  - *Expected*: Session status transitions from **Published** → **Confirmed**.
  - Check in the database: `SELECT status, current_participants FROM sport_sessions WHERE id = <id>;`
  - 📸 _Screenshot placeholder: session status in coach dashboard showing "Confirmed"_

- [ ] **D2** — Check Mailpit for session confirmation email to the coach (Marc).
  - *Expected*: Coach receives "Your session has been confirmed" notification.
  - 📸 _Screenshot placeholder: coach confirmation email_

- [ ] **D3** — Check Mailpit for session confirmation emails to all booked athletes (Alice, Bob, Charlie).
  - *Expected*: All three receive "Session confirmed" notifications.
  - 📸 _Screenshot placeholder: athlete confirmation email_

- [ ] **D4** — Log in as Marc and verify coach dashboard shows session as **Confirmed** with 3 participants.

- [ ] **D5** — Log in as Charlie and verify athlete dashboard shows booking as **Confirmed**.

---

## 9. Phase E — Reminders, Completion & Invoices

**Goal**: Verify reminder dispatch and post-session invoice generation.

### E1 — Session Reminders

- [ ] **E1a** — Set the session date to "tomorrow" in the database for testing:
  ```sql
  UPDATE sport_sessions SET date = DATE('now', '+1 day') WHERE id = <id>;
  ```
  Or via tinker:
  ```bash
  php artisan tinker
  >>> \App\Models\SportSession::where('status', 'confirmed')->first()->update(['date' => now()->addDay()]);
  ```

- [ ] **E1b** — Run the reminder scheduler:
  ```bash
  php artisan schedule:run
  ```
  Or trigger the reminder command directly if one exists.

- [ ] **E1c** — Check Mailpit for reminder emails sent to all confirmed athletes (Alice, Bob, Charlie).
  - *Expected*: Each athlete receives a "Your session is tomorrow" reminder.
  - 📸 _Screenshot placeholder: reminder email_

### E2 — Session Completion

- [ ] **E2a** — Set the session to **Completed** status (simulate post-date completion):
  ```bash
  php artisan tinker
  >>> \App\Models\SportSession::where('status', 'confirmed')->first()->update(['status' => 'completed']);
  ```
  Or trigger via scheduler if auto-completion is implemented.

- [ ] **E2b** — Verify the `SessionCompleted` event is dispatched (check application logs).

- [ ] **E2c** — Verify an **Invoice** is created in the database for Marc.
  - *Expected*: A new invoice record exists with status **Draft**, linked to Marc and the sport session.
  - Check: `SELECT * FROM invoices WHERE coach_id = <marc_id> ORDER BY created_at DESC LIMIT 1;`

- [ ] **E2d** — Verify the invoice has correct financial data:
  - `revenue_ttc`: sum of all `amount_paid` values from confirmed bookings (3 × price_per_person)
  - `revenue_htva`: `revenue_ttc / 1.21` (rounded)
  - `commission_amount`: calculated based on Marc's subscription plan
  - `coach_payout`: `revenue_htva - commission_amount`
  - 📸 _Screenshot placeholder: invoice record in accountant dashboard_

---

## 10. Phase F — Accountant: Financial Verification

**Goal**: Verify the accountant can review transactions, invoices, and export financial data.

**Actor**: `accountant@motivya.test`

- [ ] **F1** — Log in as the accountant at `http://localhost:8000/login`.
  - *Expected*: Redirected to accountant dashboard at `/accountant/dashboard`.
  - 📸 _Screenshot placeholder: accountant dashboard_

- [ ] **F2** — View transaction list.
  - *Expected*: Marc's session bookings are visible with amounts, statuses, and dates.

- [ ] **F3** — Navigate to the invoice detail for Marc's completed session invoice at `/accountant/invoices/{id}`.
  - *Expected*: Invoice details shown with all financial fields (revenue TTC/HTVA, VAT, commission, payout).
  - 📸 _Screenshot placeholder: invoice detail page_

- [ ] **F4** — Verify PEPPOL XML is generated (if invoice has been issued):
  - *Expected*: XML path is set in the invoice record. File exists in storage.
  - Check: `SELECT xml_path FROM invoices WHERE id = <id>;`

- [ ] **F5** — Export financial data at `/accountant/export`:
  - *Expected*: CSV/Excel file downloads successfully with correct columns and data.
  - 📸 _Screenshot placeholder: exported CSV content_

- [ ] **F6** — Verify commission amounts and VAT breakdowns are correct:
  - For a VAT-subject coach (Marc, `is_vat_subject = true`): standard commission applied
  - For a non-VAT-subject coach: payout formula based on HTVA to preserve margin

---

## 11. Phase G — Cancellation & Refund Flow

**Goal**: Verify athlete cancellation and automatic refund.

**Actor**: Charlie (`charlie@motivya.test`), then Admin / Accountant verification

### G1 — Athlete Cancels Within Policy Window

- [ ] **G1a** — Log in as Charlie. Navigate to athlete dashboard.
- [ ] **G1b** — Cancel the booking for Marc's Cardio session.
  - *Note*: Cancellation is allowed up to **48h before** a confirmed session, or **24h before** a pending session.
  - *Expected*: Cancellation succeeds. Booking status → **Cancelled**.
  - 📸 _Screenshot placeholder: cancellation confirmation_

- [ ] **G1c** — Check Mailpit for cancellation confirmation email to Charlie.
  - *Expected*: "Your booking has been cancelled" email received.

- [ ] **G1d** — Verify Stripe refund is initiated (check Stripe test dashboard or `stripe listen` output).
  - *Expected*: Refund event received and booking status → **Refunded**.
  - 📸 _Screenshot placeholder: Stripe refund event in CLI_

- [ ] **G1e** — Check Mailpit for refund notification email to Charlie.

- [ ] **G1f** — Verify session `current_participants` decremented back to 2.

### G2 — Cancellation Outside Policy Window (Rejected)

- [ ] **G2a** — Set session date to within 24h:
  ```bash
  php artisan tinker
  >>> \App\Models\SportSession::find(<id>)->update(['date' => now()->addHours(12)]);
  ```

- [ ] **G2b** — Attempt cancellation as Charlie.
  - *Expected*: Cancellation is rejected with a clear error message about the cancellation policy.
  - 📸 _Screenshot placeholder: cancellation rejection message_

---

## 12. Phase H — Recovery Queue & Anomaly Review

**Goal**: Verify the admin can see payment anomalies, pending-payment bookings, and review system health.

**Actor**: Admin (`admin@motivya.test`)

- [ ] **H1** — Navigate to `/admin/anomalies`.
  - *Expected*: At least one anomaly from the seed is visible (type: "Completed session without invoice").
  - 📸 _Screenshot placeholder: anomaly queue_

- [ ] **H2** — Verify Diana's pending-payment booking is visible in the recovery queue.
  - Navigate to the session detail for "Running Cinquantenaire". Diana should appear as "Pending Payment".
  - *Expected*: Booking shows `PendingPayment` status with an expiry time.

- [ ] **H3** — Navigate to `/admin/data-export`.
  - *Expected*: Export page loads. Options available.
  - 📸 _Screenshot placeholder: admin data export page_

- [ ] **H4** — Download a data export (e.g., sessions or bookings).
  - *Expected*: File downloads. Content is valid (not empty, correct format).

- [ ] **H5** — Check `/health` endpoint.
  - *Expected*: Returns `{"status":"ok","database":"ok","cache":"ok"}`.

- [ ] **H6** — Verify admin activity images management at `/admin/activity-images`.
  - *Expected*: Page loads without errors. Existing images listed.

---

## 13. Phase I — MVP Readiness Checklist (Admin)

**Goal**: Verify the readiness page shows correct status for all platform prerequisites.

**Actor**: Admin (`admin@motivya.test`)

- [ ] **I1** — Navigate to `/admin/readiness`.
  - *Expected*: Page loads. Shows green/yellow/red indicators for all checks.
  - 📸 _Screenshot placeholder: readiness page_

- [ ] **I2** — Verify the **Stripe keys** check.
  - *Expected*: 🟡 Yellow (test keys not matching `pk_test_`/`sk_test_` format if using examples), or 🟢 Green if real test keys are configured.

- [ ] **I3** — Verify the **Mail** check.
  - *Expected*: 🟡 Yellow when `MAIL_MAILER=log` (dev default), 🟢 Green when a real mailer is configured.

- [ ] **I4** — Verify the **Database** check.
  - *Expected*: 🟢 Green (database is connected).

- [ ] **I5** — Verify the **Cache** check.
  - *Expected*: 🟢 Green.

- [ ] **I6** — Verify the **Scheduler heartbeats** summary.
  - *Expected*: 🔴 Red when scheduler has never run; 🟢 Green after running `php artisan schedule:run`.
  - After running the scheduler, refresh the page and verify each command's status turns green.

- [ ] **I7** — Verify the **Postal code coordinates** check.
  - *Expected*: 🟢 Green with a count > 0 if the PostalCodeCoordinates seeder has been run; 🔴 Red otherwise.

- [ ] **I8** — Verify the **Admin with MFA** check.
  - *Expected*: 🟢 Green if at least one admin has MFA configured (use `admin@motivya.test` with MFA enabled).

- [ ] **I9** — Verify the **Accountant user** check.
  - *Expected*: 🟢 Green (accountant@motivya.test was seeded).

- [ ] **I10** — Verify the **Activity images** check.
  - *Expected*: 🟡 Yellow (no images seeded by default) or 🟢 Green if images were uploaded.

- [ ] **I11** — Verify the **Billing config page** check.
  - *Expected*: 🟢 Green (route admin.configuration.billing is registered).

---

## 14. Notes & Screenshot Placeholders

Use this section to attach screenshots, notes, and observations during testing.

### Testing Environment

| Field | Value |
|-------|-------|
| Date of test | ___ |
| Tester | ___ |
| App version / commit SHA | ___ |
| PHP version | ___ |
| Stripe test mode confirmed | ☐ Yes ☐ No |
| Mailpit running | ☐ Yes ☐ No |

### Observations

| Phase | Result | Notes |
|-------|--------|-------|
| A — Admin KYC Approval | ☐ Pass ☐ Fail ☐ Partial | |
| B — Coach Onboarding + Session | ☐ Pass ☐ Fail ☐ Partial | |
| C — Athlete Discovery + Booking | ☐ Pass ☐ Fail ☐ Partial | |
| D — Threshold + Confirmation | ☐ Pass ☐ Fail ☐ Partial | |
| E — Reminders + Invoices | ☐ Pass ☐ Fail ☐ Partial | |
| F — Accountant Verification | ☐ Pass ☐ Fail ☐ Partial | |
| G — Cancellation + Refund | ☐ Pass ☐ Fail ☐ Partial | |
| H — Recovery Queue & Anomalies | ☐ Pass ☐ Fail ☐ Partial | |
| I — MVP Readiness Checklist | ☐ Pass ☐ Fail ☐ Partial | |

### Known Limitations / Out-of-Scope for This Checklist

- Real Stripe payouts to coach bank accounts (not tested in test mode without a live Stripe account)
- Push notifications (deferred to Phase 2)
- Google OAuth login (requires Google OAuth credentials; not tested in the default dev setup)
- Real PEPPOL XML submission to the Belgian PEPPOL access point (validated locally only)
- Multi-currency or non-EUR payments (out of MVP scope)

---

*Maintained by the Motivya dev team. Update after each release that changes the MVP journey.*
