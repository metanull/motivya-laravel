<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Registration', function () {

    it('registers a new user with athlete role', function () {
        $this->postJson('/register', [
            'name' => 'Test Athlete',
            'email' => 'athlete@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
        ])->assertCreated();

        $user = User::where('email', 'athlete@example.com')->first();

        expect($user)->not->toBeNull();
        expect($user->role)->toBe(UserRole::Athlete);
        expect($user->name)->toBe('Test Athlete');
    });

    it('stores hashed password', function () {
        $this->postJson('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
        ]);

        $user = User::where('email', 'test@example.com')->first();

        expect($user->password)->not->toBe('Password1!');
    });

    it('returns 422 when email is already taken', function () {
        User::factory()->create(['email' => 'existing@example.com']);

        $this->postJson('/register', [
            'name' => 'Duplicate',
            'email' => 'existing@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('returns 422 when required fields are missing', function () {
        $this->postJson('/register', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    });

    it('returns 422 when passwords do not match', function () {
        $this->postJson('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'WrongPassword1!',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    });

});
