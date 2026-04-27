<?php

use App\Http\Middleware\EnsureTwoFactorEnabled;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware(['web', 'auth', 'role:admin', '2fa'])
                ->prefix('admin')
                ->name('admin.')
                ->group(base_path('routes/web/admin.php'));

            Route::middleware(['web', 'auth', 'role:coach'])
                ->prefix('coach')
                ->name('coach.')
                ->group(base_path('routes/web/coach.php'));

            Route::middleware(['web', 'auth', 'role:athlete'])
                ->prefix('athlete')
                ->name('athlete.')
                ->group(base_path('routes/web/athlete.php'));

            Route::middleware(['web', 'auth', 'role:accountant,admin', '2fa'])
                ->prefix('accountant')
                ->name('accountant.')
                ->group(base_path('routes/web/accountant.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SetLocale::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'stripe/webhook',
        ]);
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            '2fa' => EnsureTwoFactorEnabled::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
