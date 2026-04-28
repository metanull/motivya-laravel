<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Reference data — always seeded regardless of environment.
        $this->call(PostalCodeCoordinatesSeeder::class);

        if (app()->environment('local', 'testing')) {
            $this->call(DevSeeder::class);
        }
    }
}
