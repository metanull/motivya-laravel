# Motivya MVP Readiness Epics

Date: 2026-05-04

## Purpose

The GitHub MVP milestones are closed, but a real launchable MVP still needs several product and operational gaps closed. This document translates the review into implementation-ready epics and stories for the four Motivya roles: Athlete, Coach, Accountant, and Admin.

The intent is not to redesign the product. It is to make the existing Laravel/Livewire application coherent enough that each profile can sign in, reach the right workspace, complete its core work, and recover from common operational problems.

## Current App Snapshot

Implemented strengths:

- Public session discovery exists through `routes/web.php` with `/sessions`, `/sessions/{sportSession}`, and `/coaches/{user}`.
- Role-gated areas exist in `bootstrap/app.php`: `/coach`, `/athlete`, `/admin`, and `/accountant`.
- Coach application, admin coach approval, Stripe Connect onboarding, session creation/editing, recurring sessions, booking, cancellation, reminders, payments, refunds, invoices, PEPPOL XML, CSV/Excel exports, and basic dashboards are present.
- Postal-code coordinate reference data exists through `PostalCodeCoordinatesSeeder`, and `SessionService` stores coordinates on sessions.
- Unpaid bookings now expire through `bookings:expire-unpaid`, scheduled every five minutes.
- Accountant stuck-session recovery exists through `Accountant\StuckSessionsQueue`.

Launch-impacting gaps:

- There is no first-class way to create, invite, or manage accountant users. `app:create-admin` only handles admins, and the admin UI has no user management screen.
- The accountant dashboard is routed but not discoverable from the main navigation. `resources/views/components/nav/user-menu.blade.php` exposes admin coach approval and coach links, but no accountant entry point.
- Admin tooling is too narrow for MVP operations: coach approval, activity images, and data export exist, but admins cannot manage users, supervise sessions, handle exceptional refunds, or see operational queues from one dashboard.
- Athlete discovery is usable but too rigid: geolocation uses a hardcoded 2 km radius in `SessionQueryService::DEFAULT_RADIUS_KM`, postal-code search is exact-match only, city/municipality search is missing, and map markers are built independently from the active geolocation radius.
- Role-aware post-login routing is missing. Fortify still uses `config/fortify.php` home `/`, Google OAuth redirects to `home`, and 2FA challenge redirects to `home`.
- The accounting model needs a clearer MVP workflow for coach payout requests and PEPPOL-compliant documents. Current invoice automation exists, but `doc/UseCases.md` clarifies that coaches must be able to invoice and get paid under timing conditions, with monthly details.
- The MVP has many feature tests, but lacks a single cross-role smoke journey that proves all four profiles can use the product end-to-end.

## MVP Completion Definition

Motivya is MVP-ready when all four roles can complete these journeys in a seeded or real environment:

- Athlete: discover sessions by postal code, city, or geolocation radius; inspect map/list results; book and pay; manage upcoming/past bookings; cancel within policy; use favourites/share.
- Coach: apply, get approved, finish Stripe onboarding, complete profile/VAT details, create/publish sessions, monitor bookings/revenue, and request payout documentation.
- Accountant: sign in with MFA, reach the dashboard from navigation, inspect transactions/invoices/refunds/payouts, export financial data, detect anomalies, and reconcile stuck sessions.
- Admin: sign in with MFA, manage users including accountants, approve coaches, supervise sessions/payments/refunds, manage activity images, export operational data, and resolve launch blockers without database access.

---

# Epic 1: Role Entry Points and Account Administration

## Goal

Make every role reachable and manageable without manual database edits. This closes the most visible MVP hole: accountants exist as a role, but there is no onboarding or navigation path for them, and admins cannot manage platform users.

## Story 1.1: Admin Can Create and Invite Accountant Users

**Role:** Admin

**Context:** `UserRole::Accountant` exists, accountant routes are protected by `role:accountant,admin` plus `2fa`, and accountant Livewire pages exist. However, the only privileged-user command is `app:create-admin`, and no UI creates accountant accounts.

**Implementation details:**

- Add an admin Livewire component, for example `App\Livewire\Admin\Users\Create`, or combine it into a broader admin users index from Story 1.2.
- Form fields: name, email, role. For MVP, role options should be `accountant` and `admin`; do not allow creating coaches through this screen because coaches must pass the application workflow.
- Create the user with a random temporary password and `email_verified_at = now()`, then send a localized notification/email with a password-reset link.
- Require MFA after first login for accountants/admins using the existing `EnsureTwoFactorEnabled` middleware.
- Keep user-facing strings in `lang/fr`, `lang/en`, and `lang/nl`.
- Add route under `routes/web/admin.php`, for example `/users/create`, behind the existing admin route group.
- Add a link from the admin navigation/hub introduced in Story 1.3.

**Acceptance criteria:**

- An admin can create an accountant from the browser.
- The accountant receives an onboarding email with a reset-password link.
- The accountant can set a password, log in, is forced to configure MFA, then reaches the accountant dashboard.
- Duplicate email validation is shown in the form.
- Athletes/coaches cannot access the screen.

**Test expectations:**

- Feature/Livewire test: admin creates accountant successfully.
- Feature/Livewire test: non-admin receives 403.
- Notification/mail test: onboarding email contains password reset URL and localized copy.
- Middleware test: new accountant is redirected to profile/MFA setup before `/accountant/dashboard`.

## Story 1.2: Admin Can Manage Users and Roles Safely

**Role:** Admin

**Context:** `UserPolicy` has admin bypass, but `viewAny`, `delete`, and `promote` return false for normal users. No admin user-management routes exist. Admins need to create accountants, inspect users, suspend problematic accounts, and fix accidental role assignment.

**Implementation details:**

- Add `App\Livewire\Admin\Users\Index` with search by name/email, filter by role, and status badges.
- Add columns: name, email, role, email verification status, MFA status for admin/accountant, coach profile status if any, created date, last update.
- Add actions:
  - Send password reset link.
  - Change role between `athlete`, `accountant`, and `admin` with confirmation.
  - Demote admin/accountant only if at least one other active admin remains.
  - Suspend/reactivate account if a `users.suspended_at` migration is added.
- Do not allow direct promotion to `coach`; coach role must continue to come from `AdminService::approveCoach()`.
- Update `UserPolicy` with explicit admin-only abilities or rely on admin route middleware plus policy checks in component actions.

**Acceptance criteria:**

- Admins can find any user by email/name and see their role and operational status.
- Admins can create or promote an accountant without database access.
- Admins cannot accidentally remove the final admin.
- Coaches are not manually assignable except by approving coach applications.
- Suspended users cannot log in or use role dashboards if suspension is implemented.

**Test expectations:**

- Livewire test for search/filter/pagination.
- Tests for role-change guardrails, especially final-admin protection and blocked direct coach promotion.
- Auth test for suspended-user behavior if suspension is included.

## Story 1.3: Add Role-Aware Navigation and Dashboard Hub Links

**Roles:** Admin, Accountant, Coach, Athlete

**Context:** `/accountant/dashboard` works, but the main nav does not expose an accountant menu item. Admin links only expose coach approval. Athlete dashboard/favourites also exist but are not first-class nav items.

**Implementation details:**

- Update `resources/views/components/nav/user-menu.blade.php`, `main.blade.php`, and `mobile-menu.blade.php`.
- Add accountant menu items under `@can('access-accountant-panel')`:
  - Accountant dashboard: `route('accountant.dashboard')`.
  - Financial export can remain inside the dashboard.
- Add admin menu items under `@can('access-admin-panel')`:
  - Admin dashboard/hub from Epic 3.
  - Users.
  - Coach approval.
  - Activity images.
  - Data export.
- Add athlete menu items under role check or a new `access-athlete-panel` gate:
  - Athlete dashboard.
  - Favourites.
- Keep coach menu as dashboard, create session, profile, payout history.

**Acceptance criteria:**

- Each authenticated role sees a clear link to its dashboard in desktop and mobile navigation.
- Accountants do not need to manually type `/accountant/dashboard`.
- Admins can reach accountant dashboard because the route allows admin, but the link should be labelled as finance/accounting, not hidden.
- Guest navigation still prioritizes session discovery and registration/login.

**Test expectations:**

- `NavigationTest` coverage for every role and guest.
- Assertions for both desktop and mobile menus.

## Story 1.4: Role-Aware Post-Login and Post-2FA Redirects

**Roles:** All

**Context:** `config/fortify.php` uses `home => '/'`; Google OAuth redirects to `route('home')`; `TwoFactorChallenge` redirects to `home`. This leaves users on the marketing/home page after sign-in.

**Implementation details:**

- Add a small service such as `App\Services\RoleRedirectService` returning:
  - Admin: `admin.dashboard` if added, otherwise `admin.coach-approval`.
  - Accountant: `accountant.dashboard`.
  - Coach: `coach.dashboard`.
  - Athlete: `athlete.dashboard` or `sessions.index` depending product choice; use athlete dashboard when the user has bookings, otherwise sessions index is acceptable.
- Bind Fortify `LoginResponse` and `RegisterResponse` in `FortifyServiceProvider` or an auth service provider.
- Update `GoogleController::callback()` to use `redirect()->intended($roleRedirectService->pathFor($user))`.
- Update `Auth\TwoFactorChallenge` success redirects to the same service.

**Acceptance criteria:**

- After password login, Google login, registration, and successful 2FA challenge, users land on the correct role workspace.
- Intended URLs still win when a user was intercepted from a protected page.
- Tests cover all four roles.

**Test expectations:**

- Feature tests for login redirects by role.
- Google OAuth test update for role-aware destination.
- 2FA challenge tests for admin/accountant redirection.

---

# Epic 2: Athlete Discovery, Map, and Localisation

## Goal

Make discovery useful in Brussels and nearby Belgian cities. A hardcoded 2 km radius and exact postal-code matching are too narrow for the MVP marketplace.

## Story 2.1: User-Selectable Search Radius

**Role:** Athlete / Guest

**Context:** `SessionQueryService::DEFAULT_RADIUS_KM` is `2.0`, and `Session\Index` does not expose a radius property. In practice, 2 km is too small for Brussels sports sessions.

**Implementation details:**

- Add `#[Url] public int|string $radiusKm` to `App\Livewire\Session\Index`.
- Supported radius options: 2, 5, 10, 20, 50 km. Default should be 10 km for Brussels MVP.
- Validate/coerce radius in the component; never pass arbitrary input directly to the query.
- Pass selected radius to `SessionQueryService::searchByLocation()`.
- Add a radius selector in `resources/views/livewire/session/index.blade.php`, enabled when geolocation or city/postal-code distance search is active.
- Add translations under `lang/*/sessions.php`.

**Acceptance criteria:**

- User can choose radius from predefined options.
- Geolocation search uses the selected radius.
- URL query string preserves the radius.
- Default search is broader than 2 km.

**Test expectations:**

- Livewire test that 2 km excludes a farther session and 10/20 km includes it.
- Unit/feature test around invalid radius values falling back to default.

## Story 2.2: Search by City or Municipality, Not Only Postal Code

**Role:** Athlete / Guest

**Context:** The requirement says search by city or postal code. The current UI has a `postalCode` field and `SessionQueryService::applyFilters()` exact-matches `sport_sessions.postal_code`.

**Implementation details:**

- Rename the user-facing field to location search while keeping internal compatibility.
- Add a `locationQuery` URL property to `Session\Index`; deprecate direct `postalCode` if needed.
- Extend `PostalCodeCoordinateService` with lookup by exact postal code or municipality name, using `postal_code_coordinates.municipality`.
- If the query is a known postal code or municipality, resolve it to coordinates and use distance search with selected radius.
- If the query does not resolve, fall back to exact postal-code matching only when it matches four digits; otherwise show no results plus a localized message.
- Keep existing `postalCode` query support as a backwards-compatible alias during migration.

**Acceptance criteria:**

- Searching `Ixelles`, `Etterbeek`, `Bruxelles`, `Brussel`, or `1050` returns sessions within the selected radius.
- Search remains public for guests.
- The UI clearly labels the field as city or postal code.
- Invalid locations do not throw errors.

**Test expectations:**

- Tests for municipality lookup in French/Dutch bilingual names from seeded data.
- Livewire tests for city search, postal-code search, and invalid search.

## Story 2.3: Keep Map Markers Consistent with Active Search Scope

**Role:** Athlete / Guest

**Context:** `Session\Index::render()` calls `mapMarkers($filters)` regardless of active geolocation coordinates. This can show markers outside the current radius when the list is radius-filtered.

**Implementation details:**

- Add a marker query method that accepts optional center coordinates and radius, for example `mapMarkers(array $filters, ?float $lat = null, ?float $lng = null, ?float $radiusKm = null)`.
- Reuse the same distance condition as `searchByLocation()` so list and map represent the same scope.
- Consider extracting the Haversine expression into one private helper in `SessionQueryService`.
- Update `Session\Index::render()` to pass geolocation or resolved city/postal coordinates into both list and map methods.
- Add a small UI indicator showing the active location and radius.

**Acceptance criteria:**

- With a location/radius active, the map only displays sessions included in the scoped search.
- Without location/radius, the map shows all discoverable sessions matching non-location filters.
- Changing filters updates both the list and the map.

**Test expectations:**

- Feature test that `mapMarkers` excludes a session outside radius.
- Livewire test that marker count changes with radius.

## Story 2.4: Improve Map and List Empty States

**Role:** Athlete / Guest

**Context:** When no coordinates or no sessions exist, the map is hidden. That is correct technically but confusing for users who just granted geolocation.

**Implementation details:**

- Add distinct empty states:
  - No sessions in selected radius: suggest increasing radius or clearing filters.
  - Unknown location: suggest postal code or city examples.
  - Geolocation denied: keep the current warning, but leave the location field usable.
- Keep messages localized.
- Avoid hiding all discovery context; display the active filters and a reset action.

**Acceptance criteria:**

- Empty states tell users what to change next.
- Reset filters and radius controls are available on empty results.

**Test expectations:**

- Livewire rendering tests for unknown location and no-results states.

---

# Epic 3: Admin Operations Portal

## Goal

Move admin from a single coach-approval queue into a minimal operations console. The MVP does not need advanced analytics, but it does need human operators to manage launch blockers.

## Story 3.1: Admin Dashboard Hub

**Role:** Admin

**Context:** Admin routes exist for coach approval, activity images, and data export, but there is no `/admin/dashboard` or central hub.

**Implementation details:**

- Add `App\Livewire\Admin\Dashboard` and route `/admin/dashboard` named `admin.dashboard`.
- Show compact cards and links for:
  - Pending coach applications.
  - Users needing attention: accountants/admins without MFA, unverified accounts, suspended users if added.
  - Confirmed sessions past end time.
  - Pending refunds/failed payments if available from bookings/invoices.
  - Activity images missing by activity type.
  - Data export.
- Use efficient counts, not large table loads.
- Make this the admin role redirect target.

**Acceptance criteria:**

- Admins can reach all admin MVP tools from one screen.
- Dashboard counts match underlying records.
- Accountants and other users cannot access it.

**Test expectations:**

- Livewire test for counts and links.
- Authorization test for forbidden roles.

## Story 3.2: Admin Session Supervision

**Role:** Admin

**Context:** `UC-P03` requires admins to supervise sessions. Current admin tooling cannot search sessions, inspect capacity/payment state, or intervene except through data export.

**Implementation details:**

- Add `App\Livewire\Admin\Sessions\Index` under `/admin/sessions`.
- Filters: status, coach, activity type, date range, postal code/city, sessions with pending payments, sessions past end time.
- Columns: title, coach, date/time, status, current participants, confirmed paid bookings, pending-payment holds, min/max participants, revenue paid, Stripe readiness of coach.
- Actions:
  - Open public session detail.
  - Open coach profile.
  - Cancel session with reason, using `SessionService::cancel()` and existing events.
  - Manually complete past confirmed sessions, reusing `SessionService::complete()` and existing policy/event flow.
- Preserve coach ownership rules; admin action should be auditable.

**Acceptance criteria:**

- Admins can find and inspect any session.
- Admins can cancel eligible published/confirmed sessions with reason.
- Admins can complete stuck sessions without using accountant routes.
- Actions dispatch existing notifications/refund/invoice side effects.

**Test expectations:**

- Livewire tests for filters, cancel action, and manual complete action.
- Event tests confirming `SessionCancelled` and `SessionCompleted` still fire.

## Story 3.3: Admin Exceptional Refund Queue

**Role:** Admin

**Context:** Normal athlete cancellation and Stripe refund events exist, but `UC-P10` requires admins to manage exceptional refunds. There is no admin surface for that.

**Implementation details:**

- Add an admin refunds page or section in session supervision.
- Display bookings with statuses: confirmed, cancelled, refunded, payment failed, pending payment expired.
- Provide an exceptional refund action for confirmed/paid bookings when normal policy does not apply.
- Require a reason and confirmation.
- Delegate Stripe logic to `RefundService`; do not put Stripe calls in Livewire.
- Generate credit-note side effects through existing refund/credit-note events.
- Store audit metadata if current schema supports it; otherwise add a small refund audit table.

**Acceptance criteria:**

- Admins can refund a paid booking exceptionally with a recorded reason.
- Refund action is not available for unpaid or already refunded bookings.
- Accountant dashboard reflects the refund/credit note after processing.

**Test expectations:**

- Service tests for exceptional refund guardrails.
- Livewire test for admin refund action.
- Listener test for credit note generation after exceptional refund.

## Story 3.4: Admin Commission and Subscription Configuration Review

**Role:** Admin

**Context:** Subscription tiers and payout/commission logic exist in services, but admins have no read/write view of the active economic configuration. MVP operators need at least visibility before launch.

**Implementation details:**

- Add an admin read-only configuration page first, showing current subscription plans, commission rates, VAT assumptions, payment fee assumptions, and config source.
- If values are hardcoded in enums/services, expose them read-only and document where changes require code/config deployment.
- If changing values at runtime is required, add a separate story and a database-backed configuration model with audit trail.
- Link from admin dashboard.

**Acceptance criteria:**

- Admin can verify what commission/subscription rules the app is using without code access.
- Page makes it clear which values are deploy-time config vs runtime editable.

**Test expectations:**

- Feature/Livewire test that page renders expected configured values.

---

# Epic 4: Accountant Portal and Financial Operations

## Goal

Make the accountant role operational, discoverable, and aligned with the Belgian payout/invoice workflow described in requirements.

## Story 4.1: Accountant Dashboard Navigation and Landing State

**Role:** Accountant

**Context:** `Accountant\Dashboard`, invoice detail, XML download, export, and stuck sessions queue exist, but no navigation entry exposes them.

**Implementation details:**

- Implement the accountant nav changes from Story 1.3.
- Add dashboard summary cards above the invoice table:
  - Current month revenue TTC/HTVA/VAT.
  - Coach payout amount pending.
  - Number of invoices, credit notes, refunds.
  - Stuck sessions count.
  - Export shortcuts.
- Keep the existing invoice filters/table below.

**Acceptance criteria:**

- Accountant lands on a useful dashboard after login.
- Accountant can reach invoice list, invoice detail, XML download, and exports from visible UI.

**Test expectations:**

- Navigation tests for accountant links.
- Dashboard tests for summary cards and filters.

## Story 4.2: Daily Transaction Ledger

**Role:** Accountant

**Context:** The dashboard is invoice-centered. `UC-K01`, `UC-K03`, `UC-K07`, and `UC-K08` require visibility into all transaction flows, including payments, payouts, and refunds.

**Implementation details:**

- Add `App\Livewire\Accountant\Transactions\Index` under `/accountant/transactions`.
- Base it on bookings, invoices, Stripe payment identifiers, refund records/events, and coach/session relationships currently available in models.
- Columns: transaction date, type, athlete, coach, session, booking status, gross amount TTC, platform commission, payment fees if stored, coach payout, Stripe payment intent/checkout session id, refund status.
- Filters: date range, coach, session status, booking/payment status, anomaly flag.
- Export same filtered ledger to CSV/Excel, ideally through `FinancialExportService` extension.

**Acceptance criteria:**

- Accountant can inspect money movement even before an invoice is issued.
- Refunds and failed/expired payments are visible.
- Export matches on-screen filters.

**Test expectations:**

- Feature tests with paid, refunded, failed, and expired payment bookings.
- Export tests for ledger rows and filters.

## Story 4.3: Monthly Coach Payout and Invoice Request Workflow

**Role:** Coach, Accountant

**Context:** `doc/UseCases.md` says the platform cannot invoice for the coach; the coach must be able to make an invoice and get paid under timing conditions, with monthly detail. Current services generate invoices automatically on session completion, but the MVP needs a clear workflow that matches the legal/accounting model.

**Implementation details:**

- Define one canonical monthly payout statement model or reuse `Invoice` if it already represents the right document. If reuse is ambiguous, add a dedicated model such as `CoachPayoutStatement`.
- Statement period: calendar month, one coach.
- Statement lines: sessions completed in the period, paid bookings count, revenue TTC, revenue HTVA, VAT amount, payment fees, selected subscription tier, commission, coach payout, VAT-subject/non-subject payout formula result.
- Coach dashboard should show monthly statements and a call to action: request payout / create PEPPOL invoice / mark invoice submitted, depending final legal process.
- Accountant dashboard should show statement status: draft, awaiting coach invoice, invoice received, approved, paid, blocked.
- Do not auto-pay without an auditable state transition.
- Keep all amounts as integer cents.

**Acceptance criteria:**

- Coach can see the current and past monthly payout statements.
- Coach can initiate the required invoice/payout step from the dashboard.
- Accountant can approve or block a statement with reason.
- Payout formula for VAT-subject and non-subject coaches matches `VatService`/`PayoutService` tests.
- Existing PEPPOL XML generation remains available where legally appropriate.

**Test expectations:**

- Unit tests for statement calculation with the examples from `doc/UseCases.md`.
- Feature tests for coach statement view and accountant approval/blocking.
- Regression tests proving all monetary values are integer cents.

## Story 4.4: Payment Anomaly Detection Queue

**Role:** Accountant, Admin

**Context:** `UC-K09` requires anomaly detection. Current invoice detail includes discrepancy checks, but there is no dashboard-level anomaly queue.

**Implementation details:**

- Add computed anomaly flags for cases such as:
  - Confirmed booking with missing amount or missing Stripe payment id.
  - Paid booking whose session is cancelled but no refund/credit note exists.
  - Completed session with no invoice/statement.
  - Invoice totals not matching booking totals.
  - Coach Stripe onboarding incomplete while sessions are published.
- Add `Accountant\Anomalies\Index` or a dashboard panel.
- Each anomaly row must link to the relevant invoice, booking, session, and coach.
- Add resolve/ignore action only if an audit model is added; otherwise keep read-only for MVP.

**Acceptance criteria:**

- Accountant sees a count and list of actionable anomalies.
- Each anomaly explains the issue and the next recommended action.
- Admin can view the same queue or a subset from admin dashboard.

**Test expectations:**

- Unit tests for anomaly detector service.
- Feature tests for dashboard rendering.

---

# Epic 5: Coach Launch Journey

## Goal

Ensure approved coaches can move from application to bookable sessions without invisible blockers.

## Story 5.1: Coach Onboarding Checklist

**Role:** Coach

**Context:** Coach dashboard exists and Stripe onboarding starts at `/coach/stripe/onboard`, but coaches need a clear checklist for profile, approval, Stripe, VAT details, and first session.

**Implementation details:**

- Add an onboarding panel to `resources/views/livewire/coach/dashboard.blade.php`.
- Checklist items:
  - Coach profile approved.
  - Profile complete: specialties, bio, experience, postal code, enterprise number.
  - VAT status captured/verified.
  - Stripe onboarding complete.
  - At least one published future session.
  - Activity cover image selected or fallback acceptable.
- Each item links to the relevant action.
- Hide or collapse once all launch-critical items are complete.

**Acceptance criteria:**

- Newly approved coaches know exactly what remains before sessions are bookable.
- Stripe onboarding button is visible until complete.
- Missing VAT/enterprise data is visible before payout/invoicing problems occur.

**Test expectations:**

- Livewire tests for checklist state before and after each milestone.

## Story 5.2: Publish Guard for Payment Readiness

**Role:** Coach

**Context:** `Booking\Book::hasPaymentSetup()` blocks booking if the coach has no completed Stripe onboarding. A coach may still publish sessions that athletes cannot pay for.

**Implementation details:**

- Update `SessionService::publish()` or the Livewire publish action to check coach Stripe readiness.
- Either block publishing until onboarding complete, or allow publishing with an explicit non-bookable state. For MVP, blocking is cleaner.
- Return localized validation errors explaining the missing setup.
- Keep drafts editable while onboarding is incomplete.

**Acceptance criteria:**

- Coach cannot publish a payable session until Stripe onboarding is complete.
- Coach sees a direct link to start/continue onboarding.
- Already published sessions from before the guard are surfaced as requiring attention.

**Test expectations:**

- Unit/feature tests for publish blocked when `stripe_onboarding_complete = false`.
- Test publish succeeds when onboarding is complete.

## Story 5.3: Coach Session Performance Uses Paid Counts Consistently

**Role:** Coach

**Context:** Coach dashboard already uses confirmed booking count for total bookings and `amount_paid` for revenue, but fill rate uses `sport_sessions.current_participants`, which may include temporary holds depending booking state.

**Implementation details:**

- Decide whether fill rate means reserved spots or paid confirmed participants.
- For MVP dashboard revenue/performance, show both when useful:
  - Confirmed paid participants.
  - Temporary payment holds.
  - Available spots.
- Avoid using `current_participants` as a proxy for revenue.
- Update session cards and dashboard stats accordingly.

**Acceptance criteria:**

- Coach can distinguish paid participants from temporary holds.
- Revenue never includes unpaid or expired holds.
- Session confirmation threshold is visually tied to paid/confirmed bookings if business rules require that.

**Test expectations:**

- Dashboard tests with confirmed, pending-payment, cancelled, and refunded bookings.

---

# Epic 6: Athlete Booking and Payment Recovery

## Goal

Make payment failures, checkout abandonment, and cancellation policies understandable and recoverable for athletes.

## Story 6.1: Pending Payment Recovery Page

**Role:** Athlete

**Context:** `BookingPaymentReturnController` exists and bookings have `payment_expires_at`, but athletes need an obvious retry/cancel path for pending payments.

**Implementation details:**

- Add or enhance the payment return page to show status-specific content:
  - Success: booking confirmed or awaiting webhook confirmation.
  - Cancelled checkout: pending hold, expiry time, retry payment, cancel hold.
  - Failed payment: retry or release hold.
- Add retry action that creates a new Checkout Session for the same pending booking when still valid.
- Add cancel hold action delegating to `BookingService` or a dedicated payment-hold service.
- Show pending payment cards in `Athlete\Dashboard` with expiry countdown or timestamp.

**Acceptance criteria:**

- Athlete who leaves Stripe can recover without creating duplicate bookings.
- Expired holds cannot be retried.
- Cancelling a hold releases capacity.

**Test expectations:**

- Feature tests for success/cancel return statuses.
- Service tests for retry guardrails.
- Dashboard test showing pending payment state.

## Story 6.2: Booking Confirmation Step Before Stripe Redirect

**Role:** Athlete

**Context:** The booking button immediately creates a booking and redirects to Stripe. Athletes should confirm key details before capacity is held.

**Implementation details:**

- Add a Livewire confirmation modal in `Booking\Book`.
- Show session title, coach, date/time, location, price, payment methods, cancellation policy, and payment hold expiry.
- Only create the booking after the athlete confirms.
- Keep guest/user-not-verified behavior unchanged.

**Acceptance criteria:**

- Athlete sees clear booking/payment details before leaving Motivya.
- No booking row is created until confirmation.
- Modal is accessible on mobile.

**Test expectations:**

- Livewire test: clicking initial button opens modal and does not create booking.
- Confirm action creates booking and redirects to Stripe.

---

# Epic 7: MVP Smoke Data, Tests, and Launch Verification

## Goal

Provide a repeatable way to prove the MVP works for all four profiles after each deployment.

## Story 7.1: Cross-Role MVP Journey Seeder

**Roles:** Admin, Accountant, Coach, Athlete

**Context:** `MvpJourneySeeder` exists but is not called by default. A smoke-ready dataset should represent all four roles and common states.

**Implementation details:**

- Review and update `Database\Seeders\MvpJourneySeeder`.
- Seed users:
  - admin@example.test with MFA-ready instructions for local only.
  - accountant@example.test.
  - approved coach with completed Stripe onboarding fake data.
  - pending coach application.
  - athlete with upcoming, pending-payment, past, cancelled, and favourite sessions.
- Seed sessions across multiple Brussels postal codes so radius/city tests are visible.
- Seed invoices/credit notes/statements/anomalies as needed for accountant/admin dashboards.
- Add README/doc instructions for local use only; never seed predictable credentials in production.

**Acceptance criteria:**

- Running the seeder locally creates a usable demo for all four roles.
- Seeded sessions exercise map, city search, radius, booking, and accounting screens.

**Test expectations:**

- Seeder smoke test in testing environment if practical.

## Story 7.2: Automated Four-Role Smoke Test

**Roles:** All

**Context:** There are many focused tests, but no single high-level safety net proving the MVP journeys connect.

**Implementation details:**

- Add feature tests under `tests/Feature/Mvp/`.
- Test journeys:
  - Guest can browse sessions and view a session detail.
  - Athlete can reach dashboard, favourites, booking flow, and payment return recovery.
  - Coach can reach dashboard, profile edit, create session, and see onboarding checklist.
  - Accountant can reach dashboard, invoice detail, XML export, transaction/anomaly/stuck-session views.
  - Admin can reach dashboard, users, coach approval, sessions, activity images, data export.
- Mock Stripe services where payment/onboarding is involved.

**Acceptance criteria:**

- `php artisan test --filter=Mvp` proves role navigation and major pages do not 403 or crash.
- Smoke tests run in CI with SQLite.

**Test expectations:**

- Pest feature tests grouped or named consistently for easy filtering.

## Story 7.3: MVP Readiness Checklist Page for Operators

**Role:** Admin

**Context:** Launch needs operational confidence, not only passing tests.

**Implementation details:**

- Add a read-only admin readiness page or dashboard section with checks:
  - Stripe keys configured.
  - Mail configured.
  - Scheduler recently ran, if a scheduler heartbeat table is added.
  - Postal-code coordinate seed count is nonzero.
  - At least one admin with MFA.
  - Accountant user exists.
  - Activity images exist for common activity types.
  - Queue/cache/database health.
- Reuse `/health` where possible; do not expose sensitive config values.

**Acceptance criteria:**

- Admin sees a green/yellow/red checklist before launch.
- Missing critical setup links to the relevant admin page or documentation.

**Test expectations:**

- Feature test for readiness page with missing and complete states.

---

# Recommended Implementation Order

1. Epic 1: role entry points, accountant creation, navigation, login redirects.
2. Epic 2: radius, city search, map/list consistency.
3. Epic 3: admin dashboard, user management, session supervision.
4. Epic 4: accountant ledger, payout/invoice workflow, anomaly queue.
5. Epic 5 and Epic 6: coach/athlete journey polish around publish/payment recovery.
6. Epic 7: demo data and smoke tests once the journeys are stable.

# Non-MVP Items to Keep Deferred

These remain outside the real MVP unless explicitly reprioritized:

- Coach ratings/reviews and moderation.
- Calendar sync.
- Weather outfit recommender.
- Push notifications for favourites.
- Advanced analytics, scoring, dynamic pricing, recommendations, ambassador program, and marketing automation.
- External accounting software integration beyond CSV/Excel/PEPPOL-ready export.
