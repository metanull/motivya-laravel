<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\SportSession;
use App\Models\User;
use App\Policies\InvoicePolicy;
use App\Policies\SessionPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(SportSession::class, SessionPolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);

        Gate::define('apply-as-coach', function (User $user): bool {
            return $user->role === UserRole::Athlete
                && $user->coachProfile === null;
        });

        Gate::define('access-admin-panel', function (User $user): bool {
            return $user->role === UserRole::Admin;
        });

        Gate::define('access-coach-panel', function (User $user): bool {
            return $user->role === UserRole::Coach;
        });
    }
}
