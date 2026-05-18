<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;

final class ListUsers extends Command
{
    protected $signature = 'users:list
                            {--role= : Filter by role: coach, athlete, accountant, admin}
                            {--json : Output JSON for scripting}';

    protected $description = 'List user accounts and roles';

    public function handle(): int
    {
        $role = $this->option('role');

        if (is_string($role) && $role !== '') {
            try {
                $role = UserRole::from($role);
            } catch (\ValueError) {
                $this->error('--role must be one of: coach, athlete, accountant, admin.');

                return self::FAILURE;
            }
        } else {
            $role = null;
        }

        $users = User::query()
            ->when($role instanceof UserRole, fn ($query) => $query->where('role', $role->value))
            ->orderBy('id', 'asc')
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'email_verified' => $user->email_verified_at !== null,
                'suspended' => $user->suspended_at !== null,
                'created_at' => $user->created_at?->toISOString(),
            ]);

        if ((bool) $this->option('json')) {
            $this->line((string) json_encode(['users' => $users->all()], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        if ($users->isEmpty()) {
            $this->warn('No users found.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Name', 'Email', 'Role', 'Verified', 'Suspended'],
            $users->map(fn (array $user): array => [
                $user['id'],
                $user['name'],
                $user['email'],
                $user['role'],
                $user['email_verified'] ? 'yes' : 'no',
                $user['suspended'] ? 'yes' : 'no',
            ])->all()
        );

        return self::SUCCESS;
    }
}
