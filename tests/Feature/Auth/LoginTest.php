<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Login', function () {

    it('authenticates a user with valid credentials', function () {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('Password1!'),
        ]);

        $this->postJson('/login', [
            'email' => 'user@example.com',
            'password' => 'Password1!',
        ])->assertOk();

        $this->assertAuthenticatedAs($user);
    });

    it('returns 422 with invalid credentials', function () {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('Password1!'),
        ]);

        $this->postJson('/login', [
            'email' => 'user@example.com',
            'password' => 'WrongPassword!',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('returns 422 when email does not exist', function () {
        $this->postJson('/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'Password1!',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    it('returns 422 when required fields are missing', function () {
        $this->postJson('/login', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    });

    it('logs out an authenticated user', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/logout')
            ->assertNoContent();

        $this->assertGuest();
    });

});
