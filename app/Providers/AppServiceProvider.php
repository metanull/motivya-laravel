<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\SportSession;
use App\Models\User;
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

        Gate::define('apply-as-coach', function (User $user): bool {
            return $user->role === UserRole::Athlete
                && $user->coachProfile === null;
        });

        Gate::define('access-admin-panel', function (User $user): bool {
            return $user->role === UserRole::Admin;
        });
    }
}
