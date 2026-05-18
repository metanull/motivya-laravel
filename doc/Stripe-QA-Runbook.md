# Stripe QA Runbook

Date: 2026-05-17

## Purpose

This runbook describes the manual Stripe integration suite under `tests/Manual/StripeIntegration`. The suite validates Motivya's Stripe test-mode booking, payment failure, refund, and coach billing flows. It is intentionally excluded from CI and from the default `php artisan test` command.

## Required Environment

Use Stripe test-mode credentials only.

```env
MOTIVYA_STRIPE_LIVE_TESTS=1
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
MOTIVYA_QA_BASE_URL=http://127.0.0.1:8000
MOTIVYA_STRIPE_CONNECTED_ACCOUNT_ID=acct_...
```

`MOTIVYA_STRIPE_CONNECTED_ACCOUNT_ID` must reference a Stripe test-mode connected account that can receive destination-charge transfer data.

## UAT Environment Setup

Generate a dedicated UAT env file without overwriting the active `.env`:

```bash
cd /opt/motivya/current
php artisan env:make-uat --path=/opt/motivya/shared/.env.uat --from=/opt/motivya/shared/.env
```

The command writes UAT-safe values including:

```env
APP_ENV=uat
APP_DEBUG=false
MOTIVYA_DEPLOY_PROFILE=uat
MOTIVYA_STRIPE_LIVE_TESTS=1
MOTIVYA_STRIPE_CONNECTED_ACCOUNT_ID=acct_...
```

It discovers the connected account from approved, onboarded coach profiles. To inspect accounts yourself:

```bash
php artisan stripe:connect-accounts --json
php artisan stripe:connect-accounts --usable-only --account-id-only
```

Activate UAT by switching the shared `.env` symlink:

```bash
php artisan env:activate uat --shared-path=/opt/motivya/shared
php artisan optimize:clear
php artisan config:cache
```

Switch back to production later with:

```bash
php artisan env:activate production --shared-path=/opt/motivya/shared
php artisan optimize:clear
php artisan config:cache
```

## OVH UAT Command

OVH is currently treated as the UAT environment. Run the suite directly on the host with bare PHP. Stripe credentials and app URL come from the active `.env.uat`, but the suite forces an isolated SQLite in-memory test database and runs migrations before each test. It must not use or reset the configured UAT MySQL database.

With `.env.uat` active, the command is intentionally short:

```bash
cd /opt/motivya/current
php artisan test -c phpunit.manual-stripe.xml
```

This creates temporary Laravel test records in SQLite and real Stripe test-mode objects in Stripe. Stripe test-mode objects that cannot be deleted remain visible in the Stripe dashboard.

The suite verifies:

- Checkout initiation with real Stripe Checkout Session creation.
- Successful card payment using a real Stripe test-mode PaymentIntent.
- Webhook receipt and booking/session reconciliation for successful payment.
- Failed card payment and idempotent payment-failure webhook handling.
- Exceptional refund through Stripe test-mode refund flow and credit note generation.
- Coach billing calculation, subscription fee charge, invoice XML generation, and payout statement issuing.

## UAT Scenario Data

Generate a realistic UAT dataset on demand with:

```bash
cd /opt/motivya/current
php artisan uat:play-scenario --payments=simulated --fresh --force
```

The scenario creates 5 coaches, 15 athletes, 5 sessions per coach, bookings across a rolling -30/+30 day window, successful payments, failed payments, below-threshold refunded sessions, at least two exceptional admin refunds, completed sessions, invoices, payout statements, payment anomalies, and captured UAT mail.

To create real Stripe test-mode PaymentIntents and refunds instead of simulated payment identifiers:

```bash
php artisan uat:play-scenario --payments=stripe --fresh --force --confirm-stripe
```

Stripe mode requires test-mode Stripe configuration. Both modes force mail delivery to Laravel's `array` mailer during the run and capture generated mails in the database instead of sending them externally.

Review captured UAT mail with:

```bash
php artisan uat:mail:list --limit=25
php artisan uat:mail:list --run-id=uat_YYYYMMDD_HHMMSS
php artisan uat:mail:show {id}
php artisan uat:mail:clear --run-id=uat_YYYYMMDD_HHMMSS --force
```

## Webhook Notes

The suite posts signed Stripe-like webhook events directly to `/stripe/webhook` using `STRIPE_WEBHOOK_SECRET`. When manually completing hosted Checkout flows outside the suite, Stripe CLI forwarding remains useful:

```bash
stripe listen --forward-to http://127.0.0.1:8000/stripe/webhook
```

## Data Isolation

The manual suite uses Laravel migrations and factories against SQLite `:memory:`. Each test starts from a fresh schema, creates the users/profiles/sessions/bookings it needs, and uses a unique `qa_run_id` in user emails, session titles, and Stripe metadata where the called Stripe API supports metadata. Stripe test-mode objects that cannot be deleted remain discoverable in the Stripe Dashboard by `qa_run_id` metadata or generated object timestamps.

The existing full demo data feeder is `Database\Seeders\MvpJourneySeeder`. It is called by `DevSeeder` in local/testing and creates one user per role plus a full MVP smoke journey. It is intentionally skipped in production.

Useful UAT account operations after a database restore or reset:

```bash
php artisan users:list --json
php artisan users:create --email=admin@example.test --name="Admin User" --role=admin --password='change-me-now'
php artisan users:change-role admin@example.test admin
```

## Non-CI Rule

Do not add `tests/Manual/StripeIntegration` to `phpunit.xml`, GitHub Actions, or any required CI command. This suite is an explicit operator-run QA suite for Stripe test-mode validation.