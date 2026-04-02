---
description: "Use when implementing VAT determination, tax rate selection, coach payout computation, margin preservation for non-subject coaches, or testing VAT/tax calculation logic. Covers Belgian VAT rules, franchise regime handling, and the HTVA-based payout formula."
applyTo: "**/*Vat*,**/*Tax*"
---
# Belgian VAT Calculation Rules

## VAT Determination Logic

Every tax calculation MUST branch on the coach's `is_vat_subject` boolean (stored on the coach profile). This is the single source of truth — never infer VAT status from other fields.

```php
if ($coach->is_vat_subject) {
    // Standard rate 21% — coach invoices WITH VAT
    // Motivya recovers coach's VAT → effective cost = payout HTVA
} else {
    // Franchise regime — coach invoices WITHOUT VAT (tax category E)
    // Motivya cannot recover VAT → payout must preserve margin HTVA
}
```

## Belgian VAT Rates

| Rate | Tax Category | When |
|------|-------------|------|
| 21% | `S` (Standard) | All client-facing session prices; VAT-subject coach invoices |
| 0% | `E` (Exempt) | Non-subject coach invoices (franchise regime, art. 56bis CTVA) |

There is no reduced rate for sports coaching in Belgium. Always use 21%.

## Core Payout Formula (HTVA-Based)

All calculations operate on **HTVA (hors TVA)** amounts to ensure Motivya's margin is identical regardless of coach VAT status.

```
Step 1: revenue_ttc  = sum of client payments (cents, VAT-inclusive)
Step 2: revenue_htva = revenue_ttc * 100 / 121   (round half-up to nearest cent)
Step 3: margin_htva  = revenue_htva * commission_rate / 100  (round half-up)
Step 4: payout_htva  = revenue_htva - margin_htva
```

### What the coach receives

| VAT Status | Coach Receives | Coach Invoices |
|------------|---------------|----------------|
| Subject (21%) | `payout_htva + (payout_htva * 21 / 100)` TTC | With 21% VAT |
| Non-subject | `payout_htva` | Without VAT |

### Deductions (applied before payout)

Subtract in order:
1. **Stripe processing fees** — 1.5% + €0.25 per Bancontact transaction (store as cents)
2. **Monthly subscription fee** — Active: 3900 cents, Premium: 7900 cents (0 for Freemium)

```
net_payout_htva = payout_htva - stripe_fees - subscription_fee
```

## Commission Tiers & Auto-Best-Plan

| Plan | Subscription (TTC) | Commission |
|------|-------------------|------------|
| Freemium | 0 | 30% |
| Active | 39 € (3900 cents) | 20% |
| Premium | 79 € (7900 cents) | 10% |

The system MUST compare all three plans for each billing period and apply whichever yields the **highest net payout** for the coach. Never blindly apply the coach's current plan — always run the comparison.

```php
// Pseudo-logic for auto-best-plan
$bestPayout = 0;
$bestPlan = null;
foreach (['freemium', 'active', 'premium'] as $plan) {
    $payout = calculatePayout($revenueHtva, $plan);
    if ($payout > $bestPayout) {
        $bestPayout = $payout;
        $bestPlan = $plan;
    }
}
```

## Rounding Rules

- Use **integer arithmetic in cents** throughout — no floats for money
- HTVA conversion: `intdiv($ttc * 100 + 60, 121)` for half-up rounding (or use `(int) round($ttc * 100 / 121)`)
- Always round commission and VAT amounts **half-up** to the nearest cent
- Display layer converts cents to EUR with 2 decimal places

## Worked Examples (for tests)

### Example 1: VAT-subject coach, Freemium

10 clients × 1000 cents = 10000 cents TTC
```
revenue_htva = round(10000 * 100 / 121) = 8264 cents
margin_htva  = round(8264 * 30 / 100)   = 2479 cents
payout_htva  = 8264 - 2479              = 5785 cents
coach_invoice = 5785 + round(5785 * 21 / 100) = 5785 + 1215 = 7000 cents TTC
motivya_keeps = 10000 - 7000 = 3000 cents TTC
  minus VAT owed on 10000: round(10000 * 21 / 121) = 1736 cents
  plus VAT recovered from coach: 1215 cents
  net to motivya = 3000 - 1736 + 1215 = 2479 cents
```

### Example 2: Non-subject coach, Freemium

10 clients × 1000 cents = 10000 cents TTC
```
revenue_htva = 8264 cents
margin_htva  = 2479 cents
payout_htva  = 5785 cents
coach_invoice = 5785 cents (no VAT)
motivya_keeps = 10000 - 5785 = 4215 cents TTC
  minus VAT owed on 10000: 1736 cents
  plus VAT recovered: 0 cents
  net to motivya = 4215 - 1736 = 2479 cents  ← same margin
```

### Example 3: Auto-best-plan comparison (Active coach, February)

12 sessions, 48 payments × 1300 cents = 62400 cents TTC
```
Freemium: htva=51570, margin=15471, payout=36099, fees=936 → net=35163
Active:   htva=51570, margin=10314, payout=41256, fees=936, sub=3900 → net=36420
Premium:  htva=51570, margin=5157,  payout=46413, fees=936, sub=7900 → net=37577
→ Best plan: Premium (net 37577 cents)
```

## Testing Requirements

- Write Pest unit tests for every formula branch (subject/non-subject × each tier)
- Test rounding edge cases: amounts that produce fractional cents before rounding
- Test auto-best-plan with boundary amounts where plans cross over
- Assert that Motivya's net margin is identical for subject vs non-subject coaches at the same revenue
- Use data providers for parameterised test coverage across tiers

## Code Placement

- VAT rate determination → `app/Services/*Vat*`
- Payout computation → `app/Services/*Payout*`
- Never compute VAT in controllers, Livewire components, or Blade templates
- The `is_vat_subject` flag must come from the Coach model, never from request input
