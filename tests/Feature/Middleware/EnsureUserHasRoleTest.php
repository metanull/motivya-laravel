<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

describe('EnsureUserHasRole middleware', function () {

    beforeEach(function () {
        // Route protected by both auth and role middleware (authenticated tests)
        Route::middleware(['auth', 'role:coach'])->get('/_test/coach-only', fn () => response('ok'));
        Route::middleware(['auth', 'role:admin,accountant'])->get('/_test/admin-or-accountant', fn () => response('ok'));

        // Route protected by role middleware only (no auth), to test unauthenticated behaviour
        // directly on the EnsureUserHasRole middleware without auth middleware interference.
        Route::middleware(['role:coach'])->get('/_test/role-only-coach', fn () => response('ok'));
    });

    it('allows access when the user has the required role', function () {
        $coach = User::factory()->coach()->create();

        $this->actingAs($coach)
            ->get('/_test/coach-only')
            ->assertOk();
    });

    it('returns 403 when the user has a different role', function () {
        $athlete = User::factory()->athlete()->create();

        $this->actingAs($athlete)
            ->get('/_test/coach-only')
            ->assertForbidden();
    });

    it('allows access when the user matches one of multiple allowed roles', function () {
        $admin = User::factory()->admin()->create();
        $accountant = User::factory()->accountant()->create();

        $this->actingAs($admin)
            ->get('/_test/admin-or-accountant')
            ->assertOk();

        $this->actingAs($accountant)
            ->get('/_test/admin-or-accountant')
            ->assertOk();
    });

    it('returns 403 when the user does not match any of multiple allowed roles', function () {
        $athlete = User::factory()->athlete()->create();

        $this->actingAs($athlete)
            ->get('/_test/admin-or-accountant')
            ->assertForbidden();
    });

    it('returns 403 when no user is authenticated', function () {
        // Uses a route with only the role middleware so we test EnsureUserHasRole
        // directly; the built-in auth middleware would redirect to login instead.
        $this->get('/_test/role-only-coach')
            ->assertForbidden();
    });

});
