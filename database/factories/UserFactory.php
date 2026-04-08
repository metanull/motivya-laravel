<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TwoFactorMethod;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => UserRole::Athlete->value,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function coach(): static
    {
        return $this->state(['role' => UserRole::Coach->value]);
    }

    public function athlete(): static
    {
        return $this->state(['role' => UserRole::Athlete->value]);
    }

    public function accountant(): static
    {
        return $this->state(['role' => UserRole::Accountant->value]);
    }

    public function admin(): static
    {
        return $this->state(['role' => UserRole::Admin->value]);
    }

    public function withLocale(string $locale): static
    {
        return $this->state(['locale' => $locale]);
    }

    public function withTwoFactor(TwoFactorMethod $method = TwoFactorMethod::Email): static
    {
        return $this->state([
            'two_factor_confirmed_at' => now(),
            'two_factor_type' => $method,
        ]);
    }
}
