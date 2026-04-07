<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

describe('Password Reset', function () {

    describe('forgot-password (send reset link)', function () {

        it('sends a reset link to a valid email address', function () {
            Notification::fake();

            $user = User::factory()->create(['email' => 'user@example.com']);

            $this->postJson('/forgot-password', ['email' => 'user@example.com'])
                ->assertOk();

            Notification::assertSentTo($user, ResetPassword::class);
        });

        it('returns 422 when email is missing', function () {
            $this->postJson('/forgot-password', [])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['email']);
        });

        it('returns 422 when email does not belong to any user', function () {
            $this->postJson('/forgot-password', ['email' => 'nobody@example.com'])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['email']);
        });

    });

    describe('reset-password', function () {

        it('resets the password with a valid token', function () {
            $user = User::factory()->create(['email' => 'user@example.com']);
            $token = Password::broker()->createToken($user);

            $this->postJson('/reset-password', [
                'email' => 'user@example.com',
                'token' => $token,
                'password' => 'NewPassword1!',
                'password_confirmation' => 'NewPassword1!',
            ])->assertOk();

            $this->assertTrue(
                app('hash')->check('NewPassword1!', $user->fresh()->password)
            );
        });

        it('returns 422 with an invalid token', function () {
            User::factory()->create(['email' => 'user@example.com']);

            $this->postJson('/reset-password', [
                'email' => 'user@example.com',
                'token' => 'invalid-token',
                'password' => 'NewPassword1!',
                'password_confirmation' => 'NewPassword1!',
            ])->assertUnprocessable()
                ->assertJsonValidationErrors(['email']);
        });

        it('returns 422 when passwords do not match', function () {
            $user = User::factory()->create(['email' => 'user@example.com']);
            $token = Password::broker()->createToken($user);

            $this->postJson('/reset-password', [
                'email' => 'user@example.com',
                'token' => $token,
                'password' => 'NewPassword1!',
                'password_confirmation' => 'WrongPassword1!',
            ])->assertUnprocessable()
                ->assertJsonValidationErrors(['password']);
        });

    });

});
