<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

describe('P1: Verification link expiry', function () {

    it('configures verification link to expire in at least 24 hours', function () {
        expect(config('auth.verification.expire'))->toBeGreaterThanOrEqual(1440);
    });

});

describe('P2: Expired/invalid verification link recovery', function () {

    it('redirects to verification notice when link is expired', function () {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->subMinute(),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $this->actingAs($user)
            ->get($url)
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHas('status', __('auth.verify_link_expired'));
    });

    it('redirects to verification notice when link signature is invalid', function () {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addHour(),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        // Tamper the URL to make it invalid
        $url = $url.'&tampered=1';

        $this->actingAs($user)
            ->get($url)
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHas('status', __('auth.verify_link_invalid'));
    });

    it('shows the status message after redirect from expired link', function () {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->subMinute(),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $this->actingAs($user)
            ->followingRedirects()
            ->get($url)
            ->assertSee(__('auth.verify_link_expired'));
    });

});

describe('P4: Unverified email banner in layout', function () {

    it('shows the unverified email banner for authenticated unverified users', function () {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get('/')
            ->assertSee(route('verification.notice'));
    });

    it('does not show the banner for authenticated verified users', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/')
            ->assertDontSee(__('auth.unverified_email_banner', ['url' => route('verification.notice')]), escape: false);
    });

    it('does not show the banner for guests', function () {
        $this->get('/')
            ->assertDontSee(route('verification.notice'));
    });

});
