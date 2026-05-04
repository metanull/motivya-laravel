<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AuditEvent;
use App\Models\AuditEventSubject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditEventSubject>
 */
class AuditEventSubjectFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'audit_event_id' => AuditEvent::factory(),
            'subject_type' => 'App\\Models\\User',
            'subject_id' => $this->faker->numberBetween(1, 9999),
            'relation' => 'primary',
        ];
    }

    public function primary(): static
    {
        return $this->state(['relation' => 'primary']);
    }

    public function related(string $relation): static
    {
        return $this->state(['relation' => $relation]);
    }
}
