<?php

declare(strict_types=1);

use App\Livewire\Auth\ForgotPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('ForgotPassword Livewire Component', function () {

    it('renders the forgot password page', function () {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSeeLivewire(ForgotPassword::class);
    });

    it('displays translated heading', function () {
        $this->get(route('password.request'))
            ->assertSee(__('auth.forgot_heading'));
    });

    it('displays the description text', function () {
        $this->get(route('password.request'))
            ->assertSee(__('auth.forgot_description'));
    });

    it('contains a form posting to Fortify password email route', function () {
        $this->get(route('password.request'))
            ->assertSee('action="'.route('password.email').'"', escape: false)
            ->assertSee('method="POST"', escape: false)
            ->assertSee('name="email"', escape: false);
    });

    it('contains a CSRF token', function () {
        $this->get(route('password.request'))
            ->assertSee('name="_token"', escape: false);
    });

    it('shows link to login page', function () {
        $this->get(route('password.request'))
            ->assertSee(__('auth.forgot_back_to_login'));
    });

    it('shows status message from session', function () {
        $this->withSession(['status' => 'Test status message'])
            ->get(route('password.request'))
            ->assertSee('Test status message');
    });

});
