<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function () {
    Route::middleware('web')->get('/_test/locale-check', fn () => response()->json([
        'locale' => App::getLocale(),
    ]));
});

describe('SetLocale middleware', function () {

    it('uses the authenticated user locale preference', function () {
        $user = User::factory()->withLocale('nl')->create();

        $response = $this->actingAs($user)
            ->getJson('/_test/locale-check')
            ->assertOk();

        expect($response->json('locale'))->toBe('nl');
    });

    it('prefers user locale over session locale', function () {
        $user = User::factory()->withLocale('en')->create();

        $response = $this->actingAs($user)
            ->withSession(['locale' => 'nl'])
            ->getJson('/_test/locale-check')
            ->assertOk();

        expect($response->json('locale'))->toBe('en');
    });

    it('uses session locale when user is not authenticated', function () {
        $response = $this->withSession(['locale' => 'en'])
            ->getJson('/_test/locale-check')
            ->assertOk();

        expect($response->json('locale'))->toBe('en');
    });

    it('uses Accept-Language header when no user and no session locale', function () {
        $response = $this->getJson('/_test/locale-check', ['Accept-Language' => 'nl'])
            ->assertOk();

        expect($response->json('locale'))->toBe('nl');
    });

    it('falls back to fr when Accept-Language header contains unsupported locale', function () {
        $response = $this->getJson('/_test/locale-check', ['Accept-Language' => 'de'])
            ->assertOk();

        expect($response->json('locale'))->toBe('fr');
    });

    it('falls back to fr when no locale source is available', function () {
        $response = $this->getJson('/_test/locale-check', ['Accept-Language' => '*'])
            ->assertOk();

        expect($response->json('locale'))->toBe('fr');
    });

    it('ignores unsupported locale on authenticated user and falls through', function () {
        $user = User::factory()->create(['locale' => 'de']);

        $response = $this->actingAs($user)
            ->withSession(['locale' => 'en'])
            ->getJson('/_test/locale-check')
            ->assertOk();

        expect($response->json('locale'))->toBe('en');
    });

    it('ignores unsupported session locale and falls through to header', function () {
        $response = $this->withSession(['locale' => 'de'])
            ->getJson('/_test/locale-check', ['Accept-Language' => 'nl'])
            ->assertOk();

        expect($response->json('locale'))->toBe('nl');
    });
});

describe('GET /locale/{locale} switch route', function () {

    it('sets session locale and redirects back', function () {
        $this->get('/locale/en')
            ->assertRedirect('/');

        $response = $this->getJson('/_test/locale-check')
            ->assertOk();

        expect($response->json('locale'))->toBe('en');
    });

    it('updates authenticated user locale in database', function () {
        $user = User::factory()->withLocale('fr')->create();

        $this->actingAs($user)
            ->get('/locale/nl')
            ->assertRedirect('/');

        expect($user->fresh()->locale)->toBe('nl');
    });

    it('sets session locale for authenticated user', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/locale/en')
            ->assertRedirect('/');

        $response = $this->actingAs($user)
            ->getJson('/_test/locale-check')
            ->assertOk();

        expect($response->json('locale'))->toBe('en');
    });

    it('ignores unsupported locale and redirects back without changing session', function () {
        $this->withSession(['locale' => 'fr'])
            ->get('/locale/de')
            ->assertRedirect('/');

        $response = $this->withSession(['locale' => 'fr'])
            ->getJson('/_test/locale-check')
            ->assertOk();

        expect($response->json('locale'))->toBe('fr');
    });

    it('does not update user locale for unsupported locale', function () {
        $user = User::factory()->withLocale('fr')->create();

        $this->actingAs($user)
            ->get('/locale/de')
            ->assertRedirect('/');

        expect($user->fresh()->locale)->toBe('fr');
    });

    it('accepts all three supported locales', function (string $locale) {
        $this->get("/locale/{$locale}")
            ->assertRedirect('/');

        $response = $this->getJson('/_test/locale-check')
            ->assertOk();

        expect($response->json('locale'))->toBe($locale);
    })->with(['fr', 'en', 'nl']);
});
