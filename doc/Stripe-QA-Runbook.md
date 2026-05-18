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

## Local Windows Command

Run from the repository root. The local host does not need PHP 8.3 installed; the command runs through Docker.

```powershell
$env:MOTIVYA_STRIPE_LIVE_TESTS = "1"
$env:STRIPE_KEY = "pk_test_..."
$env:STRIPE_SECRET = "sk_test_..."
$env:STRIPE_WEBHOOK_SECRET = "whsec_..."
$env:MOTIVYA_QA_BASE_URL = "http://127.0.0.1:8000"
$env:MOTIVYA_STRIPE_CONNECTED_ACCOUNT_ID = "acct_..."
.\scripts\qa-stripe.ps1
```

## OVH UAT Command

OVH is currently treated as the UAT environment. Run the suite directly on the host with bare PHP, using the deployed app configuration and the UAT MySQL database. Do not use Docker and do not force SQLite.

The connected account can be derived from any Stripe-ready coach profile already present in the UAT database:

```bash
cd /opt/motivya/current
CONNECTED_ACCOUNT=$(php -r 'require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class); $kernel->bootstrap(); echo App\Models\CoachProfile::whereNotNull("stripe_account_id")->where("stripe_account_id", "!=", "")->where("stripe_onboarding_complete", true)->value("stripe_account_id");')
MOTIVYA_STRIPE_LIVE_TESTS=1 \
MOTIVYA_STRIPE_CONNECTED_ACCOUNT_ID="$CONNECTED_ACCOUNT" \
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