<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\CoachProfileStatus;
use App\Enums\UserRole;
use App\Models\AuditEvent;
use App\Models\CoachProfile;
use App\Models\PaymentAnomaly;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class Dashboard extends Component
{
    public function mount(): void
    {
        Gate::authorize('access-admin-panel');
    }

    #[Computed]
    public function pendingCoachCount(): int
    {
        return CoachProfile::where('status', CoachProfileStatus::Pending)->count();
    }

    #[Computed]
    public function totalUsersCount(): int
    {
        return User::count();
    }

    #[Computed]
    public function suspendedUsersCount(): int
    {
        return User::whereNotNull('suspended_at')->count();
    }

    #[Computed]
    public function unverifiedEmailCount(): int
    {
        return User::whereNull('email_verified_at')->count();
    }

    #[Computed]
    public function mfaNotConfiguredCount(): int
    {
        return User::whereIn('role', [UserRole::Admin->value, UserRole::Accountant->value])
            ->whereNull('two_factor_confirmed_at')
            ->count();
    }

    #[Computed]
    public function anomalyCount(): int
    {
        return PaymentAnomaly::where('resolution_status', 'open')->count();
    }

    #[Computed]
    public function recentAuditEventCount(): int
    {
        return AuditEvent::where('occurred_at', '>=', now()->subDays(7))->count();
    }

    public function render(): View
    {
        return view('livewire.admin.dashboard')->title(__('admin.dashboard_title'));
    }
}
