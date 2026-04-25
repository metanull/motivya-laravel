<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\InvoiceType;
use App\Models\CoachSubscription;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class SubscriptionService
{
    public function __construct(
        private readonly PayoutService $payoutService,
    ) {}

    /**
     * Compute and store the best subscription plan for a coach for a given billing month.
     *
     * Sums all session invoice revenue for the coach in the given month, runs the
     * auto-best-plan algorithm, and persists the result in `coach_subscriptions`.
     * If the applied plan is Active or Premium and the coach has completed Stripe
     * onboarding, the subscription fee is charged via Cashier.
     *
     * @param  User  $coach  A coach user with an associated CoachProfile.
     * @param  Carbon  $month  Any date within the target billing month.
     * @return CoachSubscription The created or updated subscription record.
     */
    public function computeForMonth(User $coach, Carbon $month): CoachSubscription
    {
        return DB::transaction(function () use ($coach, $month): CoachSubscription {
            $firstOfMonth = $month->copy()->startOfMonth()->toDateString();

            // Sum revenue_ttc from all session invoices for this coach in this month.
            $revenueTtc = (int) Invoice::query()
                ->where('coach_id', $coach->id)
                ->where('type', InvoiceType::Invoice->value)
                ->whereYear('billing_period_start', $month->year)
                ->whereMonth('billing_period_start', $month->month)
                ->sum('revenue_ttc');

            // Estimate Stripe processing fee at the standard 1.5% rate.
            $stripeFeeCents = (int) round($revenueTtc * 15 / 1000);

            // Compute payout breakdown using the auto-best-plan algorithm.
            $breakdown = $this->payoutService->calculatePayout(
                $coach->coachProfile,
                $revenueTtc,
                $stripeFeeCents,
            );

            $subscription = CoachSubscription::updateOrCreate(
                ['coach_id' => $coach->id, 'month' => $firstOfMonth],
                [
                    'plan' => $breakdown->applied_plan,
                    'revenue_ttc' => $breakdown->revenue_ttc,
                    'applied_plan' => $breakdown->applied_plan,
                    'subscription_fee' => $breakdown->subscription_fee,
                    'commission_rate' => $breakdown->commission_rate,
                ],
            );

            // Charge subscription fee via Stripe if applicable.
            if ($breakdown->subscription_fee > 0 && $coach->coachProfile?->stripe_onboarding_complete) {
                $this->chargeSubscriptionFee($coach, $subscription);
            }

            return $subscription;
        });
    }

    /**
     * Charge the monthly subscription fee to the coach via Stripe Cashier.
     *
     * @param  User  $coach  The coach whose card will be charged.
     * @param  CoachSubscription  $subscription  The subscription record for this billing cycle.
     */
    public function chargeSubscriptionFee(User $coach, CoachSubscription $subscription): void
    {
        $coach->charge($subscription->subscription_fee, [
            'description' => sprintf(
                'Motivya subscription — %s — %s',
                $subscription->applied_plan->value,
                $subscription->month->format('Y-m'),
            ),
            'metadata' => [
                'coach_id' => $coach->id,
                'subscription_id' => $subscription->id,
                'month' => $subscription->month->format('Y-m'),
                'plan' => $subscription->applied_plan->value,
            ],
        ]);
    }
}
