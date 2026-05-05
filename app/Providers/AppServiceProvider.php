<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\BookingServiceContract;
use App\Contracts\PaymentServiceContract;
use App\Enums\UserRole;
use App\Models\AuditEvent;
use App\Models\Invoice;
use App\Models\SportSession;
use App\Models\User;
use App\Policies\AuditEventPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\SessionPolicy;
use App\Services\Audit\AuditContextResolver;
use App\Services\BookingService;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AuditContextResolver::class);

        // Bind service contracts to their concrete implementations
        $this->app->singleton(BookingServiceContract::class, BookingService::class);
        $this->app->singleton(PaymentServiceContract::class, PaymentService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(SportSession::class, SessionPolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(AuditEvent::class, AuditEventPolicy::class);

        Gate::define('apply-as-coach', function (User $user): bool {
            return $user->role === UserRole::Athlete
                && $user->coachProfile === null;
        });

        Gate::define('access-admin-panel', function (User $user): bool {
            return $user->role === UserRole::Admin;
        });

        Gate::define('access-accountant-panel', function (User $user): bool {
            return in_array($user->role, [UserRole::Accountant, UserRole::Admin], true);
        });

        Gate::define('access-coach-panel', function (User $user): bool {
            return $user->role === UserRole::Coach;
        });

        Gate::define('access-athlete-panel', function (User $user): bool {
            return $user->role === UserRole::Athlete;
        });
    }
}
