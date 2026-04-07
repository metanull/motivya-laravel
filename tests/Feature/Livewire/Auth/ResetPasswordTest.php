<?php

declare(strict_types=1);

use App\Livewire\Auth\ResetPassword;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('ResetPassword Livewire Component', function () {

    it('renders the reset password page', function () {
        $this->get(route('password.reset', ['token' => 'test-token']))
            ->assertOk()
            ->assertSeeLivewire(ResetPassword::class);
    });

    it('displays translated heading', function () {
        Livewire::test(ResetPassword::class, ['token' => 'test-token'])
            ->assertSee(__('auth.reset_heading'));
    });

    it('receives token and email from route parameters', function () {
        Livewire::test(ResetPassword::class, ['token' => 'test-token', 'email' => 'user@example.com'])
            ->assertSet('token', 'test-token')
            ->assertSet('email', 'user@example.com');
    });

    it('resets password with valid token', function () {
        $user = User::factory()->create(['email' => 'user@example.com']);
        $token = Password::broker()->createToken($user);

        Livewire::test(ResetPassword::class, ['token' => $token])
            ->set('email', 'user@example.com')
            ->set('password', 'NewPassword1!')
            ->set('password_confirmation', 'NewPassword1!')
            ->call('resetPassword')
            ->assertRedirect(route('login'));

        expect(app('hash')->check('NewPassword1!', $user->fresh()->password))->toBeTrue();
    });

    it('shows error with invalid token', function () {
        User::factory()->create(['email' => 'user@example.com']);

        Livewire::test(ResetPassword::class, ['token' => 'invalid-token'])
            ->set('email', 'user@example.com')
            ->set('password', 'NewPassword1!')
            ->set('password_confirmation', 'NewPassword1!')
            ->call('resetPassword')
            ->assertHasErrors('email');
    });

    it('requires all fields', function () {
        Livewire::test(ResetPassword::class, ['token' => 'test-token'])
            ->set('email', '')
            ->set('password', '')
            ->set('password_confirmation', '')
            ->call('resetPassword')
            ->assertHasErrors(['email', 'password', 'password_confirmation']);
    });

    it('validates password confirmation matches', function () {
        Livewire::test(ResetPassword::class, ['token' => 'test-token'])
            ->set('email', 'user@example.com')
            ->set('password', 'NewPassword1!')
            ->set('password_confirmation', 'DifferentPassword!')
            ->call('resetPassword')
            ->assertHasErrors('password_confirmation');
    });

});
