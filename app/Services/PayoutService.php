<?php

declare(strict_types=1);

namespace App\Services;

use App\DataTransferObjects\PayoutBreakdown;
use App\Models\CoachProfile;

final class PayoutService
{
    /**
     * Plan definitions: name → [commission_rate (%), subscription_fee (cents TTC)].
     *
     * @var array<string, array{commission_rate: int, subscription_fee: int}>
     */
    private const array PLANS = [
        'freemium' => ['commission_rate' => 30, 'subscription_fee' => 0],
        'active' => ['commission_rate' => 20, 'subscription_fee' => 3900],
        'premium' => ['commission_rate' => 10, 'subscription_fee' => 7900],
    ];

    public function __construct(
        private readonly VatService $vatService,
    ) {}

    /**
     * Calculates the coach payout using the auto-best-plan algorithm.
     *
     * Computes the payout for all three plans (Freemium, Active, Premium) and
     * returns the breakdown for the plan that yields the highest net payout.
     * All amounts operate on HTVA (tax-exclusive) values to ensure Motivya's
     * margin is identical regardless of the coach's VAT status.
     *
     * @param  CoachProfile  $coach  The coach whose payout is being calculated.
     * @param  int  $revenueTtc  Total client revenue in cents (VAT-inclusive).
     * @param  int  $stripeFeeCents  Stripe processing fees in cents.
     */
    public function calculatePayout(
        CoachProfile $coach,
        int $revenueTtc,
        int $stripeFeeCents,
    ): PayoutBreakdown {
        $revenueHtva = $this->vatService->toHtva($revenueTtc);

        $bestNetPayout = PHP_INT_MIN;
        $bestPlan = null;
        $bestBreakdown = null;

        foreach (self::PLANS as $planName => $plan) {
            $commissionAmount = (int) round($revenueHtva * $plan['commission_rate'] / 100);
            $payoutHtva = $revenueHtva - $commissionAmount;
            $netPayout = $payoutHtva - $stripeFeeCents - $plan['subscription_fee'];

            if ($netPayout > $bestNetPayout) {
                $bestNetPayout = $netPayout;
                $bestPlan = $planName;
                $bestBreakdown = [
                    'commission_rate' => $plan['commission_rate'],
                    'subscription_fee' => $plan['subscription_fee'],
                    'commission_amount' => $commissionAmount,
                    'coach_payout' => $netPayout,
                ];
            }
        }

        /** @var string $bestPlan */
        /** @var array{commission_rate: int, subscription_fee: int, commission_amount: int, coach_payout: int} $bestBreakdown */
        return new PayoutBreakdown(
            revenue_ttc: $revenueTtc,
            revenue_htva: $revenueHtva,
            stripe_fee: $stripeFeeCents,
            subscription_fee: $bestBreakdown['subscription_fee'],
            commission_rate: $bestBreakdown['commission_rate'],
            commission_amount: $bestBreakdown['commission_amount'],
            coach_payout: $bestBreakdown['coach_payout'],
            platform_margin: $bestBreakdown['commission_amount'],
            applied_plan: $bestPlan,
        );
    }
}
