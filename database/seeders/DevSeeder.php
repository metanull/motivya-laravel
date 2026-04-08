<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class DevSeeder extends Seeder
{
    /**
     * Seed the database with one user per role for local development.
     */
    public function run(): void
    {
        $password = Hash::make('password');

        User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@motivya.test',
            'password' => $password,
        ]);

        User::factory()->coach()->create([
            'name' => 'Coach User',
            'email' => 'coach@motivya.test',
            'password' => $password,
        ]);

        User::factory()->athlete()->create([
            'name' => 'Athlete User',
            'email' => 'athlete@motivya.test',
            'password' => $password,
        ]);

        User::factory()->accountant()->create([
            'name' => 'Accountant User',
            'email' => 'accountant@motivya.test',
            'password' => $password,
        ]);
    }
}
