<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AuditActorType;
use App\Enums\AuditEventType;
use App\Enums\AuditOperation;
use App\Enums\AuditSource;
use App\Models\AuditEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditEvent>
 */
class AuditEventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'occurred_at' => now(),
            'event_type' => $this->faker->randomElement(AuditEventType::cases())->value,
            'operation' => $this->faker->randomElement(AuditOperation::cases())->value,
            'actor_type' => AuditActorType::User->value,
            'actor_id' => $this->faker->optional()->numberBetween(1, 9999),
            'actor_role' => null,
            'source' => AuditSource::Web->value,
            'request_id' => $this->faker->uuid(),
            'ip_address' => $this->faker->optional()->ipv4(),
            'user_agent' => $this->faker->optional()->userAgent(),
            'route_name' => null,
            'job_uuid' => null,
            'model_type' => null,
            'model_id' => null,
            'old_values' => null,
            'new_values' => null,
            'metadata' => null,
        ];
    }

    public function forUser(int $userId, string $role = 'athlete'): static
    {
        return $this->state([
            'actor_type' => AuditActorType::User->value,
            'actor_id' => $userId,
            'actor_role' => $role,
        ]);
    }

    public function system(): static
    {
        return $this->state([
            'actor_type' => AuditActorType::System->value,
            'actor_id' => null,
            'actor_role' => null,
            'source' => AuditSource::Console->value,
        ]);
    }

    public function stripe(): static
    {
        return $this->state([
            'actor_type' => AuditActorType::Stripe->value,
            'actor_id' => null,
            'actor_role' => null,
            'source' => AuditSource::Webhook->value,
        ]);
    }
}
