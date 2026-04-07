<?php

declare(strict_types=1);

use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Login Livewire Component', function () {

    it('renders the login page', function () {
        $this->get(route('login'))
            ->assertOk()
            ->assertSeeLivewire(Login::class);
    });

    it('displays translated heading', function () {
        $this->get(route('login'))
            ->assertSee(__('auth.login_heading'));
    });

    it('contains a form posting to Fortify login route', function () {
        $this->get(route('login'))
            ->assertSee('action="'.route('login.store').'"', escape: false)
            ->assertSee('method="POST"', escape: false)
            ->assertSee('name="email"', escape: false)
            ->assertSee('name="password"', escape: false);
    });

    it('contains a CSRF token', function () {
        $this->get(route('login'))
            ->assertSee('name="_token"', escape: false);
    });

    it('redirects authenticated users away from login page', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('login'))
            ->assertRedirect();
    });

    it('shows link to register page', function () {
        $this->get(route('login'))
            ->assertSee(__('auth.login_register_link'));
    });

    it('shows link to forgot password page', function () {
        $this->get(route('login'))
            ->assertSee(__('auth.login_forgot'));
    });

    it('contains remember me checkbox', function () {
        $this->get(route('login'))
            ->assertSee('name="remember"', escape: false);
    });

});
