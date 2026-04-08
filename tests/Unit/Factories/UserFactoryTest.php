<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('UserFactory', function () {

    it('creates an athlete by default', function () {
        $user = User::factory()->create();

        expect($user->role)->toBe(UserRole::Athlete);
    });

    it('creates a coach with the coach state', function () {
        $user = User::factory()->coach()->create();

        expect($user->role)->toBe(UserRole::Coach);
    });

    it('creates an athlete with the athlete state', function () {
        $user = User::factory()->athlete()->create();

        expect($user->role)->toBe(UserRole::Athlete);
    });

    it('creates an accountant with the accountant state', function () {
        $user = User::factory()->accountant()->create();

        expect($user->role)->toBe(UserRole::Accountant);
    });

    it('creates an admin with the admin state', function () {
        $user = User::factory()->admin()->create();

        expect($user->role)->toBe(UserRole::Admin);
    });

    it('creates a user with a specific locale', function () {
        $user = User::factory()->withLocale('nl')->create();

        expect($user->locale)->toBe('nl');
    });

    it('creates an unverified user', function () {
        $user = User::factory()->unverified()->create();

        expect($user->email_verified_at)->toBeNull();
    });

    it('creates a verified user by default', function () {
        $user = User::factory()->create();

        expect($user->email_verified_at)->not->toBeNull();
    });
});
