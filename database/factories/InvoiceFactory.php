<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $revenueTtc = $this->faker->numberBetween(10000, 100000);
        $revenueHtva = intdiv($revenueTtc * 100 + 60, 121);
        $commissionAmount = (int) round($revenueHtva * 30 / 100);
        $coachPayout = $revenueHtva - $commissionAmount;
        $vatAmount = (int) round($revenueHtva * 21 / 100);

        $start = $this->faker->dateTimeBetween('-3 months', '-1 month');
        $end = (clone $start)->modify('last day of this month');

        return [
            'type'                 => InvoiceType::Invoice->value,
            'coach_id'             => User::factory()->coach(),
            'sport_session_id'     => null,
            'billing_period_start' => $start->format('Y-m-d'),
            'billing_period_end'   => $end->format('Y-m-d'),
            'revenue_ttc'          => $revenueTtc,
            'revenue_htva'         => $revenueHtva,
            'vat_amount'           => $vatAmount,
            'stripe_fee'           => (int) round($revenueTtc * 15 / 1000),
            'subscription_fee'     => 0,
            'commission_amount'    => $commissionAmount,
            'coach_payout'         => $coachPayout,
            'platform_margin'      => $commissionAmount,
            'plan_applied'         => 'freemium',
            'tax_category_code'    => 'S',
            'xml_path'             => null,
            'issued_at'            => null,
            'status'               => InvoiceStatus::Draft->value,
            'related_invoice_id'   => null,
        ];
    }

    public function invoice(): static
    {
        return $this->state(['type' => InvoiceType::Invoice->value]);
    }

    public function creditNote(): static
    {
        return $this->state(['type' => InvoiceType::CreditNote->value]);
    }

    public function draft(): static
    {
        return $this->state(['status' => InvoiceStatus::Draft->value]);
    }

    public function issued(): static
    {
        return $this->state([
            'status'    => InvoiceStatus::Issued->value,
            'issued_at' => now(),
        ]);
    }

    public function sent(): static
    {
        return $this->state([
            'status'    => InvoiceStatus::Sent->value,
            'issued_at' => now(),
        ]);
    }

    public function paid(): static
    {
        return $this->state([
            'status'    => InvoiceStatus::Paid->value,
            'issued_at' => now(),
        ]);
    }

    public function vatSubject(): static
    {
        return $this->state(['tax_category_code' => 'S']);
    }

    public function nonVatSubject(): static
    {
        return $this->state([
            'tax_category_code' => 'E',
            'vat_amount'        => 0,
        ]);
    }
}
