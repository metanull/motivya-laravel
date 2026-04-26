<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SubscriptionPlan;
use App\Models\CoachSubscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CoachSubscription>
 */
class CoachSubscriptionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'coach_id' => User::factory()->coach(),
            'plan' => SubscriptionPlan::Freemium->value,
            'month' => now()->startOfMonth()->toDateString(),
            'revenue_ttc' => $this->faker->numberBetween(0, 100000),
            'applied_plan' => SubscriptionPlan::Freemium->value,
            'subscription_fee' => 0,
            'commission_rate' => 30,
        ];
    }

    public function freemium(): static
    {
        return $this->state([
            'plan' => SubscriptionPlan::Freemium->value,
            'applied_plan' => SubscriptionPlan::Freemium->value,
            'subscription_fee' => 0,
            'commission_rate' => 30,
        ]);
    }

    public function active(): static
    {
        return $this->state([
            'plan' => SubscriptionPlan::Active->value,
            'applied_plan' => SubscriptionPlan::Active->value,
            'subscription_fee' => 3900,
            'commission_rate' => 20,
        ]);
    }

    public function premium(): static
    {
        return $this->state([
            'plan' => SubscriptionPlan::Premium->value,
            'applied_plan' => SubscriptionPlan::Premium->value,
            'subscription_fee' => 7900,
            'commission_rate' => 10,
        ]);
    }
}
