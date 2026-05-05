<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaymentAnomalyType;
use App\Models\PaymentAnomaly;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentAnomaly>
 */
class PaymentAnomalyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'anomaly_type' => $this->faker->randomElement(PaymentAnomalyType::cases())->value,
            'anomalous_model_type' => null,
            'anomalous_model_id' => null,
            'related_invoice_id' => null,
            'related_booking_id' => null,
            'related_session_id' => null,
            'related_coach_id' => null,
            'related_statement_id' => null,
            'resolution_status' => 'open',
            'resolution_reason' => null,
            'resolved_by' => null,
            'resolved_at' => null,
            'description' => $this->faker->sentence(),
            'recommended_action' => $this->faker->sentence(),
        ];
    }

    public function open(): static
    {
        return $this->state([
            'resolution_status' => 'open',
            'resolution_reason' => null,
            'resolved_by' => null,
            'resolved_at' => null,
        ]);
    }

    public function resolved(): static
    {
        return $this->state([
            'resolution_status' => 'resolved',
            'resolution_reason' => 'Manually verified and corrected.',
            'resolved_by' => User::factory()->accountant(),
            'resolved_at' => now(),
        ]);
    }

    public function ignored(): static
    {
        return $this->state([
            'resolution_status' => 'ignored',
            'resolution_reason' => 'Known edge case — no action needed.',
            'resolved_by' => User::factory()->accountant(),
            'resolved_at' => now(),
        ]);
    }

    public function ofType(PaymentAnomalyType $type): static
    {
        return $this->state(['anomaly_type' => $type->value]);
    }
}
