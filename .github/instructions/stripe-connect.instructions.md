---
description: "Use when implementing Stripe Connect onboarding, payment intents, Bancontact payment methods, coach payouts via Stripe, webhook signature verification, fee calculation, or Stripe-related service classes. Covers Express account setup, payment capture flow, and Belgian payment requirements."
applyTo: "app/Services/*Stripe*,app/Services/*Payment*,app/Http/Controllers/*Webhook*,app/Http/Controllers/*Stripe*"
---
# Stripe Connect & Payment Rules

## Architecture Overview

Motivya uses **Stripe Connect** with **Express accounts** for coaches. The platform collects payments from athletes, takes its commission, and pays out coaches via Stripe.

- Payment collection: Stripe handles athlete payments (destination charges)
- Coach onboarding: Express accounts via Stripe Connect OAuth
- Payouts: automated via Stripe to the coach's connected bank account
- Payment method: **Bancontact** is mandatory (Belgium); card payments optional
- All integration through **Laravel Cashier** where applicable

## Payment Flow

```
1. Athlete books session → PaymentIntent created (amount in cents, EUR)
2. PaymentIntent uses destination charge: funds go to Motivya's platform account
3. Session threshold met → session confirmed → athletes charged (or auth captured)
4. Session completed → payout calculated (see peppol-invoicing / vat-calculations instructions)
5. Transfer to coach's Express account → Stripe handles bank payout
```

### Key Rules

- **Never capture payment before session is confirmed** — use `capture_method: 'manual'` for holds, or charge only on confirmation
- All PaymentIntents MUST specify `payment_method_types: ['bancontact']` at minimum
- Always set `currency: 'eur'`
- All amounts are **integers in cents** — never use floats
- Use `metadata` on PaymentIntents to store: `session_id`, `athlete_id`, `coach_id`
- Wrap booking + payment operations in a **DB transaction** to prevent overbooking

## Stripe Connect Onboarding (Coach)

### Express Account Setup

```php
// Create Express account for coach
$account = \Stripe\Account::create([
    'type'         => 'express',
    'country'      => 'BE',
    'email'        => $coach->email,
    'capabilities' => [
        'transfers' => ['requested' => true],
        'bancontact_payments' => ['requested' => true],
    ],
    'business_type'   => 'individual', // or 'company' based on coach profile
    'business_profile' => [
        'mcc'  => '7941', // Sports clubs, fields, and promoters
        'url'  => config('app.url') . '/coaches/' . $coach->slug,
    ],
    'metadata' => [
        'coach_id'          => $coach->id,
        'enterprise_number' => $coach->enterprise_number,
    ],
]);
```

### Required Coach Fields

| Field | Storage | Purpose |
|-------|---------|---------|
| `stripe_account_id` | `coaches` table | Express account reference (`acct_...`) |
| `stripe_onboarding_complete` | `coaches` table | Boolean — can receive payouts |
| `enterprise_number` | `coaches` table | Belgian BCE/KBO number (required for PEPPOL) |
| `is_vat_subject` | `coaches` table | Drives payout formula (see `vat-calculations` instruction) |

### Onboarding Flow

1. Coach submits application → admin approves → `stripe_account_id` created
2. Generate Account Link (`\Stripe\AccountLink::create`) → redirect coach to Stripe-hosted onboarding
3. On return, verify `details_submitted` and `charges_enabled` via `account.updated` webhook
4. Set `stripe_onboarding_complete = true` only when both are `true`
5. Coach cannot receive payouts until onboarding is complete

## Bancontact Support

Bancontact is the dominant payment method in Belgium and **must** be supported.

### PaymentIntent Creation

```php
$paymentIntent = \Stripe\PaymentIntent::create([
    'amount'               => $amountInCents,
    'currency'             => 'eur',
    'payment_method_types' => ['bancontact', 'card'],
    'capture_method'       => 'automatic', // Bancontact does not support manual capture
    'metadata'             => [
        'session_id'  => $session->id,
        'athlete_id'  => $athlete->id,
        'coach_id'    => $session->coach_id,
    ],
    'transfer_data' => [
        'destination' => $coach->stripe_account_id,
        'amount'      => $coachPayoutInCents,
    ],
]);
```

### Bancontact Constraints

- Bancontact **does not support** `capture_method: 'manual'` — charges are always immediate
- Bancontact **does not support** recurring/subscription billing natively — use SEPA Direct Debit for subscriptions if needed
- Bancontact requires a `return_url` for the 3D-Secure-like redirect flow
- Refunds on Bancontact go back to the customer's bank account (not instant)

## Fee Calculation

### Stripe Processing Fees (Bancontact)

```
stripe_fee = (transaction_amount * 0.015) + 25   // 1.5% + €0.25, all in cents
```

- Round **half-up** to the nearest cent
- Store the fee amount alongside the payment record for audit
- For card payments, use Stripe's reported `balance_transaction.fee` instead of computing manually

### Platform Fee Structure

| Plan | Subscription (cents TTC) | Commission Rate |
|------|--------------------------|-----------------|
| Freemium | 0 | 30% |
| Active | 3900 | 20% |
| Premium | 7900 | 10% |

The detailed payout formula (auto-best-plan, VAT adjustment) is defined in the `vat-calculations` and `peppol-invoicing` instructions. Do not duplicate that logic — reference those service classes.

### Deductions Order

When computing net coach payout, apply deductions in this order:
1. Stripe processing fees
2. Monthly subscription fee (if Active or Premium)
3. Platform commission on HTVA revenue

## Webhook Handling

### Signature Verification

- **Always** verify Stripe webhook signatures — never process unverified payloads
- Use Laravel Cashier's `WebhookController` which handles signature verification automatically
- Store the webhook signing secret in `config('services.stripe.webhook.secret')`, sourced from `STRIPE_WEBHOOK_SECRET` env var
- **Never** access `env()` directly — always use `config()` helper

### Critical Webhooks to Handle

| Event | Action |
|-------|--------|
| `checkout.session.completed` | Confirm booking, update session participant count |
| `payment_intent.succeeded` | Mark payment as completed, trigger payout calculation |
| `payment_intent.payment_failed` | Release booking slot, notify athlete |
| `charge.refunded` | Generate credit note, update payout ledger |
| `account.updated` | Update coach onboarding status (`stripe_onboarding_complete`) |
| `transfer.created` | Record payout transfer to coach |
| `invoice.payment_succeeded` | Confirm coach subscription payment (Active/Premium) |

### Idempotency

- Store `stripe_event_id` in a `processed_webhooks` table
- Skip processing if the event ID has already been recorded
- Wrap state changes in a DB transaction with the idempotency check

### Error Handling

- Return HTTP `200` to Stripe even on processing errors (to prevent retries flooding)
- Log the error with full payload context for manual investigation
- For transient failures (DB unavailable), return HTTP `500` to trigger Stripe retry
- Never swallow exceptions — log with `Log::error()` including the event type and ID

## Service Class Conventions

- Place Stripe integration logic in `app/Services/StripeService.php` or `app/Services/Stripe/` namespace
- Payment orchestration (booking + payment + capacity check) in `app/Services/PaymentService.php`
- Keep webhook controller thin — delegate to service classes immediately
- Use Laravel's `config('services.stripe.*')` for all Stripe configuration
- **Never** hardcode API keys, webhook secrets, or Stripe URLs
- Use Stripe's official PHP SDK (`stripe/stripe-php`) — never make raw HTTP calls
- Mock Stripe API calls in tests using Cashier's test helpers or `Stripe\ApiRequestor` stubs

## Testing

- Use Pest for all Stripe-related tests
- Test webhook handlers with fake payloads (construct valid Stripe event structures)
- Assert idempotency: sending the same event ID twice produces no duplicate side effects
- Test Bancontact-specific flows: redirect URL handling, refund behavior
- Test onboarding state transitions: incomplete → pending → complete
- Test fee calculation at boundary values (1 cent transaction, very large transactions)
- Use `Stripe\Stripe::setApiKey('sk_test_...')` only via config, never inline
