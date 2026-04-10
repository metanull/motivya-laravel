<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ActivityType;
use App\Models\ActivityImage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityImage>
 */
class ActivityImageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'activity_type' => $this->faker->randomElement(ActivityType::cases())->value,
            'path' => 'activity-images/'.$this->faker->uuid().'.jpg',
            'alt_text' => $this->faker->sentence(3),
            'uploaded_by' => User::factory()->admin(),
        ];
    }

    public function forActivity(ActivityType $type): static
    {
        return $this->state(['activity_type' => $type->value]);
    }
}
