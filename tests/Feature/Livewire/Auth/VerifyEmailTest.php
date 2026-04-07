<?php

declare(strict_types=1);

use App\Livewire\Auth\VerifyEmail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

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

        $this->actingAs($user)
            ->get(route('verification.notice'))
            ->assertSee(__('auth.verify_heading'));
    });

    it('displays the description text', function () {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('verification.notice'))
            ->assertSee(__('auth.verify_description'));
    });

    it('redirects guests to login', function () {
        $this->get(route('verification.notice'))
            ->assertRedirect(route('login'));
    });

    it('contains a form posting to Fortify verification send route', function () {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('verification.notice'))
            ->assertSee('action="'.route('verification.send').'"', escape: false)
            ->assertSee(__('auth.verify_resend'));
    });

    it('contains a logout form', function () {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('verification.notice'))
            ->assertSee('action="'.route('logout').'"', escape: false)
            ->assertSee(__('auth.verify_logout'));
    });

    it('shows status message from session', function () {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->withSession(['status' => 'Verification link sent!'])
            ->get(route('verification.notice'))
            ->assertSee('Verification link sent!');
    });

});
