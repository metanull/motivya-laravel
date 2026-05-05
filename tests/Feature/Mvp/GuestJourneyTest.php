<?php

declare(strict_types=1);

use App\Models\SportSession;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('MVP Guest Journey', function () {

    it('can browse the session listing as a guest', function () {
        SportSession::factory()->published()->create([
            'title' => 'Public Running Session',
        ]);

        $this->get(route('sessions.index'))
            ->assertOk()
            ->assertSee('Public Running Session');
    });

    it('can view a session detail as a guest', function () {
        $session = SportSession::factory()->published()->create([
            'title' => 'Guest Viewable Session',
        ]);

        $this->get(route('sessions.show', $session))
            ->assertOk()
            ->assertSee('Guest Viewable Session');
    });

    it('is redirected to login when accessing athlete dashboard', function () {
        $this->get(route('athlete.dashboard'))
            ->assertRedirect(route('login'));
    });

    it('is redirected to login when accessing coach dashboard', function () {
        $this->get(route('coach.dashboard'))
            ->assertRedirect(route('login'));
    });

    it('is redirected to login when accessing accountant dashboard', function () {
        $this->get(route('accountant.dashboard'))
            ->assertRedirect(route('login'));
    });

    it('is redirected to login when accessing admin dashboard', function () {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));
    });

    it('can view the home page', function () {
        $this->get(route('home'))
            ->assertOk();
    });

    it('can view the login page', function () {
        $this->get(route('login'))
            ->assertOk();
    });

    it('can view the register page', function () {
        $this->get(route('register'))
            ->assertOk();
    });

    it('can reach the health endpoint', function () {
        $this->get(route('health'))
            ->assertOk()
            ->assertJsonPath('status', 'ok');
    });

});
