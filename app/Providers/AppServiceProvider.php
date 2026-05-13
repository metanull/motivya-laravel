<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\BookingServiceContract;
use App\Contracts\Maps\AddressValidationProviderContract;
use App\Contracts\Maps\DirectionsUrlProviderContract;
use App\Contracts\Maps\GeocodingProviderContract;
use App\Contracts\Maps\HealthProbeProviderContract;
use App\Contracts\Maps\MapRenderConfigProviderContract;
use App\Contracts\PaymentServiceContract;
use App\Enums\MapProvider;
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
use App\Services\Maps\Free\FreeAddressValidationProvider;
use App\Services\Maps\Free\FreeDirectionsUrlProvider;
use App\Services\Maps\Free\FreeGeocodingProvider;
use App\Services\Maps\Free\FreeHealthProbeProvider;
use App\Services\Maps\Free\FreeMapRenderConfigProvider;
use App\Services\Maps\Google\GoogleAddressValidationProvider;
use App\Services\Maps\Google\GoogleDirectionsUrlProvider;
use App\Services\Maps\Google\GoogleGeocodingProvider;
use App\Services\Maps\Google\GoogleHealthProbeProvider;
use App\Services\Maps\Google\GoogleMapRenderConfigProvider;
use App\Services\Maps\MapProviderResolver;
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

        // Map provider contracts — resolved dynamically based on GOOGLE_MAPS_API_KEY presence.
        $this->app->singleton(MapProviderResolver::class);

        $this->app->bind(AddressValidationProviderContract::class, function ($app) {
            return $app->make(MapProviderResolver::class)->resolve() === MapProvider::Google
                ? $app->make(GoogleAddressValidationProvider::class)
                : $app->make(FreeAddressValidationProvider::class);
        });

        $this->app->bind(GeocodingProviderContract::class, function ($app) {
            return $app->make(MapProviderResolver::class)->resolve() === MapProvider::Google
                ? $app->make(GoogleGeocodingProvider::class)
                : $app->make(FreeGeocodingProvider::class);
        });

        $this->app->bind(DirectionsUrlProviderContract::class, function ($app) {
            return $app->make(MapProviderResolver::class)->resolve() === MapProvider::Google
                ? $app->make(GoogleDirectionsUrlProvider::class)
                : $app->make(FreeDirectionsUrlProvider::class);
        });

        $this->app->bind(MapRenderConfigProviderContract::class, function ($app) {
            return $app->make(MapProviderResolver::class)->resolve() === MapProvider::Google
                ? $app->make(GoogleMapRenderConfigProvider::class)
                : $app->make(FreeMapRenderConfigProvider::class);
        });

        $this->app->bind(HealthProbeProviderContract::class, function ($app) {
            return $app->make(MapProviderResolver::class)->resolve() === MapProvider::Google
                ? $app->make(GoogleHealthProbeProvider::class)
                : $app->make(FreeHealthProbeProvider::class);
        });
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
