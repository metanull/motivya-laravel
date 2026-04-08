<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ActivityType;
use App\Enums\SessionLevel;
use App\Enums\SessionStatus;
use App\Models\SportSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SportSession>
 */
class SportSessionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = $this->faker->dateTimeBetween('+1 day', '+30 days');
        $startHour = $this->faker->numberBetween(8, 18);

        return [
            'coach_id' => User::factory()->coach(),
            'activity_type' => $this->faker->randomElement(ActivityType::cases())->value,
            'level' => $this->faker->randomElement(SessionLevel::cases())->value,
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'location' => $this->faker->address(),
            'postal_code' => (string) $this->faker->numberBetween(1000, 9999),
            'date' => $date->format('Y-m-d'),
            'start_time' => sprintf('%02d:00:00', $startHour),
            'end_time' => sprintf('%02d:00:00', $startHour + 1),
            'price_per_person' => $this->faker->numberBetween(500, 5000),
            'min_participants' => 3,
            'max_participants' => 15,
            'current_participants' => 0,
            'status' => SessionStatus::Draft->value,
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => SessionStatus::Draft->value]);
    }

    public function published(): static
    {
        return $this->state(['status' => SessionStatus::Published->value]);
    }

    public function confirmed(): static
    {
        return $this->state(['status' => SessionStatus::Confirmed->value]);
    }

    public function completed(): static
    {
        return $this->state(['status' => SessionStatus::Completed->value]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => SessionStatus::Cancelled->value]);
    }

    public function withCoordinates(): static
    {
        return $this->state([
            'latitude' => $this->faker->latitude(50.7, 50.9),
            'longitude' => $this->faker->longitude(4.2, 4.5),
        ]);
    }
}
