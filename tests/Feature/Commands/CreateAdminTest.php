<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('app:create-admin (create mode)', function (): void {
    it('creates a new admin user with all options', function (): void {
        $this->artisan('app:create-admin', [
            '--email' => 'admin@example.com',
            '--name' => 'Test Admin',
            '--password' => 'SecurePassword123',
        ])
            ->expectsOutput('Admin user created: admin@example.com')
            ->assertSuccessful();

        $user = User::where('email', 'admin@example.com')->first();

        expect($user)->not->toBeNull()
            ->and($user->role)->toBe(UserRole::Admin)
            ->and($user->name)->toBe('Test Admin')
            ->and($user->email_verified_at)->not->toBeNull();
    });

    it('fails when email already exists', function (): void {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->artisan('app:create-admin', [
            '--email' => 'taken@example.com',
            '--name' => 'Duplicate',
            '--password' => 'SecurePassword123',
        ])->assertFailed();
    });

    it('fails when email is invalid', function (): void {
        $this->artisan('app:create-admin', [
            '--email' => 'not-an-email',
            '--name' => 'Bad Email',
            '--password' => 'SecurePassword123',
        ])->assertFailed();
    });

    it('fails when password is too short', function (): void {
        $this->artisan('app:create-admin', [
            '--email' => 'admin@example.com',
            '--name' => 'Short Pass',
            '--password' => 'short',
        ])->assertFailed();
    });
});

describe('app:create-admin --promote', function (): void {
    it('promotes an existing athlete to admin', function (): void {
        $user = User::factory()->athlete()->create(['email' => 'athlete@example.com']);

        $this->artisan('app:create-admin', [
            '--promote' => true,
            '--email' => 'athlete@example.com',
        ])
            ->expectsOutput('User athlete@example.com promoted from athlete to admin.')
            ->assertSuccessful();

        $user->refresh();
        expect($user->role)->toBe(UserRole::Admin);
    });

    it('succeeds silently when user is already admin', function (): void {
        User::factory()->admin()->create(['email' => 'admin@example.com']);

        $this->artisan('app:create-admin', [
            '--promote' => true,
            '--email' => 'admin@example.com',
        ])
            ->expectsOutput('User admin@example.com is already an admin.')
            ->assertSuccessful();
    });

    it('fails when user does not exist', function (): void {
        $this->artisan('app:create-admin', [
            '--promote' => true,
            '--email' => 'ghost@example.com',
        ])
            ->expectsOutput('No user found with email: ghost@example.com')
            ->assertFailed();
    });

    it('promotes a coach to admin', function (): void {
        $user = User::factory()->coach()->create(['email' => 'coach@example.com']);

        $this->artisan('app:create-admin', [
            '--promote' => true,
            '--email' => 'coach@example.com',
        ])->assertSuccessful();

        $user->refresh();
        expect($user->role)->toBe(UserRole::Admin);
    });
});
