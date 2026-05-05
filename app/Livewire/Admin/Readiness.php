<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\BookingStatus;
use App\Enums\CoachProfileStatus;
use App\Enums\SessionStatus;
use App\Enums\UserRole;
use App\Models\ActivityImage;
use App\Models\Booking;
use App\Models\CoachProfile;
use App\Models\PostalCodeCoordinate;
use App\Models\SchedulerHeartbeat;
use App\Models\SportSession;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class Readiness extends Component
{
    /**
     * Critical scheduled commands and their acceptable recency windows (in minutes).
     * hourly → 120 min, 5-minute → 30 min, monthly → 46080 min (32 days).
     */
    private const CRITICAL_COMMANDS = [
        'sessions:send-reminders' => 120,
        'sessions:cancel-expired' => 120,
        'sessions:complete-finished' => 120,
        'subscriptions:compute-monthly' => 46080,
        'bookings:expire-unpaid' => 30,
    ];

    public function mount(): void
    {
        Gate::authorize('access-admin-panel');
    }

    /**
     * @return array<string, array{status: string, message: string}>
     */
    #[Computed]
    public function checks(): array
    {
        return [
            'stripe' => $this->checkStripe(),
            'mail' => $this->checkMail(),
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'queue' => $this->checkQueue(),
            'scheduler' => $this->checkScheduler(),
            'public_storage' => $this->checkPublicStorage(),
            'postal_code_reference' => $this->checkPostalCodeReference(),
            'session_coordinates' => $this->checkSessionCoordinates(),
            'payment_anomalies' => $this->checkPaymentAnomalies(),
            'stripe_connect' => $this->checkStripeConnect(),
            'admin_mfa' => $this->checkAdminWithMfa(),
            'accountant' => $this->checkAccountant(),
            'activity_images' => $this->checkActivityImages(),
            'billing_config' => $this->checkBillingConfig(),
            'google_maps_key' => $this->checkGoogleMapsKey(),
            'geocoding_cache' => $this->checkGeocodingCache(),
            'public_storage' => $this->checkPublicStorage(),
        ];
    }

    /**
     * @return array<string, array{status: string, message: string}>
     */
    #[Computed]
    public function schedulerChecks(): array
    {
        $results = [];

        foreach (self::CRITICAL_COMMANDS as $command => $windowMinutes) {
            $heartbeat = SchedulerHeartbeat::where('command', $command)->first();

            if ($heartbeat === null) {
                $results[$command] = [
                    'status' => 'red',
                    'message' => __('admin.readiness_scheduler_never_run'),
                ];
            } elseif ($heartbeat->last_run_at->lt(now()->subMinutes($windowMinutes))) {
                $results[$command] = [
                    'status' => 'yellow',
                    'message' => __('admin.readiness_scheduler_stale', [
                        'time' => $heartbeat->last_run_at->diffForHumans(),
                    ]),
                ];
            } else {
                $results[$command] = [
                    'status' => 'green',
                    'message' => __('admin.readiness_scheduler_ok', [
                        'time' => $heartbeat->last_run_at->diffForHumans(),
                    ]),
                ];
            }
        }

        return $results;
    }

    /**
     * @return array{status: string, message: string}
     */
    private function checkStripe(): array
    {
        $key = config('services.stripe.key');
        $secret = config('services.stripe.secret');

        if (empty($key) || empty($secret)) {
            return ['status' => 'red', 'message' => __('admin.readiness_stripe_missing')];
        }

        // Keys must look like Stripe keys (start with pk_ / sk_ or rk_)
        if (! str_starts_with((string) $key, 'pk_') || ! str_starts_with((string) $secret, 'sk_')) {
            return ['status' => 'yellow', 'message' => __('admin.readiness_stripe_unexpected_format')];
        }

        return ['status' => 'green', 'message' => __('admin.readiness_stripe_ok')];
    }

    /**
     * @return array{status: string, message: string}
     */
    private function checkMail(): array
    {
        $mailer = config('mail.default');
        $host = config('mail.mailers.smtp.host');

        if ($mailer === 'log') {
            return ['status' => 'yellow', 'message' => __('admin.readiness_mail_log_driver')];
        }

        if ($mailer === 'smtp' && empty($host)) {
            return ['status' => 'red', 'message' => __('admin.readiness_mail_smtp_missing_host')];
        }

        return ['status' => 'green', 'message' => __('admin.readiness_mail_ok', ['driver' => $mailer])];
    }

    /**
     * @return array{status: string, message: string}
     */
    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();

            return ['status' => 'green', 'message' => __('admin.readiness_database_ok')];
        } catch (\Throwable) {
            return ['status' => 'red', 'message' => __('admin.readiness_database_error')];
        }
    }

    /**
     * @return array{status: string, message: string}
     */
    private function checkCache(): array
    {
        try {
            $driver = config('cache.default');
            Cache::store((string) $driver)->put('readiness-check', true, 10);

            return ['status' => 'green', 'message' => __('admin.readiness_cache_ok', ['driver' => $driver])];
        } catch (\Throwable) {
            return ['status' => 'red', 'message' => __('admin.readiness_cache_error')];
        }
    }

    /**
     * @return array{status: string, message: string}
     */
    private function checkQueue(): array
    {
        try {
            $driver = config('queue.default');

            return ['status' => 'green', 'message' => __('admin.readiness_queue_ok', ['driver' => $driver])];
        } catch (\Throwable) {
            return ['status' => 'red', 'message' => __('admin.readiness_queue_error')];
        }
    }

    /**
     * @return array{status: string, message: string}
     */
    private function checkScheduler(): array
    {
        $schedulerChecks = $this->schedulerChecks;
        $anyRed = collect($schedulerChecks)->contains(fn ($c) => $c['status'] === 'red');
        $anyYellow = collect($schedulerChecks)->contains(fn ($c) => $c['status'] === 'yellow');

        if ($anyRed) {
            return ['status' => 'red', 'message' => __('admin.readiness_scheduler_error')];
        }

        if ($anyYellow) {
            return ['status' => 'yellow', 'message' => __('admin.readiness_scheduler_warning')];
        }

        return ['status' => 'green', 'message' => __('admin.readiness_scheduler_all_ok')];
    }

    /**
     * @return array{status: string, message: string}
     */
    private function checkPublicStorage(): array
    {
        $linkPath = public_path('storage');

        if (! is_link($linkPath)) {
            return ['status' => 'red', 'message' => __('admin.readiness_public_storage_missing')];
        }

        $target = readlink($linkPath);
        if (! is_dir($target !== false ? $target : $linkPath)) {
            return ['status' => 'red', 'message' => __('admin.readiness_public_storage_broken', ['target' => (string) $target])];
        }

        // Verify at least one stored activity image URL is reachable when images exist.
        $image = ActivityImage::first();
        if ($image !== null) {
            $publicUrl = url('storage/'.ltrim($image->path, '/'));
            $localFile = public_path('storage/'.ltrim($image->path, '/'));
            if (! file_exists($localFile)) {
                return ['status' => 'yellow', 'message' => __('admin.readiness_public_storage_image_missing', ['url' => $publicUrl])];
            }
        }

        return ['status' => 'green', 'message' => __('admin.readiness_public_storage_ok')];
    }

    /**
     * @return array{status: string, message: string}
     */
    private function checkPostalCodeReference(): array
    {
        $count = PostalCodeCoordinate::count();

        if ($count === 0) {
            return ['status' => 'red', 'message' => __('admin.readiness_postal_code_reference_missing')];
        }

        return ['status' => 'green', 'message' => __('admin.readiness_postal_code_reference_ok', ['count' => $count])];
    }

    /**
     * @return array{status: string, message: string}
     */
    private function checkSessionCoordinates(): array
    {
        $total = SportSession::count();

        if ($total === 0) {
            return ['status' => 'yellow', 'message' => __('admin.readiness_session_coordinates_no_sessions')];
        }

        $missing = SportSession::whereNull('latitude')->orWhereNull('longitude')->count();

        if ($missing === $total) {
            return ['status' => 'red', 'message' => __('admin.readiness_session_coordinates_all_missing', ['count' => $missing])];
        }

        if ($missing > 0) {
            return ['status' => 'yellow', 'message' => __('admin.readiness_session_coordinates_some_missing', ['missing' => $missing, 'total' => $total])];
        }

        return ['status' => 'green', 'message' => __('admin.readiness_session_coordinates_ok', ['count' => $total])];
    }

    /**
     * @return array{status: string, message: string}
     */
    private function checkPaymentAnomalies(): array
    {
        $count = Booking::where('status', BookingStatus::Confirmed->value)
            ->where('amount_paid', '>', 0)
            ->whereNull('stripe_payment_intent_id')
            ->count();

        if ($count > 0) {
            return ['status' => 'red', 'message' => __('admin.readiness_payment_anomalies_found', ['count' => $count])];
        }

        return ['status' => 'green', 'message' => __('admin.readiness_payment_anomalies_ok')];
    }

    /**
     * @return array{status: string, message: string}
     */
    private function checkStripeConnect(): array
    {
        // Count approved coaches who have published or confirmed sessions
        // but have an incomplete Stripe Connect onboarding.
        $count = CoachProfile::where('status', CoachProfileStatus::Approved->value)
            ->where('stripe_onboarding_complete', false)
            ->whereHas('user.sportSessions', function ($query): void {
                $query->whereIn('status', [SessionStatus::Published->value, SessionStatus::Confirmed->value]);
            })
            ->count();

        if ($count > 0) {
            return ['status' => 'yellow', 'message' => __('admin.readiness_stripe_connect_incomplete', ['count' => $count])];
        }

        return ['status' => 'green', 'message' => __('admin.readiness_stripe_connect_ok')];
    }

    /**
     * @return array{status: string, message: string}
     */
    private function checkAdminWithMfa(): array
    {
        $count = User::where('role', UserRole::Admin->value)
            ->whereNull('suspended_at')
            ->whereNotNull('two_factor_confirmed_at')
            ->count();

        if ($count === 0) {
            return ['status' => 'red', 'message' => __('admin.readiness_admin_mfa_missing')];
        }

        return ['status' => 'green', 'message' => __('admin.readiness_admin_mfa_ok', ['count' => $count])];
    }

    /**
     * @return array{status: string, message: string}
     */
    private function checkAccountant(): array
    {
        $count = User::where('role', UserRole::Accountant->value)
            ->whereNull('suspended_at')
            ->count();

        if ($count === 0) {
            return ['status' => 'red', 'message' => __('admin.readiness_accountant_missing')];
        }

        return ['status' => 'green', 'message' => __('admin.readiness_accountant_ok', ['count' => $count])];
    }

    /**
     * @return array{status: string, message: string}
     */
    private function checkActivityImages(): array
    {
        $count = ActivityImage::count();

        if ($count === 0) {
            return ['status' => 'yellow', 'message' => __('admin.readiness_activity_images_missing')];
        }

        return ['status' => 'green', 'message' => __('admin.readiness_activity_images_ok', ['count' => $count])];
    }

    /**
     * @return array{status: string, message: string}
     */
    private function checkBillingConfig(): array
    {
        if (! Route::has('admin.configuration.billing')) {
            return ['status' => 'red', 'message' => __('admin.readiness_billing_config_missing')];
        }

        return ['status' => 'green', 'message' => __('admin.readiness_billing_config_ok')];
    }

    /**
     * @return array{status: string, message: string}
     */
    private function checkGoogleMapsKey(): array
    {
        $key = config('maps.google_api_key');

        if (empty($key)) {
            return ['status' => 'yellow', 'message' => __('admin.readiness_google_maps_key_missing')];
        }

        if (! str_starts_with((string) $key, 'AIza')) {
            return ['status' => 'yellow', 'message' => __('admin.readiness_google_maps_key_format')];
        }

        return ['status' => 'green', 'message' => __('admin.readiness_google_maps_key_ok')];
    }

    /**
     * @return array{status: string, message: string}
     */
    private function checkGeocodingCache(): array
    {
        try {
            DB::table('geocoding_cache')->count();

            return ['status' => 'green', 'message' => __('admin.readiness_geocoding_cache_ok')];
        } catch (\Throwable) {
            return ['status' => 'red', 'message' => __('admin.readiness_geocoding_cache_missing')];
        }
    }

    /**
     * @return array{status: string, message: string}
     */
    private function checkPublicStorage(): array
    {
        $linkPath = public_path('storage');

        if (! file_exists($linkPath)) {
            return ['status' => 'red', 'message' => __('admin.readiness_public_storage_missing')];
        }

        if (! is_link($linkPath)) {
            return ['status' => 'yellow', 'message' => __('admin.readiness_public_storage_not_symlink')];
        }

        return ['status' => 'green', 'message' => __('admin.readiness_public_storage_ok')];
    }

    public function render(): View
    {
        return view('livewire.admin.readiness')
            ->title(__('admin.readiness_title'));
    }
}
