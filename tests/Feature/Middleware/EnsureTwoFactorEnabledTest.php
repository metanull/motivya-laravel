<?php

declare(strict_types=1);

use App\Enums\TwoFactorMethod;
use App\Http\Middleware\EnsureTwoFactorEnabled;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Register a test route behind the middleware
    Route::middleware(['web', 'auth', '2fa'])->get('/test-2fa-required', function () {
        return response('OK');
    })->name('test-2fa-required');
});

describe('EnsureTwoFactorEnabled middleware', function () {

    it('allows admin with TOTP 2FA enabled', function () {
        $user = User::factory()->admin()->create([
            'two_factor_secret' => encrypt('secret'),
            'two_factor_confirmed_at' => now(),
            'two_factor_type' => TwoFactorMethod::Totp,
        ]);

        $response = $this->actingAs($user)->get('/test-2fa-required');

        $response->assertOk();
        $response->assertSee('OK');
    });

    it('allows admin with email 2FA enabled', function () {
        $user = User::factory()->admin()->create([
            'two_factor_confirmed_at' => now(),
            'two_factor_type' => TwoFactorMethod::Email,
        ]);

        $response = $this->actingAs($user)->get('/test-2fa-required');

        $response->assertOk();
        $response->assertSee('OK');
    });

    it('redirects admin without 2FA to profile setup', function () {
        $user = User::factory()->admin()->create([
            'two_factor_confirmed_at' => null,
        ]);

        $response = $this->actingAs($user)->get('/test-2fa-required');

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('status', __('auth.two_factor_required'));
    });

    it('allows accountant with 2FA enabled', function () {
        $user = User::factory()->accountant()->create([
            'two_factor_confirmed_at' => now(),
            'two_factor_type' => TwoFactorMethod::Email,
        ]);

        $response = $this->actingAs($user)->get('/test-2fa-required');

        $response->assertOk();
    });

    it('redirects accountant without 2FA to profile setup', function () {
        $user = User::factory()->accountant()->create([
            'two_factor_confirmed_at' => null,
        ]);

        $response = $this->actingAs($user)->get('/test-2fa-required');

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('status', __('auth.two_factor_required'));
    });

    it('allows coach without 2FA (not required)', function () {
        $user = User::factory()->coach()->create([
            'two_factor_confirmed_at' => null,
        ]);

        $response = $this->actingAs($user)->get('/test-2fa-required');

        $response->assertOk();
    });

    it('allows athlete without 2FA (not required)', function () {
        $user = User::factory()->athlete()->create([
            'two_factor_confirmed_at' => null,
        ]);

        $response = $this->actingAs($user)->get('/test-2fa-required');

        $response->assertOk();
    });

    it('passes through for unauthenticated users', function () {
        // The auth middleware will handle unauthenticated users before this middleware
        // but the middleware itself should not crash on null user
        $middleware = new EnsureTwoFactorEnabled;
        $request = Request::create('/test');

        $response = $middleware->handle($request, fn () => new Response('OK'));

        expect($response->getContent())->toBe('OK');
    });
});
