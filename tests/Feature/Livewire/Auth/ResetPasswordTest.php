<?php

declare(strict_types=1);

use App\Livewire\Auth\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('ResetPassword Livewire Component', function () {

    it('renders the reset password page', function () {
        $this->get(route('password.reset', ['token' => 'test-token']))
            ->assertOk()
            ->assertSeeLivewire(ResetPassword::class);
    });

    it('displays translated heading', function () {
        $this->get(route('password.reset', ['token' => 'test-token']))
            ->assertSee(__('auth.reset_heading'));
    });

    it('contains a form posting to Fortify password update route', function () {
        $this->get(route('password.reset', ['token' => 'test-token']))
            ->assertSee('action="'.route('password.update').'"', escape: false)
            ->assertSee('method="POST"', escape: false)
            ->assertSee('name="email"', escape: false)
            ->assertSee('name="password"', escape: false)
            ->assertSee('name="password_confirmation"', escape: false);
    });

    it('contains the reset token as a hidden field', function () {
        $this->get(route('password.reset', ['token' => 'test-token-value']))
            ->assertSee('name="token"', escape: false)
            ->assertSee('value="test-token-value"', escape: false);
    });

    it('contains a CSRF token', function () {
        $this->get(route('password.reset', ['token' => 'test-token']))
            ->assertSee('name="_token"', escape: false);
    });

    it('pre-fills email from query parameter', function () {
        $this->get(route('password.reset', ['token' => 'test-token', 'email' => 'user@example.com']))
            ->assertSee('value="user@example.com"', escape: false);
    });

});
