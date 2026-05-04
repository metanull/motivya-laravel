<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;

uses(RefreshDatabase::class);

describe('Two-Factor Authentication — TOTP', function () {

    it('shows the two-factor challenge page', function () {
        $this->get(route('two-factor.login'))
            ->assertOk()
            ->assertSee(__('auth.two_factor_heading'));
    });

    it('allows enabling two-factor authentication', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->postJson('/user/two-factor-authentication');

        $response->assertOk();

        $user->refresh();
        expect($user->two_factor_secret)->not->toBeNull();
    });

    it('allows confirming two-factor authentication with valid code', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->postJson('/user/two-factor-authentication');

        $user->refresh();

        $google2fa = new Google2FA;
        $secret = decrypt($user->two_factor_secret);
        $validCode = $google2fa->getCurrentOtp($secret);

        $response = $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->postJson('/user/confirmed-two-factor-authentication', [
                'code' => $validCode,
            ]);

        $response->assertOk();

        $user->refresh();
        expect($user->two_factor_confirmed_at)->not->toBeNull();
    });

    it('rejects confirming two-factor with invalid code', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->postJson('/user/two-factor-authentication');

        $response = $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->postJson('/user/confirmed-two-factor-authentication', [
                'code' => '000000',
            ]);

        $response->assertUnprocessable();

        $user->refresh();
        expect($user->two_factor_confirmed_at)->toBeNull();
    });

    it('allows disabling two-factor authentication', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->postJson('/user/two-factor-authentication');
        $user->refresh();

        $google2fa = new Google2FA;
        $secret = decrypt($user->two_factor_secret);
        $validCode = $google2fa->getCurrentOtp($secret);

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->postJson('/user/confirmed-two-factor-authentication', [
                'code' => $validCode,
            ]);

        $response = $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->deleteJson('/user/two-factor-authentication');

        $response->assertOk();

        $user->refresh();
        expect($user->two_factor_secret)->toBeNull();
        expect($user->two_factor_confirmed_at)->toBeNull();
    });

    it('provides recovery codes after enabling 2FA', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->postJson('/user/two-factor-authentication');

        $user->refresh();
        expect($user->two_factor_recovery_codes)->not->toBeNull();

        $response = $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->getJson('/user/two-factor-recovery-codes');

        $response->assertOk();
        $codes = $response->json();
        expect($codes)->toHaveCount(8);
    });

    it('requires two-factor code during login when 2FA is enabled', function () {
        $user = User::factory()->create([
            'password' => bcrypt('Password1!'),
        ]);

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->postJson('/user/two-factor-authentication');
        $user->refresh();

        $google2fa = new Google2FA;
        $secret = decrypt($user->two_factor_secret);
        $validCode = $google2fa->getCurrentOtp($secret);

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->postJson('/user/confirmed-two-factor-authentication', [
                'code' => $validCode,
            ]);

        $this->post('/logout');

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'Password1!',
        ]);

        $response->assertRedirect(route('two-factor.login'));
    });

    it('completes login with valid TOTP code after 2FA challenge', function () {
        $google2fa = new Google2FA;
        $secret = $google2fa->generateSecretKey();

        $user = User::factory()->create([
            'password' => bcrypt('Password1!'),
            'two_factor_secret' => encrypt($secret),
            'two_factor_recovery_codes' => encrypt(json_encode([
                'recovery-code-1',
                'recovery-code-2',
            ])),
            'two_factor_confirmed_at' => now(),
        ]);

        // Login (should redirect to 2FA challenge)
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'Password1!',
        ]);

        // Submit valid TOTP code
        $code = $google2fa->getCurrentOtp($secret);
        $response = $this->post('/two-factor-challenge', [
            'code' => $code,
        ]);

        $response->assertRedirect(route('athlete.dashboard'));
        $this->assertAuthenticatedAs($user);
    });

    it('rejects login with invalid TOTP code', function () {
        $user = User::factory()->create([
            'password' => bcrypt('Password1!'),
        ]);

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->postJson('/user/two-factor-authentication');
        $user->refresh();

        $google2fa = new Google2FA;
        $secret = decrypt($user->two_factor_secret);
        $validCode = $google2fa->getCurrentOtp($secret);

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->postJson('/user/confirmed-two-factor-authentication', [
                'code' => $validCode,
            ]);

        $this->post('/logout');

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'Password1!',
        ]);

        $response = $this->post('/two-factor-challenge', [
            'code' => '000000',
        ]);

        $response->assertRedirect(route('two-factor.login'));
        $this->assertGuest();
    });

    it('allows login with a valid recovery code', function () {
        $user = User::factory()->create([
            'password' => bcrypt('Password1!'),
        ]);

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->postJson('/user/two-factor-authentication');
        $user->refresh();

        $google2fa = new Google2FA;
        $secret = decrypt($user->two_factor_secret);
        $validCode = $google2fa->getCurrentOtp($secret);

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->postJson('/user/confirmed-two-factor-authentication', [
                'code' => $validCode,
            ]);

        $recoveryCodes = $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->getJson('/user/two-factor-recovery-codes')
            ->json();

        $this->post('/logout');

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'Password1!',
        ]);

        $response = $this->post('/two-factor-challenge', [
            'recovery_code' => $recoveryCodes[0],
        ]);

        $response->assertRedirect(route('athlete.dashboard'));
        $this->assertAuthenticatedAs($user);
    });

    it('does not require 2FA for users who have not enabled it', function () {
        $user = User::factory()->create([
            'password' => bcrypt('Password1!'),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'Password1!',
        ]);

        $response->assertRedirect(route('athlete.dashboard'));
        $this->assertAuthenticatedAs($user);
    });

    it('shows QR code endpoint for authenticated user enabling 2FA', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->postJson('/user/two-factor-authentication');

        $response = $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->getJson('/user/two-factor-qr-code');

        $response->assertOk();
        expect($response->json('svg'))->toContain('<svg');
    });
});
