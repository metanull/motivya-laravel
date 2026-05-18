<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;

final class ChangeUserRole extends Command
{
    protected $signature = 'users:change-role
                            {email : Email address of the user to update}
                            {role : New role: coach, athlete, accountant, admin}';

    protected $description = 'Change the role of an existing user account';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $roleValue = (string) $this->argument('role');

        try {
            $role = UserRole::from($roleValue);
        } catch (\ValueError) {
            $this->error('Role must be one of: coach, athlete, accountant, admin.');

            return self::FAILURE;
        }

        $user = User::where('email', '=', $email, 'and')->first();

        if ($user === null) {
            $this->error("No user found with email: {$email}");

            return self::FAILURE;
        }

        $previousRole = $user->role->value;
        $user->update(['role' => $role]);

        $this->info("User {$email} role changed from {$previousRole} to {$role->value}.");

        return self::SUCCESS;
    }
}
