<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

describe('user management console commands', function () {
    it('creates a verified user with the requested role', function (): void {
        $this->artisan('users:create --email=admin@example.test --name="Admin User" --role=admin --password=Password1!')
            ->assertSuccessful();

        $user = User::where('email', '=', 'admin@example.test', 'and')->firstOrFail();

        expect($user->role)->toBe(UserRole::Admin)
            ->and($user->email_verified_at)->not->toBeNull();
    });

    it('changes an existing user role', function (): void {
        $user = User::factory()->athlete()->create([
            'email' => 'role-change@example.test',
        ]);

        $this->artisan('users:change-role role-change@example.test accountant')
            ->assertSuccessful();

        expect($user->fresh()->role)->toBe(UserRole::Accountant);
    });

    it('lists users as JSON for scripting', function (): void {
        User::factory()->coach()->create([
            'email' => 'coach-list@example.test',
            'name' => 'Coach List',
        ]);

        expect(Artisan::call('users:list', ['--json' => true]))->toBe(0);

        $payload = json_decode(trim(Artisan::output()), true, 512, JSON_THROW_ON_ERROR);

        expect($payload['users'][0]['email'])->toBe('coach-list@example.test')
            ->and($payload['users'][0]['role'])->toBe(UserRole::Coach->value);
    });
});
