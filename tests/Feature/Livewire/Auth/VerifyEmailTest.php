<?php

declare(strict_types=1);

use App\Livewire\Auth\VerifyEmail;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail as VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('VerifyEmail Livewire Component', function () {

    it('renders for authenticated users', function () {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('verification.notice'))
            ->assertOk()
            ->assertSeeLivewire(VerifyEmail::class);
    });

    it('displays translated heading', function () {
        $user = User::factory()->unverified()->create();

        Livewire::actingAs($user)
            ->test(VerifyEmail::class)
            ->assertSee(__('auth.verify_heading'));
    });

    it('redirects guests to login', function () {
        $this->get(route('verification.notice'))
            ->assertRedirect(route('login'));
    });

    it('resends verification email', function () {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        Livewire::actingAs($user)
            ->test(VerifyEmail::class)
            ->call('resend')
            ->assertSet('resent', true);

        Notification::assertSentTo($user, VerifyEmailNotification::class);
    });

    it('shows confirmation after resending', function () {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        Livewire::actingAs($user)
            ->test(VerifyEmail::class)
            ->call('resend')
            ->assertSee(__('auth.verify_resent'));
    });

    it('logs out the user', function () {
        $user = User::factory()->unverified()->create();

        Livewire::actingAs($user)
            ->test(VerifyEmail::class)
            ->call('logout')
            ->assertRedirect(route('home'));
    });

});
