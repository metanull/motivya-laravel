<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\RoleRedirectService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('RoleRedirectService', function () {

    it('redirects admins to admin dashboard', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $service = app(RoleRedirectService::class);

        expect($service->pathFor($admin))->toBe(route('admin.dashboard'));
    });

    it('redirects accountants to accountant dashboard', function () {
        $accountant = User::factory()->accountant()->withTwoFactor()->create();
        $service = app(RoleRedirectService::class);

        expect($service->pathFor($accountant))->toBe(route('accountant.dashboard'));
    });

    it('redirects coaches to coach dashboard', function () {
        $coach = User::factory()->coach()->create();
        $service = app(RoleRedirectService::class);

        expect($service->pathFor($coach))->toBe(route('coach.dashboard'));
    });

    it('redirects athletes to athlete dashboard', function () {
        $athlete = User::factory()->athlete()->create();
        $service = app(RoleRedirectService::class);

        expect($service->pathFor($athlete))->toBe(route('athlete.dashboard'));
    });

});
