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

OVH is currently treated as the UAT environment. Run the suite directly on the host with bare PHP, using the deployed app configuration and the UAT MySQL database. Do not use Docker and do not force SQLite.

With `.env.uat` active, the command is intentionally short:

```bash
cd /opt/motivya/current
php artisan test -c phpunit.manual-stripe.xml
```

This writes provisional QA records to the UAT database and creates Stripe test-mode objects. That is expected for UAT.

The suite verifies:

- Checkout initiation with real Stripe Checkout Session creation.
- Successful card payment using a real Stripe test-mode PaymentIntent.
- Webhook receipt and booking/session reconciliation for successful payment.
- Failed card payment and idempotent payment-failure webhook handling.
- Exceptional refund through Stripe test-mode refund flow and credit note generation.
- Coach billing calculation, subscription fee charge, invoice XML generation, and payout statement issuing.

## Webhook Notes

The suite posts signed Stripe-like webhook events directly to `/stripe/webhook` using `STRIPE_WEBHOOK_SECRET`. When manually completing hosted Checkout flows outside the suite, Stripe CLI forwarding remains useful:

```bash
stripe listen --forward-to http://127.0.0.1:8000/stripe/webhook
```

## Data Isolation

Each test creates a unique `qa_run_id` and uses it in user emails, session titles, and Stripe metadata where the called Stripe API supports metadata. Stripe test-mode objects that cannot be deleted remain discoverable in the Stripe Dashboard by `qa_run_id` metadata or generated object timestamps.

## Non-CI Rule

Do not add `tests/Manual/StripeIntegration` to `phpunit.xml`, GitHub Actions, or any required CI command. This suite is an explicit operator-run QA suite for Stripe test-mode validation.