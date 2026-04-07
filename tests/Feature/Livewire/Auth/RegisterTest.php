<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Livewire\Auth\Register;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('Register Livewire Component', function () {

    it('renders the register page', function () {
        $this->get(route('register'))
            ->assertOk()
            ->assertSeeLivewire(Register::class);
    });

    it('displays translated heading', function () {
        Livewire::test(Register::class)
            ->assertSee(__('auth.register_heading'));
    });

    it('registers a new user with athlete role', function () {
        Livewire::test(Register::class)
            ->set('form.name', 'Test Athlete')
            ->set('form.email', 'athlete@example.com')
            ->set('form.password', 'Password1!')
            ->set('form.password_confirmation', 'Password1!')
            ->call('register')
            ->assertRedirect(route('home'));

        $user = User::where('email', 'athlete@example.com')->first();

        expect($user)->not->toBeNull();
        expect($user->role)->toBe(UserRole::Athlete);
        expect($user->name)->toBe('Test Athlete');
    });

    it('logs in the user after registration', function () {
        Livewire::test(Register::class)
            ->set('form.name', 'Test User')
            ->set('form.email', 'test@example.com')
            ->set('form.password', 'Password1!')
            ->set('form.password_confirmation', 'Password1!')
            ->call('register');

        $this->assertAuthenticated();
    });

    it('requires all fields', function () {
        Livewire::test(Register::class)
            ->set('form.name', '')
            ->set('form.email', '')
            ->set('form.password', '')
            ->set('form.password_confirmation', '')
            ->call('register')
            ->assertHasErrors(['form.name', 'form.email', 'form.password', 'form.password_confirmation']);
    });

    it('validates email format', function () {
        Livewire::test(Register::class)
            ->set('form.name', 'Test')
            ->set('form.email', 'not-an-email')
            ->set('form.password', 'Password1!')
            ->set('form.password_confirmation', 'Password1!')
            ->call('register')
            ->assertHasErrors('form.email');
    });

    it('validates password confirmation matches', function () {
        Livewire::test(Register::class)
            ->set('form.name', 'Test')
            ->set('form.email', 'test@example.com')
            ->set('form.password', 'Password1!')
            ->set('form.password_confirmation', 'DifferentPassword!')
            ->call('register')
            ->assertHasErrors('form.password_confirmation');
    });

    it('rejects duplicate email', function () {
        User::factory()->create(['email' => 'existing@example.com']);

        Livewire::test(Register::class)
            ->set('form.name', 'Duplicate')
            ->set('form.email', 'existing@example.com')
            ->set('form.password', 'Password1!')
            ->set('form.password_confirmation', 'Password1!')
            ->call('register')
            ->assertHasErrors();
    });

    it('redirects authenticated users away from register page', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('register'))
            ->assertRedirect();
    });

    it('shows link to login page', function () {
        Livewire::test(Register::class)
            ->assertSee(__('auth.register_login_link'));
    });

});
