<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

/**
 * Value object representing the full payout breakdown for a coach billing period.
 *
 * All monetary amounts are integers in EUR cents.
 */
final class PayoutBreakdown
{
    public function __construct(
        /** Total revenue collected from clients (VAT-inclusive). */
        public readonly int $revenue_ttc,

        /** Revenue excluding VAT (HTVA), used as the basis for all calculations. */
        public readonly int $revenue_htva,

        /** Stripe processing fees deducted from the payout. */
        public readonly int $stripe_fee,

        /** Monthly subscription fee deducted (0 for Freemium). */
        public readonly int $subscription_fee,

        /** Commission rate applied as a percentage integer (30, 20, or 10). */
        public readonly int $commission_rate,

        /** Motivya's commission amount in cents (margin_htva). */
        public readonly int $commission_amount,

        /** Net amount owed to the coach in cents (HTVA, after all deductions). */
        public readonly int $coach_payout,

        /** Motivya's effective margin in cents (equals commission_amount). */
        public readonly int $platform_margin,

        /** The plan that was applied: 'freemium', 'active', or 'premium'. */
        public readonly string $applied_plan,
    ) {}
}
