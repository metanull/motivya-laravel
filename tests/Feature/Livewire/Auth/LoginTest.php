<?php

declare(strict_types=1);

use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('Login Livewire Component', function () {

    it('renders the login page', function () {
        $this->get(route('login'))
            ->assertOk()
            ->assertSeeLivewire(Login::class);
    });

    it('displays translated heading', function () {
        Livewire::test(Login::class)
            ->assertSee(__('auth.login_heading'));
    });

    it('authenticates a user with valid credentials', function () {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('Password1!'),
        ]);

        Livewire::test(Login::class)
            ->set('form.email', 'user@example.com')
            ->set('form.password', 'Password1!')
            ->call('authenticate')
            ->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
    });

    it('shows validation error with invalid credentials', function () {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('Password1!'),
        ]);

        Livewire::test(Login::class)
            ->set('form.email', 'user@example.com')
            ->set('form.password', 'WrongPassword!')
            ->call('authenticate')
            ->assertHasErrors('form.email');
    });

    it('requires email and password', function () {
        Livewire::test(Login::class)
            ->set('form.email', '')
            ->set('form.password', '')
            ->call('authenticate')
            ->assertHasErrors(['form.email', 'form.password']);
    });

    it('validates email format', function () {
        Livewire::test(Login::class)
            ->set('form.email', 'not-an-email')
            ->set('form.password', 'Password1!')
            ->call('authenticate')
            ->assertHasErrors('form.email');
    });

    it('redirects authenticated users away from login page', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('login'))
            ->assertRedirect();
    });

    it('shows link to register page', function () {
        Livewire::test(Login::class)
            ->assertSee(__('auth.login_register_link'));
    });

    it('shows link to forgot password page', function () {
        Livewire::test(Login::class)
            ->assertSee(__('auth.login_forgot'));
    });

});
