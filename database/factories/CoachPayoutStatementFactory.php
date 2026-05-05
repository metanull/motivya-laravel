<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CoachPayoutStatementStatus;
use App\Models\CoachPayoutStatement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CoachPayoutStatement>
 */
class CoachPayoutStatementFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $revenueTtc = $this->faker->numberBetween(10000, 200000);
        $revenueHtva = intdiv($revenueTtc * 100 + 60, 121);
        $vatAmount = $revenueTtc - $revenueHtva;
        $paymentFees = (int) round($revenueTtc * 15 / 1000);
        $commissionAmount = (int) round($revenueHtva * 30 / 100);
        $coachPayout = $revenueHtva - $commissionAmount - $paymentFees;

        $year = $this->faker->numberBetween(2025, 2026);
        $month = $this->faker->numberBetween(1, 12);

        return [
            'coach_id' => User::factory()->coach(),
            'period_month' => $month,
            'period_year' => $year,
            'status' => CoachPayoutStatementStatus::Draft->value,
            'sessions_count' => $this->faker->numberBetween(1, 20),
            'paid_bookings_count' => $this->faker->numberBetween(1, 50),
            'revenue_ttc' => $revenueTtc,
            'revenue_htva' => $revenueHtva,
            'vat_amount' => $vatAmount,
            'payment_fees' => $paymentFees,
            'subscription_tier' => 'freemium',
            'commission_rate' => 30,
            'commission_amount' => $commissionAmount,
            'coach_payout' => max(0, $coachPayout),
            'is_vat_subject' => true,
            'block_reason' => null,
            'invoice_submitted_at' => null,
            'approved_at' => null,
            'paid_at' => null,
            'approved_by' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => CoachPayoutStatementStatus::Draft->value]);
    }

    public function readyForInvoice(): static
    {
        return $this->state(['status' => CoachPayoutStatementStatus::ReadyForInvoice->value]);
    }

    public function invoiceSubmitted(): static
    {
        return $this->state([
            'status' => CoachPayoutStatementStatus::InvoiceSubmitted->value,
            'invoice_submitted_at' => now(),
        ]);
    }

    public function approved(): static
    {
        return $this->state([
            'status' => CoachPayoutStatementStatus::Approved->value,
            'approved_at' => now(),
        ]);
    }

    public function paid(): static
    {
        return $this->state([
            'status' => CoachPayoutStatementStatus::Paid->value,
            'approved_at' => now(),
            'paid_at' => now(),
        ]);
    }

    public function blocked(): static
    {
        return $this->state([
            'status' => CoachPayoutStatementStatus::Blocked->value,
            'block_reason' => 'Missing invoice documentation.',
        ]);
    }

    public function forPeriod(int $year, int $month): static
    {
        return $this->state(['period_year' => $year, 'period_month' => $month]);
    }
}
