<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

final class CreateAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-admin
                            {--email= : Email address for the admin user}
                            {--name= : Display name for the admin user}
                            {--password= : Password (prompted interactively if omitted)}
                            {--promote : Promote an existing user instead of creating a new one}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new admin user or promote an existing user to admin';

    public function handle(): int
    {
        if ($this->option('promote')) {
            return $this->promoteExisting();
        }

        return $this->createNew();
    }

    private function createNew(): int
    {
        $email = $this->option('email') ?? $this->ask('Email address');
        $name = $this->option('name') ?? $this->ask('Display name');
        $password = $this->option('password') ?? $this->secret('Password');

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
            'password' => Hash::make($password),
            'role' => UserRole::Admin,
        ]);

        $user->markEmailAsVerified();

        $this->info("Admin user created: {$user->email}");

        return self::SUCCESS;
    }

    private function promoteExisting(): int
    {
        $email = $this->option('email') ?? $this->ask('Email address of the user to promote');

        $user = User::where('email', $email)->first();

        if ($user === null) {
            $this->error("No user found with email: {$email}");

            return self::FAILURE;
        }

        if ($user->role === UserRole::Admin) {
            $this->warn("User {$email} is already an admin.");

            return self::SUCCESS;
        }

        $previousRole = $user->role->value;
        $user->update(['role' => UserRole::Admin]);

        $this->info("User {$email} promoted from {$previousRole} to admin.");

        return self::SUCCESS;
    }
}
