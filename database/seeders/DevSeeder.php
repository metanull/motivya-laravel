<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class DevSeeder extends Seeder
{
    /**
     * Seed the database with one user per role for local development,
     * and run the MVP journey scenario for manual QA smoke testing.
     */
    public function run(): void
    {
        $password = Hash::make('password');

        User::firstOrCreate(
            ['email' => 'admin@motivya.test'],
            [
                'name' => 'Admin User',
                'password' => $password,
                'role' => UserRole::Admin->value,
                'email_verified_at' => now(),
            ]
        );

        User::firstOrCreate(
            ['email' => 'coach@motivya.test'],
            [
                'name' => 'Coach User',
                'password' => $password,
                'role' => UserRole::Coach->value,
                'email_verified_at' => now(),
            ]
        );

        User::firstOrCreate(
            ['email' => 'athlete@motivya.test'],
            [
                'name' => 'Athlete User',
                'password' => $password,
                'role' => UserRole::Athlete->value,
                'email_verified_at' => now(),
            ]
        );

        User::firstOrCreate(
            ['email' => 'accountant@motivya.test'],
            [
                'name' => 'Accountant User',
                'password' => $password,
                'role' => UserRole::Accountant->value,
                'email_verified_at' => now(),
            ]
        );

        // Seed the full MVP journey scenario for manual QA smoke testing.
        // See doc/MVP-Smoke-Test.md for the checklist.
        $this->call(MvpJourneySeeder::class);
    }
}
