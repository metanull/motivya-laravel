<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CoachProfileStatus;
use App\Models\CoachProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CoachProfile>
 */
class CoachProfileFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->coach(),
            'status' => CoachProfileStatus::Pending,
            'specialties' => [$this->faker->randomElement(['fitness', 'yoga', 'running', 'cycling', 'swimming'])],
            'bio' => $this->faker->paragraph(),
            'experience_level' => $this->faker->randomElement(['beginner', 'intermediate', 'advanced', 'expert']),
            'postal_code' => (string) $this->faker->numberBetween(1000, 9999),
            'country' => 'BE',
            'enterprise_number' => sprintf('%04d.%03d.%03d', $this->faker->numberBetween(0, 9999), $this->faker->numberBetween(0, 999), $this->faker->numberBetween(0, 999)),
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => CoachProfileStatus::Pending]);
    }

    public function approved(): static
    {
        return $this->state([
            'status' => CoachProfileStatus::Approved,
            'verified_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(['status' => CoachProfileStatus::Rejected]);
    }

    public function vatSubject(): static
    {
        return $this->state(['is_vat_subject' => true]);
    }

    public function nonVatSubject(): static
    {
        return $this->state(['is_vat_subject' => false]);
    }

    /**
     * State representing a coach profile whose address has been successfully
     * geocoded and validated by a provider.  Uses fixed Brussels coordinates
     * (Grand-Place) so tests remain deterministic.
     */
    public function withValidatedAddress(): static
    {
        return $this->state([
            'formatted_address' => 'Grand-Place, 1000 Bruxelles, Belgium',
            'street_address' => 'Grand-Place',
            'locality' => 'Bruxelles',
            'latitude' => 50.8467,
            'longitude' => 4.3525,
            'geocoding_provider' => 'google',
            'geocoding_place_id' => 'ChIJ_coach_test_place_id',
            'geocoded_at' => now(),
        ]);
    }
}
