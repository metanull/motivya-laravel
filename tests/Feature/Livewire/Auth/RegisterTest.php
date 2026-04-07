<?php

declare(strict_types=1);

use App\Livewire\Auth\Register;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Register Livewire Component', function () {

    it('renders the register page', function () {
        $this->get(route('register'))
            ->assertOk()
            ->assertSeeLivewire(Register::class);
    });

    it('displays translated heading', function () {
        $this->get(route('register'))
            ->assertSee(__('auth.register_heading'));
    });

    it('contains a form posting to Fortify register route', function () {
        $this->get(route('register'))
            ->assertSee('action="'.route('register.store').'"', escape: false)
            ->assertSee('method="POST"', escape: false)
            ->assertSee('name="name"', escape: false)
            ->assertSee('name="email"', escape: false)
            ->assertSee('name="password"', escape: false)
            ->assertSee('name="password_confirmation"', escape: false);
    });

    it('contains a CSRF token', function () {
        $this->get(route('register'))
            ->assertSee('name="_token"', escape: false);
    });

    it('redirects authenticated users away from register page', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('register'))
            ->assertRedirect();
    });

    it('shows link to login page', function () {
        $this->get(route('register'))
            ->assertSee(__('auth.register_login_link'));
    });

});
