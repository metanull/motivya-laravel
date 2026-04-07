<?php

declare(strict_types=1);

use App\Livewire\Auth\ForgotPassword;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('ForgotPassword Livewire Component', function () {

    it('renders the forgot password page', function () {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSeeLivewire(ForgotPassword::class);
    });

    it('displays translated heading', function () {
        Livewire::test(ForgotPassword::class)
            ->assertSee(__('auth.forgot_heading'));
    });

    it('sends a password reset link to a valid email', function () {
        Notification::fake();

        $user = User::factory()->create(['email' => 'user@example.com']);

        Livewire::test(ForgotPassword::class)
            ->set('email', 'user@example.com')
            ->call('sendResetLink')
            ->assertSet('linkSent', true)
            ->assertHasNoErrors();

        Notification::assertSentTo($user, ResetPassword::class);
    });

    it('shows success message after sending link', function () {
        Notification::fake();

        User::factory()->create(['email' => 'user@example.com']);

        Livewire::test(ForgotPassword::class)
            ->set('email', 'user@example.com')
            ->call('sendResetLink')
            ->assertSee(__('auth.forgot_link_sent'));
    });

    it('shows error for non-existent email', function () {
        Livewire::test(ForgotPassword::class)
            ->set('email', 'nobody@example.com')
            ->call('sendResetLink')
            ->assertHasErrors('email');
    });

    it('requires email', function () {
        Livewire::test(ForgotPassword::class)
            ->set('email', '')
            ->call('sendResetLink')
            ->assertHasErrors('email');
    });

    it('validates email format', function () {
        Livewire::test(ForgotPassword::class)
            ->set('email', 'not-an-email')
            ->call('sendResetLink')
            ->assertHasErrors('email');
    });

    it('shows link to login page', function () {
        Livewire::test(ForgotPassword::class)
            ->assertSee(__('auth.forgot_back_to_login'));
    });

});
