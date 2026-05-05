<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

describe('MVP Route Namespace Separation', function () {

    it('admin routes use the admin. prefix', function () {
        expect(Route::has('admin.dashboard'))->toBeTrue();
        expect(Route::has('admin.users.index'))->toBeTrue();
        expect(Route::has('admin.coach-approval'))->toBeTrue();
        expect(Route::has('admin.sessions.index'))->toBeTrue();
        expect(Route::has('admin.refunds.index'))->toBeTrue();
        expect(Route::has('admin.anomalies.index'))->toBeTrue();
        expect(Route::has('admin.configuration.billing'))->toBeTrue();
        expect(Route::has('admin.activity-images'))->toBeTrue();
        expect(Route::has('admin.data-export'))->toBeTrue();
        expect(Route::has('admin.readiness'))->toBeTrue();
    });

    it('accountant routes use the accountant. prefix', function () {
        expect(Route::has('accountant.dashboard'))->toBeTrue();
        expect(Route::has('accountant.transactions.index'))->toBeTrue();
        expect(Route::has('accountant.payout-statements.index'))->toBeTrue();
        expect(Route::has('accountant.anomalies.index'))->toBeTrue();
        expect(Route::has('accountant.invoices.show'))->toBeTrue();
    });

    it('coach routes use the coach. prefix', function () {
        expect(Route::has('coach.dashboard'))->toBeTrue();
        expect(Route::has('coach.profile.edit'))->toBeTrue();
        expect(Route::has('coach.sessions.create'))->toBeTrue();
        expect(Route::has('coach.payout-history'))->toBeTrue();
        expect(Route::has('coach.payout-statements.index'))->toBeTrue();
    });

    it('athlete routes use the athlete. prefix', function () {
        expect(Route::has('athlete.dashboard'))->toBeTrue();
        expect(Route::has('athlete.favourites'))->toBeTrue();
    });

    it('public session routes exist without a role prefix', function () {
        expect(Route::has('sessions.index'))->toBeTrue();
        expect(Route::has('sessions.show'))->toBeTrue();
    });

    it('auth routes are present', function () {
        expect(Route::has('login'))->toBeTrue();
        expect(Route::has('register'))->toBeTrue();
        expect(Route::has('password.request'))->toBeTrue();
    });

    it('admin and accountant do not share route names', function () {
        $adminRoutes = collect(Route::getRoutes()->getRoutesByName())
            ->keys()
            ->filter(fn ($name) => str_starts_with($name, 'admin.'))
            ->values()
            ->all();

        $accountantRoutes = collect(Route::getRoutes()->getRoutesByName())
            ->keys()
            ->filter(fn ($name) => str_starts_with($name, 'accountant.'))
            ->values()
            ->all();

        // No route name should appear in both namespaces
        $overlap = array_intersect($adminRoutes, $accountantRoutes);
        expect($overlap)->toBeEmpty();
    });

});
