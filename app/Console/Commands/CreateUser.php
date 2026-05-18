<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

final class CreateUser extends Command
{
    protected $signature = 'users:create
                            {--email= : Email address}
                            {--name= : Display name}
                            {--role=athlete : Role: coach, athlete, accountant, admin}
                            {--password= : Password; prompted interactively if omitted}
                            {--unverified : Leave email unverified}';

    protected $description = 'Create a user account with a Motivya role';

    public function handle(): int
    {
        $email = $this->stringOption('email') ?? $this->ask('Email address');
        $name = $this->stringOption('name') ?? $this->ask('Display name');
        $password = $this->stringOption('password') ?? $this->secret('Password');
        $roleValue = $this->stringOption('role') ?? UserRole::Athlete->value;

        try {
            $role = UserRole::from($roleValue);
        } catch (\ValueError) {
            $this->error('--role must be one of: coach, athlete, accountant, admin.');

            return self::FAILURE;
        }

        $validator = Validator::make([
            'email' => $email,
            'name' => $name,
            'password' => $password,
        ], [
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make((string) $password),
            'role' => $role,
        ]);

        if (! (bool) $this->option('unverified')) {
            $user->markEmailAsVerified();
        }

        $this->info("User created: {$user->email} ({$user->role->value})");

        return self::SUCCESS;
    }

    private function stringOption(string $name): ?string
    {
        $value = $this->option($name);

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
