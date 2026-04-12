<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\SportSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sport_session_id' => SportSession::factory(),
            'athlete_id' => User::factory()->athlete(),
            'status' => BookingStatus::PendingPayment->value,
            'amount_paid' => $this->faker->numberBetween(500, 5000),
        ];
    }

    public function pendingPayment(): static
    {
        return $this->state(['status' => BookingStatus::PendingPayment->value]);
    }

    public function confirmed(): static
    {
        return $this->state(['status' => BookingStatus::Confirmed->value]);
    }

    public function cancelled(): static
    {
        return $this->state([
            'status' => BookingStatus::Cancelled->value,
            'cancelled_at' => now(),
        ]);
    }

    public function refunded(): static
    {
        return $this->state([
            'status' => BookingStatus::Refunded->value,
            'refunded_at' => now(),
        ]);
    }
}
