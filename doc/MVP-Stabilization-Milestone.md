# Milestone: MVP Partner Demo Stabilization

Date: 2026-05-05

## Purpose

The previous MVP milestones, epics, and stories have been implemented, but partner-demo testing exposed launch-blocking defects in the operational flow. This milestone focuses on making the existing Laravel/Livewire product reliable enough for a partner presentation. It is not a broad refactor. The goal is to repair broken journeys, make existing tools discoverable, and add the minimum observability needed to know when the MVP is healthy.

## Audit Summary

This report is based on a fresh read-only code audit plus a read-only OVH production check. Production access was available through the deploy user. No files, services, database rows, configuration, or scheduler state were changed during the check.

Observed production state:

- The deployed app points to `/opt/motivya/current -> /opt/motivya/releases/20260505134556`.
- `scheduler_heartbeats` exists, but no heartbeat rows exist for the five critical scheduled commands.
- No deploy-user crontab exists. No `motivya-scheduler.service` or `motivya-scheduler.timer` was found. `cron.service` is active, but the visible Motivya cron entry only covers database backup.
- `postal_code_coordinates` has `0` rows.
- `sport_sessions` has `9` rows and all `9` are missing latitude or longitude.
- `activity_images` has `1` row and the physical file exists in shared storage, but `/opt/motivya/current/public/storage` is missing and the public `/storage/...` URL returns `404`.
- Bookings grouped by status: `cancelled = 1` with `amount_paid = 0`; `confirmed = 1` with `amount_paid = 1234`.
- The confirmed paid booking is missing `stripe_payment_intent_id`, which explains why Stripe refund processing cannot proceed.
- `motivya-queue.service` is active and enabled.

Core diagnosis:

- Booking and payment are too fragile around Stripe failures, missing payment intent IDs, and webhook reconciliation.
- Maps are implemented with MapLibre/OpenFreeMap, but no production session has coordinates, so no markers are produced and the map never renders.
- Uploaded activity images are stored but not publicly served because the Laravel public storage link is missing in production.
- The scheduler is not configured in production, so readiness correctly reports missing scheduled-command heartbeats, although the French wording is confusing.
- Admin and accountant pages for audits and operations exist, but several are hidden from navigation or dashboard cards.
- Accountant and coach dashboards are invoice/status driven; a paid booking without a confirmed payment-intent trail can appear in admin tools while remaining invisible or unusable elsewhere.

Implementation decisions for this milestone:

- Booking holds remain in `PendingPayment` when Stripe Checkout creation fails, and athletes recover through retry/cancel actions until the hold expires.
- Map display stays on MapLibre GL JS with OpenFreeMap tiles. Google is used for geocoding and directions through an environment-managed API key.
- Postal-code coordinates are loaded from a curated built-in Belgian/Brussels MVP dataset through a production-safe command.
- OVH scheduler setup uses a systemd service and timer, not cron.
- Production storage uses an explicit release-time symlink from `public/storage` to shared public storage.
- Admin repair tools are read-only for MVP and show exact operator commands instead of executing repairs from the web UI.
- Role navigation stays compact in the top bar and exposes role tools through rich desktop dropdown and mobile menu links.
- Payment anomaly queues are read-only and include links plus recommended actions; no resolve/ignore workflow is added in this milestone.
- Map and image smoke coverage uses Playwright/browser tests.
- Payment reconciliation defaults to dry-run and includes an explicit repair mode that queries Stripe only when a single safe match exists.

## MVP Completion Definition For This Milestone

The MVP is partner-demo ready when:

- An athlete can discover sessions, see a map, open directions, book a session, complete Stripe payment, and see the confirmed booking.
- A coach can see the paid booking and revenue signal on the dashboard.
- An accountant can see the payment in transaction tools, audit the payment event, and understand anomalies when payment data is incomplete.
- An admin can upload activity images, see them immediately, use exceptional refunds for eligible paid bookings, browse audit logs, and understand readiness checks.
- Production has scheduler, queue, storage, postal-code/coordinate, and Stripe setup that make the readiness page green or clearly actionable.

---

# Epic 1: Booking, Payment, and Refund Recovery

## Story 1.1: Restore Reliable Stripe Checkout Redirects

**Role:** Athlete

**Context:** The athlete clicks the session booking button, the button animates briefly, and no payment page opens. The booking action is implemented in `App\Livewire\Booking\Book::book()`. It creates the booking and calls `PaymentService::createCheckoutSession()` inside one database transaction, then returns `redirect()->away($checkoutSession->url)`. The catch block only handles domain exceptions plus `InvalidArgumentException` and `RuntimeException`; Stripe SDK exceptions and other throwables bubble out. If Stripe rejects the checkout payload, coach account, API key, payment method, or transfer data, the user can experience a silent Livewire failure or server error instead of a recoverable payment flow.

**Implementation details:**

- Update `app/Livewire/Booking/Book.php` so all payment-start failures are caught and logged with booking, session, athlete, and exception class context.
- Keep domain exceptions user-facing, but replace Stripe/internal messages with a localized generic message such as `bookings.payment_start_failed`.
- Validate that the returned checkout session has a non-empty `url` before redirecting. Treat a missing URL as a payment-start failure.
- Avoid leaving the modal closed with no visible state. On failure, resync state and dispatch an error notification.
- Add localized strings in `lang/fr/bookings.php`, `lang/en/bookings.php`, and `lang/nl/bookings.php`.
- Keep user-facing text localized and avoid exposing raw Stripe error details in the UI.

**Acceptance criteria:**

- If Stripe checkout creation succeeds, the athlete is redirected to Stripe Checkout.
- If Stripe checkout creation fails, the athlete sees a localized error notification and remains on the session detail page.
- The application log contains enough structured context to diagnose the failure.
- No raw Stripe exception message is shown to the athlete.

**Test expectations:**

- Livewire test: checkout-session mock returns a valid URL and `book()` redirects away.
- Livewire test: checkout-session mock throws a Stripe-like exception and the component dispatches the localized error.
- Livewire test: checkout-session mock returns no URL and the component dispatches the localized error.

## Story 1.2: Separate Booking Hold Creation From Stripe Session Creation

**Role:** Athlete

**Context:** `Booking\Book::book()` currently wraps both `BookingService::book()` and `PaymentService::createCheckoutSession()` in one transaction. `PaymentService::createCheckoutSession()` also opens a nested transaction to store the Stripe checkout session ID and audit event. This mixes a local capacity hold with an external HTTP call to Stripe. When Stripe fails, the local booking can be rolled back, giving the athlete no retry path and making failures hard to inspect.

**Implementation details:**

- Keep `BookingService::book()` responsible for the atomic local capacity hold and `PendingPayment` booking creation.
- Move the Stripe checkout-session call outside the booking transaction.
- If Stripe session creation succeeds, persist `stripe_checkout_session_id` and audit `BookingPaymentStarted` as today.
- If Stripe session creation fails after the booking hold exists, keep the `PendingPayment` hold while `payment_expires_at` is still valid and surface retry/cancel actions through the payment recovery flow.
- Ensure capacity is not double-incremented on retry. Reuse the existing pending booking for the same athlete/session.
- Centralize retry behavior in the existing `PaymentHoldService`; do not add a second retry service and do not duplicate retry logic in Livewire.

**Acceptance criteria:**

- A Stripe outage or invalid Stripe account does not create duplicate bookings.
- The athlete can retry payment for an existing valid pending hold.
- Expired holds are not retried and capacity is released by the expiry command.
- The booking action remains atomic for capacity but not dependent on a long-running external HTTP call inside the database transaction.

**Test expectations:**

- Service test: booking hold is created once and reused on retry.
- Service test: failed checkout creation does not increment capacity twice.
- Feature test: expired pending hold cannot be retried.

## Story 1.3: Reconcile Stripe Webhooks Into Dashboard-Visible Payment State

**Role:** Athlete, Coach, Accountant, Admin

**Context:** Production contains one `confirmed` booking with `amount_paid = 1234` but no `stripe_payment_intent_id`. This booking can appear in admin refund tooling because it is confirmed and paid, but Stripe refunds cannot be created without a payment intent. Coach and accountant dashboards also depend on booking status, invoice generation, and/or payment fields, so incomplete payment state makes the MVP appear inconsistent.

**Implementation details:**

- Audit `StripeWebhookController::handleCheckoutSessionCompleted()` and payment-intent handlers so a completed checkout always stores both `amount_paid` and `stripe_payment_intent_id` when Stripe provides them.
- If the checkout session does not include an expanded payment intent, retrieve the Stripe Checkout Session by ID with `payment_intent` expanded before confirming the booking.
- Add idempotent reconciliation logic that can repair a booking by `stripe_checkout_session_id`, payment-intent metadata, or session/athlete metadata.
- Add an artisan command named `payments:reconcile-bookings` with default `--dry-run` behavior. It must find bookings with `status = confirmed`, `amount_paid > 0`, and missing `stripe_payment_intent_id`; `--repair` mode must query Stripe and update a booking only when a single safe match exists.
- Record audit events for automatic and manual payment repairs.
- Surface unrepaired rows as payment anomalies for accountant/admin review.

**Acceptance criteria:**

- A successful Stripe Checkout webhook confirms the booking and stores the payment intent ID.
- Confirmed paid bookings missing a payment intent are visible as anomalies.
- A reconciliation command can safely repair recoverable records or explain why a record cannot be repaired.
- Coach dashboard, accountant transaction ledger, and admin refund queue agree on the booking state after reconciliation.

**Test expectations:**

- Feature test: `checkout.session.completed` with a payment intent confirms the booking and persists the intent ID.
- Feature test: webhook metadata fallback finds the pending booking.
- Unit/command test: reconciliation flags missing payment intent rows in dry-run mode.
- Unit/command test: reconciliation refuses ambiguous Stripe matches.

## Story 1.4: Make Exceptional Refunds Actionable and Diagnosable

**Role:** Admin, Accountant

**Context:** Admin exceptional refund currently raises `Echec du traitement du remboursement`. The admin component validates that the booking is confirmed and paid, then calls `RefundService::refund()`. `RefundService` requires `stripe_payment_intent_id`; production's confirmed paid booking does not have it, so the refund must fail. The UI uses one generic message for ineligible bookings and Stripe/internal failures.

**Implementation details:**

- Keep `RefundService` as the single place that calls Stripe.
- In `app/Livewire/Admin/Refunds/Index.php`, distinguish these exact cases before calling Stripe: not confirmed, amount paid <= 0, already refunded, missing payment intent, and Stripe API failure.
- Add localized messages for each case in `lang/*/admin.php`.
- On missing `stripe_payment_intent_id`, disable the refund button and show a specific action that sends the booking to payment reconciliation/anomaly review.
- Keep `AdminRefundAudit` rows for every attempted refund and include the exact internal error there.
- After a successful refund, ensure the accountant ledger and audit log show refund requested/completed and any credit-note side effects.

**Acceptance criteria:**

- Admins cannot attempt a Stripe refund for a booking missing a payment intent without first reconciling it.
- Admins see a precise localized reason when a refund cannot start.
- Successful refunds update booking status and audit records as today.
- Accountant tools reflect successful refunds and failed refund attempts.

**Test expectations:**

- Livewire test: confirmed paid booking without payment intent shows a missing-payment-intent error.
- Livewire test: Stripe exception shows a Stripe failure error and logs the underlying exception.
- Service test: already-refunded booking remains a no-op.
- Feature test: successful exceptional refund creates `AdminRefundAudit` and financial audit events.

## Story 1.5: Align Refund Queue, Coach Dashboard, and Accountant Ledger Semantics

**Role:** Coach, Accountant, Admin

**Context:** The admin refunds page currently queries all bookings by default, while the refund action only works for confirmed paid bookings. Coach dashboard revenue counts confirmed bookings. Accountant dashboard summary cards are invoice-based, while the transaction ledger is booking-based. This creates confusion when a booking is pending, paid-but-not-confirmed, confirmed-without-payment-intent, refunded, or cancelled.

**Implementation details:**

- Define a shared set of booking payment states for display: pending hold, paid confirmed, paid but anomalous, cancelled unpaid, refunded, failed/expired.
- Update the admin refund queue default filter to show two groups only: confirmed paid bookings with a payment intent, and confirmed paid bookings missing a payment intent. Keep explicit filters for all booking statuses.
- Update the accountant transaction ledger to include a clear anomaly column computed from booking fields: missing payment intent, amount mismatch, status/payment mismatch, missing invoice/statement.
- Update the coach dashboard to show paid confirmed bookings and pending holds separately, with an anomaly warning if a booking is confirmed with `amount_paid > 0` but no payment intent.
- Put all anomaly classification logic in `AnomalyDetectorService`; Blade and Livewire components must consume computed anomaly flags instead of duplicating query logic.

**Acceptance criteria:**

- A paid confirmed booking appears in coach revenue, accountant ledger, and admin refund queue consistently.
- A confirmed paid booking missing a payment intent is visible but labelled anomalous and not refundable until repaired.
- Pending-payment holds never inflate coach revenue.
- Admin refund queue defaults to records requiring refund action.

**Test expectations:**

- Feature tests with confirmed paid, pending-payment, cancelled, refunded, and missing-intent bookings.
- Livewire tests for admin refund default filtering and explicit status filtering.
- Dashboard tests proving revenue ignores pending/unpaid bookings.

---

# Epic 2: Maps, Directions, Coordinates, and Activity Images

## Story 2.1: Make Session Maps Render Even When Data Is Missing

**Role:** Athlete, Guest

**Context:** The discovery page only renders `<x-session-map>` when `$markers->isNotEmpty()`. Production has no session coordinates, so no markers are produced and the map is never shown. The session detail page does not include any map at all. Users expect map context during discovery and a location preview on detail pages.

**Implementation details:**

- Keep MapLibre GL JS and OpenFreeMap as the default MVP map stack. It is already implemented in `resources/js/session-map.js` and does not require a Google Maps key for map display.
- Update the discovery view so a map container can render with Brussels as a fallback center even when there are no markers, alongside a localized message explaining that no mapped sessions match the current filters.
- Avoid hiding all location context when filters return no markers.
- Replace `<x-session-map>` with a reusable map component that accepts a unique DOM ID, marker collection, fallback center, height, and single-marker detail mode.
- Ensure the map component supports multiple instances or unique DOM IDs. The current component uses the fixed `id="session-map"`, which will break if more than one map appears on a page.
- Localize empty-map messages in `lang/*/sessions.php`.

**Acceptance criteria:**

- The discovery page shows a map area for mapped searches and a meaningful empty state when no markers are available.
- Session detail shows a map preview when the session has coordinates.
- The map JavaScript works with a unique container ID and does not rely on a single global `#session-map` element.
- No JavaScript error is thrown when markers are empty.

**Test expectations:**

- Blade/feature test: discovery renders the map container for empty markers with an empty-state message.
- Feature test: session detail with coordinates renders a map component.
- Playwright smoke test: map canvas is nonblank with seeded coordinates.

## Story 2.2: Load Belgian Postal Coordinates and Backfill Sessions in Production

**Role:** Admin, Operator

**Context:** Production has `postal_code_coordinates = 0` and all sessions are missing latitude/longitude. The default `DatabaseSeeder` calls `PostalCodeCoordinatesSeeder`, but the manual MVP smoke setup instructs `php artisan db:seed --class=MvpJourneySeeder`, which skips postal-code data. Production migrations do not load seeders, and the deploy script only runs `php artisan migrate --force`.

**Implementation details:**

- Make postal-code coordinates operational data, not optional demo data.
- Provide an idempotent production-safe command named `geo:load-postal-codes` that loads the curated built-in Belgian/Brussels postal-code coordinate reference without creating demo users or predictable credentials.
- Update `MvpJourneySeeder` so it calls `PostalCodeCoordinatesSeeder` internally for local smoke testing, and update `doc/MVP-Smoke-Test.md` to document that behavior.
- Use the existing `sessions:backfill-coordinates` command after postal-code data is loaded.
- Add a deploy/post-deploy checklist step for non-demo data: load coordinates, then backfill existing sessions.
- Keep production seeding guarded so demo accounts are never created in production.

**Acceptance criteria:**

- Production has postal-code coordinate rows before partner demos.
- Existing sessions with known postal codes receive latitude/longitude through an idempotent command.
- The readiness page can distinguish "reference data missing" from "sessions still need backfill".
- Running the command twice is safe.

**Test expectations:**

- Command test: postal-code loader is idempotent.
- Command test: backfill updates sessions with known postal codes and skips unknown postal codes.
- Readiness test: missing coordinates and missing reference data produce separate actionable statuses.

## Story 2.3: Add Directions From Session Detail

**Role:** Athlete, Guest

**Context:** Session detail displays the location text and postal code, but there is no button to open directions in the user's preferred map application. Users expect an obvious directions action next to the address.

**Implementation details:**

- Add a localized "Directions" button next to the session address in `resources/views/livewire/session/show.blade.php`.
- Use a universal HTTPS Google Maps directions link for web and mobile: `https://www.google.com/maps/dir/?api=1&destination={lat},{lng}` when coordinates exist.
- If coordinates are missing, fall back to URL-encoded address, postal code, and Belgium: `destination={location}+{postal_code}+Belgium`.
- Open in a new tab with `target="_blank"` and `rel="noopener noreferrer"`.
- Add a compact navigation icon using the app's established icon approach. If no icon library is currently available in Blade, use one minimal inline SVG and keep it inside a Blade component for reuse.
- Add localized labels in `lang/fr/sessions.php`, `lang/en/sessions.php`, and `lang/nl/sessions.php`.

**Acceptance criteria:**

- Every public session detail page has a directions button near the address.
- Sessions with coordinates open a directions URL using lat/lng.
- Sessions without coordinates still open a useful address-based directions URL.
- The button is accessible on mobile and desktop.

**Test expectations:**

- Feature test: detail page with coordinates contains a Google Maps directions URL with latitude/longitude.
- Feature test: detail page without coordinates contains an address-based directions URL.
- Render assertions for all three languages.

## Story 2.4: Configure Google Geocoding With MapLibre/OpenFreeMap Maps

**Role:** Operator, Developer

**Context:** Map display is currently free and API-keyless through MapLibre/OpenFreeMap. That remains the required MVP map-rendering stack. Address/city geocoding and directions must use Google APIs through an environment-managed key so Brussels searches and directions are reliable during partner demos.

**Implementation details:**

- Keep MapLibre GL JS and OpenFreeMap for map rendering. Do not replace the map UI with Google Maps JavaScript in this milestone.
- Add a `config/maps.php` file with these settings: MapLibre tile style URL, Google directions base URL, Google geocoding base URL, timeout, cache TTL, and `GOOGLE_MAPS_API_KEY`.
- Implement `GeocodingService` with two ordered resolvers: first the local curated postal-code table, then Google Geocoding API.
- Cache Google geocoding responses in a dedicated `geocoding_cache` table keyed by normalized query, locale, country, and provider.
- Never call a free public geocoding service on every keystroke; resolve on search submission or debounced final input only.
- Add readiness checks for the Google API key shape, geocoding cache table availability, and last successful geocoding probe.

**Acceptance criteria:**

- The app resolves Belgian city/postal-code searches through the local curated table first and Google Geocoding second.
- Map display remains functional without a Google key.
- Missing/invalid Google API key produces an actionable readiness warning and disables Google fallback geocoding while preserving local postal-code search.
- Geocoding responses are cached and rate-limited.

**Test expectations:**

- Unit tests for provider selection and fallback behavior.
- Unit tests for cached geocoding results.
- Feature test for city/postal-code search with mocked external provider.

## Story 2.5: Fix Activity Image Public Serving and Upload UX

**Role:** Admin, Coach

**Context:** Production has one activity image row and the physical file exists in shared storage, but `/public/storage` is missing and the public image URL returns `404`. The admin page stores images on the `public` disk and renders them through `Storage::disk('public')->url($path)`, which expects Laravel's public storage link to exist. The upload form also resets after save, and validation can show "image is required" if the upload state is lost or the admin submits before Livewire finishes uploading.

**Implementation details:**

- Update deployment so each release has the explicit symlink `public/storage -> /opt/motivya/shared/storage/app/public`.
- Add a deploy-script check after release extraction and current swap to create or verify the public storage symlink in the current release.
- Add an admin readiness check for public storage URL reachability, separate from activity-image count.
- Improve the upload Blade with Livewire upload progress and disabled submit state while `image` is uploading.
- Keep image validation strict: required, jpg/jpeg/png/webp, max 2048 KB.
- After successful upload, verify the stored file exists and dispatch success only after the database row is created.
- For production, ensure shared storage permissions allow PHP-FPM to write and Nginx to read uploaded files.

**Acceptance criteria:**

- Uploaded activity images display in the admin gallery immediately after upload.
- Coach session cards and detail pages can display selected cover images.
- The readiness page shows a red/yellow status if the public storage link is missing or unreadable.
- Admin cannot accidentally submit while the file is still uploading.

**Test expectations:**

- Feature test: upload stores a file and renders the expected `/storage/activity-images/...` URL.
- Feature/readiness test: missing public storage link is reported.
- Browser/Livewire test: submit button is disabled during upload progress.

---

# Epic 3: Scheduler, Readiness, and Production Operations

## Story 3.1: Configure the Laravel Scheduler on OVH

**Role:** Operator, Admin

**Context:** The readiness page reports every critical scheduled command as never run. Production confirms no heartbeat rows, no deploy-user crontab, and no `motivya-scheduler` systemd unit/timer. `routes/console.php` schedules the commands, and each relevant command records `SchedulerHeartbeat::record(...)` when it runs. The queue worker is active, but the scheduler is missing.

**Implementation details:**

- Add scheduler setup to `scripts/provision.sh` using `motivya-scheduler.service` and `motivya-scheduler.timer`. The timer must run `php artisan schedule:run` every minute.
- Ensure the scheduler command runs in `/opt/motivya/current` and uses the production `.env`.
- Write scheduler logs to shared storage, for example `/opt/motivya/shared/storage/logs/scheduler.log`.
- Ensure the scheduler survives deploys because `/opt/motivya/current` is a symlink updated by deploy.
- Document the read-only status checks operators can use: `systemctl status`, `journalctl`, and readiness page.

**Acceptance criteria:**

- The scheduler runs every minute in production.
- Critical commands update `scheduler_heartbeats` according to their schedule.
- The readiness page turns green/yellow based on real heartbeat recency.
- Scheduler logs are available without exposing secrets.

**Test expectations:**

- Script review/test: provision creates the scheduler service and timer idempotently.
- Feature test: readiness reports green when heartbeat rows are recent.
- Feature test: readiness reports red/yellow when heartbeat rows are missing or stale.

## Story 3.2: Make Readiness Messages Actionable in French

**Role:** Admin

**Context:** The French readiness label `Battements du planificateur` is not intelligible for operators. The underlying check is useful, but the wording should explain scheduled task status rather than literally translating "heartbeat".

**Implementation details:**

- Replace French strings such as `Battements du planificateur` with `Exécution des tâches planifiées` in `lang/fr/admin.php`.
- Update detail subtitles to explain that each row shows the last successful execution of a scheduled command.
- Update `readiness_scheduler_never_run` to mention that the production scheduler is not configured or has not yet executed the command.
- For postal-code readiness, mention the exact operational action: load coordinate reference data and backfill session coordinates.
- Keep translations consistent in English and Dutch if wording changes alter meaning.

**Acceptance criteria:**

- A French-speaking admin understands what the scheduler readiness check means.
- The postal-code red flag explains where the data comes from and what command/runbook loads it.
- Every non-green readiness message points to a route, command, or documentation path.

**Test expectations:**

- Translation assertion or snapshot test for the French scheduler labels.
- Feature test: readiness page renders the updated French strings.

## Story 3.3: Expand Readiness Checks for Real MVP Blockers

**Role:** Admin, Operator

**Context:** Current readiness checks cover Stripe key shape, mail, DB, cache, queue config, scheduler heartbeats, postal-code count, admin MFA, accountant existence, activity images, and billing config. The production audit shows additional MVP blockers: missing public storage link, sessions missing coordinates, Stripe-ready demo coach account placeholders, confirmed paid bookings missing payment intent, and possibly missing scheduler service despite queue being active.

**Implementation details:**

- Add readiness checks for public storage link and a sample public-read check. When no activity image exists, the public-read check reports yellow with the message that an image upload is required before reachability can be verified.
- Split postal-code readiness into reference-data count and session-coordinate coverage.
- Add a payment-data readiness check: count confirmed bookings with `amount_paid > 0` and missing `stripe_payment_intent_id`.
- Add a scheduler-service hint if heartbeat rows are missing for all commands.
- Add a Stripe Connect readiness check for approved coaches with published/confirmed sessions but incomplete or placeholder-looking Stripe accounts.
- Link actionable checks to admin pages: activity images, readiness docs, anomalies, users/coaches, billing config.
- Avoid exposing secrets or exact API keys.

**Acceptance criteria:**

- The readiness page catches the exact production issues found in this audit.
- Red/yellow statuses explain whether the issue is configuration, missing data, or a data repair task.
- Admins can navigate from each actionable readiness item to the next screen or documentation.

**Test expectations:**

- Feature tests for storage link missing/present.
- Feature tests for sessions missing coordinates.
- Feature tests for confirmed paid booking missing payment intent.
- Feature tests for placeholder Stripe Connect account warnings.

## Story 3.4: Add a Production Runbook for First Deploy and Demo Readiness

**Role:** Operator

**Context:** The deployment script runs migrations, warms caches, and restarts queues, but does not currently ensure scheduler setup, public storage link, postal-code loading, session coordinate backfill, or payment reconciliation. The manual smoke-test document is local-demo oriented and currently skips postal-code reference seeding when running only `MvpJourneySeeder`.

**Implementation details:**

- Create `doc/Production-Readiness-Runbook.md` describing the non-secret operational steps for first deploy and pre-demo verification.
- Include explicit commands for: scheduler status, queue status, public storage link check, postal-code data load, session coordinate backfill, payment reconciliation dry-run, readiness page review, and smoke journey.
- Update `doc/MVP-Smoke-Test.md` so local setup includes `php artisan storage:link`, automatic postal-code loading through `MvpJourneySeeder`, and `php artisan sessions:backfill-coordinates` after seeding.
- Include a warning that `MvpJourneySeeder` must never run in production.
- Include how to create real Stripe test-mode Express accounts instead of placeholder account IDs.

**Acceptance criteria:**

- A developer/operator can prepare a fresh local or production-like environment without discovering missing setup steps by trial and error.
- The runbook explains which commands are safe in production and which are local-only.
- The readiness page and runbook tell the same story.

**Test expectations:**

- Documentation review checklist in PR.
- Add the `mvp:health-snapshot` artisan command from Story 6.3 and document it as the pre-demo smoke command. It must run read-only readiness checks and exit non-zero on red blockers.

---

# Epic 4: Navigation, Audit Discovery, and Admin Operations

## Story 4.1: Make Role Navigation Useful for Daily Work

**Role:** Admin, Accountant, Coach, Athlete

**Context:** The top bar always presents the app title, sessions, user dropdown, and language switch. The dropdown contains some role links, but several high-value pages are missing. Admins need rapid access to sessions, refunds, readiness, anomalies, and audit logs. Accountants need transactions, anomalies, stuck sessions, exports, payout statements, and financial audit logs. The mobile menu has the same discoverability gaps.

**Implementation details:**

- Update `resources/views/components/nav/user-menu.blade.php` and `resources/views/components/nav/mobile-menu.blade.php`.
- For admin users, include links to `admin.dashboard`, `admin.users.index`, `admin.coach-approval`, `admin.sessions.index`, `admin.refunds.index`, `admin.anomalies.index`, `admin.audit-events.index`, `admin.activity-images`, `admin.data-export`, `admin.configuration.billing`, and `admin.readiness`.
- For accountant users, include links to `accountant.dashboard`, `accountant.transactions.index`, `accountant.payout-statements.index`, `accountant.anomalies.index`, `accountant.audit-events.index`, and `accountant.export`.
- For coaches, include links to `coach.dashboard`, `coach.sessions.create`, `coach.profile.edit`, `coach.payout-history`, and exactly one Stripe link: `coach.stripe.onboard` when `stripe_account_id` is empty, otherwise `coach.stripe.refresh`.
- For athletes, include links to `athlete.dashboard`, `athlete.favourites`, `sessions.index`, and the pending-payment recovery page when an active pending hold exists.
- Admins must see the accountant/finance section whenever they pass `access-accountant-panel`; label it `Finance` rather than hiding it behind accountant-only conditions.
- Add missing `common.nav.*` translation keys in all supported languages.

**Acceptance criteria:**

- Each role can reach its main tools in two clicks or fewer from desktop and mobile navigation.
- Admin and accountant audit pages are discoverable without typing URLs.
- The navigation remains readable on mobile.
- Guest navigation still prioritizes discovery and auth.

**Test expectations:**

- Navigation render tests for guest, athlete, coach, accountant, and admin.
- Assertions cover desktop dropdown and mobile menu.

## Story 4.2: Add Audit Log Cards to Admin and Accountant Dashboards

**Role:** Admin, Accountant

**Context:** Admin audit browsing exists through routes and Livewire components under `admin.audit-events.*`, and accountant financial audit browsing exists under `accountant.audit-events.*`. Tests already cover access and filters, but the pages are hidden from dashboard cards and incomplete navigation.

**Implementation details:**

- Add an admin dashboard card linking to `route('admin.audit-events.index')`.
- Add an accountant dashboard card linking to `route('accountant.audit-events.index')`.
- Include useful counts: total recent audit events for admin; recent financial audit events for accountant.
- Add or update explicit translation keys for audit cards in `lang/*/admin.php` and `lang/*/accountant.php`; do not rely on fallback keys.
- Keep accountant audit policy scoped to financial event types only.

**Acceptance criteria:**

- Admins can browse all audit events from the dashboard.
- Accountants can browse financial audit events from the dashboard.
- Dashboard counts match the same policy scope as the target pages.

**Test expectations:**

- Livewire/dashboard test: admin dashboard renders audit card and link.
- Livewire/dashboard test: accountant dashboard renders financial audit card and link.
- Authorization regression test: accountant cannot view non-financial audit events.

## Story 4.3: Improve Admin Refund Queue Usability

**Role:** Admin

**Context:** The exceptional refund page is operationally important, but currently a failed refund gives a generic error and the default list can include records that are not actionable. During partner demos, admins need to understand whether a booking is refundable, blocked by missing Stripe data, already refunded, or out of policy.

**Implementation details:**

- Add visible status badges for refund eligibility: eligible, already refunded, missing payment intent, unpaid, pending payment, cancelled unpaid.
- Disable the refund action when eligibility prerequisites are missing.
- Link missing-payment-intent records to the payment anomaly/reconciliation story.
- Show the most recent `AdminRefundAudit` status and error for each booking. If no audit exists, display the localized `admin.refunds_no_attempts` label.
- Keep the reason field mandatory for exceptional refunds.

**Acceptance criteria:**

- Admins can tell why a refund action is or is not available before clicking.
- Failed refund attempts leave a visible audit trail.
- The page defaults to operationally relevant rows and still supports filtering.

**Test expectations:**

- Livewire test: eligible booking shows refund action.
- Livewire test: missing payment intent disables refund and shows reason.
- Livewire test: last refund audit status is displayed.

## Story 4.4: Add Admin Access to Operational Data Repair Tools

**Role:** Admin, Operator

**Context:** Several MVP blockers are data/config repair tasks: postal codes missing, sessions missing coordinates, paid bookings missing payment intents, public images not reachable. Today these are only visible as symptoms across pages or red readiness checks.

**Implementation details:**

- Add a read-only admin operations panel inside the readiness page. It must show exact operator commands for coordinate loading, coordinate backfill, payment reconciliation dry-run, anomaly detection refresh, and storage link inspection.
- Do not execute repair commands from the web UI in this milestone.
- Include these exact commands in the panel: `php artisan geo:load-postal-codes`, `php artisan sessions:backfill-coordinates`, `php artisan payments:reconcile-bookings --dry-run`, `php artisan payments:reconcile-bookings --repair`, and `php artisan mvp:health-snapshot`.
- Do not run production demo seeders from the admin UI.
- Record audit events for admin-triggered repair actions.

**Acceptance criteria:**

- Admins can see which repair command is needed for a red readiness item.
- Operators can copy exact commands from the readiness page.
- Actual repair execution happens outside the web UI and is audited by the artisan command itself when it changes data.

**Test expectations:**

- Feature tests for the read-only command panel.
- Authorization tests: only admins can access repair controls.
- Audit tests for executed repair actions.

---

# Epic 5: Accountant and Coach Financial Visibility

## Story 5.1: Show Paid Bookings in Accountant Ledger Before Invoicing

**Role:** Accountant

**Context:** The accountant dashboard summary is invoice-centered, while the transaction ledger is booking-centered. In a live MVP, payment visibility must not wait for invoice generation. The reported paid booking should be visible in accountant tools even if invoice or payout statement generation has not yet run.

**Implementation details:**

- Ensure `Accountant\Transactions\Index` includes all bookings with money movement or payment state: pending payment, confirmed paid, cancelled paid, refunded, failed/expired.
- Add columns for `amount_paid`, `stripe_checkout_session_id`, `stripe_payment_intent_id`, booking status, session status, invoice existence, payout statement existence, and anomaly state.
- Add a default date range that includes recent bookings and is clear in the UI.
- Add quick filters: paid without invoice, paid without payment intent, refunded, pending payment, failed/expired.
- Extend exports to match on-screen filters and include anomaly columns.

**Acceptance criteria:**

- Accountant can see a paid booking immediately after payment confirmation.
- Missing invoice/statement/payment-intent data is visible as an accounting anomaly, not as an invisible record.
- CSV/Excel export includes the same filtered rows as the screen.

**Test expectations:**

- Feature tests with paid confirmed booking before invoice generation.
- Feature tests for paid-without-payment-intent anomaly filter.
- Export tests for filtered ledger rows and columns.

## Story 5.2: Make Coach Revenue Explain Its Payment Source

**Role:** Coach

**Context:** Coach dashboard revenue currently sums confirmed booking `amount_paid`. If payment confirmation is incomplete or invoices/statements are delayed, coaches need to understand what is paid, pending, anomalous, or awaiting monthly payout processing.

**Implementation details:**

- On the coach dashboard, separate current-month paid bookings, pending payment holds, refunds, and payout statements.
- Add a small warning when any confirmed paid booking for the coach is missing `stripe_payment_intent_id`.
- Link to payout history/statements and relevant session details.
- Keep revenue calculations in integer cents and reuse existing money components.

**Acceptance criteria:**

- Coach sees paid confirmed revenue independent of invoice timing.
- Coach can distinguish pending holds from paid participants.
- Anomalous payment records are not silently counted without explanation.

**Test expectations:**

- Dashboard test with confirmed paid, pending, refunded, and missing-intent records.
- Assertion that pending payment is excluded from revenue.

## Story 5.3: Run Payment Anomaly Detection as an Operational Workflow

**Role:** Accountant, Admin

**Context:** `AnomalyDetectorService` exists and can flag financial inconsistencies, but readiness and dashboards must make anomalies part of daily operations. The production booking missing `stripe_payment_intent_id` is a concrete example that should become visible before a refund attempt fails.

**Implementation details:**

- Add a scheduled command named `payments:detect-anomalies` and schedule it hourly through Laravel's scheduler.
- Add anomaly counts to admin and accountant dashboards.
- Include anomaly links from readiness checks and refund queue rows.
- Add anomaly types for confirmed paid bookings missing Stripe payment intent and paid bookings with no invoice/statement after expected processing.
- Keep anomaly queues read-only in this milestone. Each anomaly row must include relevant links and a recommended action; do not add resolve/ignore actions.

**Acceptance criteria:**

- Accountant/admin sees payment anomalies without manually inspecting database rows.
- Missing payment intent records are flagged before refund attempts.
- Anomaly state can be exported or included in the transaction ledger.

**Test expectations:**

- Unit tests for anomaly detection rules.
- Feature tests for anomaly dashboard count and list.
- Scheduler/readiness test if anomaly detection becomes scheduled.

---

# Epic 6: Automated Smoke Coverage and Partner Demo Confidence

## Story 6.1: Add a True End-to-End Booking Payment Smoke Test

**Role:** Developer, QA

**Context:** The app has many focused tests, but the reported defect proves that the most important athlete journey can break even when pieces exist. The MVP needs one high-level test proving that booking, Stripe checkout start, webhook confirmation, and dashboard visibility all connect.

**Implementation details:**

- Add a Pest feature test under `tests/Feature/Mvp/` for the full athlete payment journey.
- Mock Stripe checkout creation to return a valid checkout session ID and URL.
- Create a pending booking, simulate `checkout.session.completed`, and assert the booking becomes confirmed with amount and payment intent.
- Assert the confirmed booking appears in athlete dashboard, coach dashboard/revenue, accountant transaction ledger, and admin refund queue as eligible.
- Add a second path for failed checkout creation to assert user-facing error and no duplicate capacity hold.

**Acceptance criteria:**

- `php artisan test --filter=Mvp` catches broken payment redirects and webhook/dashboard mismatches.
- The test does not call real Stripe.
- The test documents the expected booking status transitions.

**Test expectations:**

- This story is itself a test expectation; it should be part of CI.

## Story 6.2: Add Browser Smoke Tests for Maps and Activity Images

**Role:** Developer, QA

**Context:** Unit and feature tests did not catch that maps are invisible in production or that uploaded images return 404. These are visual/runtime integration defects involving assets, storage, JS, and data.

**Implementation details:**

- Add Playwright browser smoke tests for local and CI preview environments.
- Seed sessions with coordinates and activity images.
- Visit session discovery and assert a map canvas/container is visible and non-empty.
- Visit session detail and assert map preview plus directions button are present.
- Upload an activity image through the admin UI, then assert the rendered image URL returns HTTP 200.
- Keep these tests out of production destructive workflows; use local/CI fixtures.

**Acceptance criteria:**

- Map rendering regressions fail a smoke test.
- Missing public storage link or broken image URL fails a smoke test.
- The test can be run before partner demos.

**Test expectations:**

- Playwright/browser test for discovery map.
- Playwright/browser test for detail directions button.
- HTTP assertion for activity image public URL.

## Story 6.3: Add a Read-Only Production Health Snapshot Command

**Role:** Operator, Admin

**Context:** The OVH read-only audit surfaced the exact problems quickly, but it required manual checks. Operators need a repeatable command or script that summarizes production readiness without changing anything.

**Implementation details:**

- Add an artisan command named `mvp:health-snapshot` that performs read-only checks and outputs JSON/table format.
- Include: scheduler heartbeat status, postal-code count, sessions missing coordinates, public storage link status, activity image public-read status, booking payment anomalies, queue driver, cache driver, Stripe key shape, and app release info if available.
- Make it safe in production: no writes and no external Stripe calls.
- Expose the same summarized data to the admin readiness page.

**Acceptance criteria:**

- Operators can run one command before a demo and see red/yellow/green blockers.
- The command never prints secrets.
- The command exits non-zero when critical blockers are present, making it CI/deploy friendly.

**Test expectations:**

- Command tests for green and red fixture states.
- Secret-redaction test.

## Story 6.4: Update MVP Demo Seed Data for Realistic Payment and Map States

**Role:** Developer, QA

**Context:** `MvpJourneySeeder` creates useful demo users and sessions, but it uses placeholder Stripe values and does not load postal-code coordinates when run alone. Production must never run this seeder, but local and staging demos need data that exercises maps, directions, booking, accounting, refunds, and audit logs.

**Implementation details:**

- Ensure local demo seeding includes postal-code coordinates and sessions with lat/lng.
- Ensure seeded paid bookings have coherent fields: confirmed status, `amount_paid`, `stripe_checkout_session_id`, and `stripe_payment_intent_id` for every refundable record.
- Add at least one deliberately anomalous booking for accountant/admin anomaly testing, clearly labelled in seed comments.
- Seed activity images using committed fixture files copied to the public storage disk during seeding; the seeder must fail with a clear message if `public/storage` is not linked.
- Update `doc/MVP-Smoke-Test.md` with the exact local setup order.

**Acceptance criteria:**

- Local smoke data exercises maps, directions, paid bookings, refunds, accounting ledger, audit logs, and readiness.
- Seeded refundable bookings can be refunded with mocked/test Stripe flow. Demo-only non-refundable bookings must use non-refundable statuses and must not appear as refund-eligible.
- Demo seed remains blocked in production.

**Test expectations:**

- Seeder smoke test for coherent booking/payment fields.
- Seeder smoke test for sessions with coordinates.
- Seeder smoke test for public activity image fixture if implemented.

---

# Recommended Implementation Order

1. Fix production setup blockers: public storage link, scheduler configuration, postal-code loading, session coordinate backfill.
2. Restore booking/payment reliability: checkout error handling, transaction separation, webhook payment-intent persistence, reconciliation.
3. Repair refund/anomaly handling for the existing confirmed paid booking without a payment intent.
4. Make maps and directions visible: discovery fallback map, session detail map, directions button.
5. Add navigation/dashboard links for admin/accountant audit and operational tools.
6. Expand readiness checks so the current production failures are caught directly.
7. Add end-to-end booking/payment, map, image, and production health smoke coverage.

## Immediate Pre-Demo Checklist

- Configure Laravel scheduler on OVH and confirm all critical `scheduler_heartbeats` rows update.
- Create the public storage link for the current release and verify uploaded activity image URLs return HTTP 200.
- Load postal-code coordinate reference data in production and run session coordinate backfill.
- Reconcile or explicitly mark the confirmed paid booking missing `stripe_payment_intent_id` before testing refunds.
- Replace placeholder Stripe Connect account IDs with real Stripe test-mode Express accounts for demo coaches.
- Run the athlete booking flow with Stripe test mode and confirm athlete, coach, accountant, and admin views all show the same paid booking.
- Verify admin and accountant can reach audit logs from navigation and dashboards.
