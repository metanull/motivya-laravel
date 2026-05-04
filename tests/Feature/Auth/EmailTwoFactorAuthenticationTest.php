<?php

declare(strict_types=1);

use App\Enums\TwoFactorMethod;
use App\Livewire\Auth\TwoFactorChallenge;
use App\Livewire\Profile\TwoFactorAuthentication;
use App\Models\User;
use App\Notifications\TwoFactorCodeNotification;
use App\Services\EmailTwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('Two-Factor Authentication — Email Code', function () {

    it('sends an email code on login when email 2FA is enabled', function () {
        Notification::fake();

        $user = User::factory()->create([
            'password' => bcrypt('Password1!'),
            'two_factor_type' => TwoFactorMethod::Email,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => encrypt(json_encode([
                'recovery-code-1',
                'recovery-code-2',
            ])),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'Password1!',
        ]);

        $response->assertRedirect(route('two-factor.login'));

        Notification::assertSentTo($user, TwoFactorCodeNotification::class);
    });

    it('redirects to challenge page with email method in session', function () {
        Notification::fake();

        $user = User::factory()->create([
            'password' => bcrypt('Password1!'),
            'two_factor_type' => TwoFactorMethod::Email,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => encrypt(json_encode(['code-1'])),
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'Password1!',
        ]);

        expect(session('login.two_factor_method'))->toBe('email');
        expect(session('login.id'))->toBe($user->id);
    });

    it('shows email 2FA challenge page', function () {
        $this->get(route('two-factor.login'))
            ->assertOk()
            ->assertSee(__('auth.two_factor_heading'));
    });

    it('completes login with valid email code', function () {
        Notification::fake();

        $user = User::factory()->create([
            'password' => bcrypt('Password1!'),
            'two_factor_type' => TwoFactorMethod::Email,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => encrypt(json_encode(['code-1'])),
        ]);

        // Simulate login redirect (sets session)
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'Password1!',
        ]);

        // Get the code from cache
        $code = Cache::get("2fa_email_code:{$user->id}");
        expect($code)->not->toBeNull();

        // Verify the email code via Livewire
        $response = Livewire::test(TwoFactorChallenge::class)
            ->set('code', $code)
            ->call('verifyEmailCode');

        $response->assertRedirect(route('athlete.dashboard'));
        $this->assertAuthenticatedAs($user);
    });

    it('rejects invalid email code', function () {
        Notification::fake();

        $user = User::factory()->create([
            'password' => bcrypt('Password1!'),
            'two_factor_type' => TwoFactorMethod::Email,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => encrypt(json_encode(['code-1'])),
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'Password1!',
        ]);

        $response = Livewire::test(TwoFactorChallenge::class)
            ->set('code', '000000')
            ->call('verifyEmailCode');

        $response->assertHasErrors('code');
        $this->assertGuest();
    });

    it('allows login with recovery code for email 2FA users', function () {
        Notification::fake();

        $user = User::factory()->create([
            'password' => bcrypt('Password1!'),
            'two_factor_type' => TwoFactorMethod::Email,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => encrypt(json_encode([
                'recovery-code-1',
                'recovery-code-2',
            ])),
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'Password1!',
        ]);

        $response = Livewire::test(TwoFactorChallenge::class)
            ->set('recoveryCode', 'recovery-code-1')
            ->call('verifyRecoveryCode');

        $response->assertRedirect(route('athlete.dashboard'));
        $this->assertAuthenticatedAs($user);

        // The used recovery code should be consumed
        $user->refresh();
        $codes = json_decode(decrypt($user->two_factor_recovery_codes), true);
        expect($codes)->not->toContain('recovery-code-1');
        expect($codes)->toContain('recovery-code-2');
    });

    it('rejects invalid recovery code for email 2FA users', function () {
        Notification::fake();

        $user = User::factory()->create([
            'password' => bcrypt('Password1!'),
            'two_factor_type' => TwoFactorMethod::Email,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'Password1!',
        ]);

        $response = Livewire::test(TwoFactorChallenge::class)
            ->set('recoveryCode', 'invalid-code')
            ->call('verifyRecoveryCode');

        $response->assertHasErrors('recovery_code');
        $this->assertGuest();
    });

    it('enforces max 5 attempts per code', function () {
        Notification::fake();

        $user = User::factory()->create([
            'password' => bcrypt('Password1!'),
            'two_factor_type' => TwoFactorMethod::Email,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => encrypt(json_encode(['code-1'])),
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'Password1!',
        ]);

        // Get the real code
        $realCode = Cache::get("2fa_email_code:{$user->id}");

        // Use up all attempts with wrong codes
        $service = app(EmailTwoFactorService::class);
        for ($i = 0; $i < 5; $i++) {
            $service->verify($user, '000000');
        }

        // Even the real code should now fail
        $result = $service->verify($user, $realCode);
        expect($result)->toBeFalse();
    });

    it('expires code after 10 minutes', function () {
        Notification::fake();

        $user = User::factory()->create([
            'two_factor_type' => TwoFactorMethod::Email,
            'two_factor_confirmed_at' => now(),
        ]);

        $service = app(EmailTwoFactorService::class);
        $service->generateAndSendCode($user);

        $code = Cache::get("2fa_email_code:{$user->id}");
        expect($code)->not->toBeNull();

        // Manually expire the code
        Cache::forget("2fa_email_code:{$user->id}");

        $result = $service->verify($user, $code);
        expect($result)->toBeFalse();
    });

    it('allows resending the code during challenge', function () {
        Notification::fake();

        $user = User::factory()->create([
            'password' => bcrypt('Password1!'),
            'two_factor_type' => TwoFactorMethod::Email,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => encrypt(json_encode(['code-1'])),
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'Password1!',
        ]);

        $oldCode = Cache::get("2fa_email_code:{$user->id}");

        $response = Livewire::test(TwoFactorChallenge::class)
            ->call('resendCode');

        $response->assertSet('codeResent', true);

        // A new code should have been generated (may or may not be different, but should exist)
        $newCode = Cache::get("2fa_email_code:{$user->id}");
        expect($newCode)->not->toBeNull();
    });

    it('does not require 2FA for users without it enabled', function () {
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
});

describe('Two-Factor Authentication — Email Profile Settings', function () {

    it('can enable email 2FA from profile', function () {
        Notification::fake();

        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Livewire::test(TwoFactorAuthentication::class)
            ->call('enableEmailTwoFactor');

        $component->assertSet('enablingEmail', true);

        Notification::assertSentTo($user, TwoFactorCodeNotification::class);
    });

    it('can confirm email 2FA with valid code', function () {
        Notification::fake();

        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Livewire::test(TwoFactorAuthentication::class)
            ->call('enableEmailTwoFactor');

        $code = Cache::get("2fa_email_code:{$user->id}");

        $component->set('emailCode', $code)
            ->call('confirmEmailTwoFactor');

        $user->refresh();
        expect($user->two_factor_type)->toBe(TwoFactorMethod::Email);
        expect($user->two_factor_confirmed_at)->not->toBeNull();
        expect($user->two_factor_recovery_codes)->not->toBeNull();

        $codes = json_decode(decrypt($user->two_factor_recovery_codes), true);
        expect($codes)->toHaveCount(8);
    });

    it('rejects invalid code when confirming email 2FA', function () {
        Notification::fake();

        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Livewire::test(TwoFactorAuthentication::class)
            ->call('enableEmailTwoFactor')
            ->set('emailCode', '000000')
            ->call('confirmEmailTwoFactor');

        $component->assertHasErrors('emailCode');

        $user->refresh();
        expect($user->two_factor_type)->toBeNull();
        expect($user->two_factor_confirmed_at)->toBeNull();
    });

    it('can disable email 2FA', function () {
        $user = User::factory()->create([
            'two_factor_type' => TwoFactorMethod::Email,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => encrypt(json_encode(['code-1'])),
        ]);

        $this->actingAs($user);

        Livewire::test(TwoFactorAuthentication::class)
            ->call('disableEmailTwoFactor');

        $user->refresh();
        expect($user->two_factor_type)->toBeNull();
        expect($user->two_factor_confirmed_at)->toBeNull();
        expect($user->two_factor_recovery_codes)->toBeNull();
    });

    it('shows email 2FA enabled status on profile', function () {
        $user = User::factory()->create([
            'two_factor_type' => TwoFactorMethod::Email,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => encrypt(json_encode(['code-1'])),
        ]);

        $this->actingAs($user);

        Livewire::test(TwoFactorAuthentication::class)
            ->assertSee(__('profile.twofa_email_enabled_status'));
    });

    it('can resend code during email 2FA setup', function () {
        Notification::fake();

        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Livewire::test(TwoFactorAuthentication::class)
            ->call('enableEmailTwoFactor')
            ->call('resendEmailCode');

        $component->assertSet('emailCodeResent', true);

        Notification::assertSentTo($user, TwoFactorCodeNotification::class, 2);
    });

    it('shows method choice when no 2FA is enabled', function () {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(TwoFactorAuthentication::class)
            ->assertSee(__('profile.twofa_method_totp'))
            ->assertSee(__('profile.twofa_method_email'));
    });
});
