<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Users;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\UserAdminService;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

final class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $roleFilter = '';

    #[Url]
    public string $statusFilter = '';

    public ?int $suspendingUserId = null;

    public string $suspensionReason = '';

    public ?int $changingRoleUserId = null;

    public string $newRole = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', User::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRoleFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function sendPasswordReset(int $userId): void
    {
        Gate::authorize('viewAny', User::class);

        $user = User::findOrFail($userId);

        Password::broker()->sendResetLink(['email' => $user->email]);

        $this->dispatch('notify', type: 'success', message: __('admin.password_reset_sent'));
    }

    public function confirmSuspend(int $userId): void
    {
        $this->suspendingUserId = $userId;
        $this->suspensionReason = '';
    }

    public function cancelSuspend(): void
    {
        $this->suspendingUserId = null;
        $this->suspensionReason = '';
    }

    public function suspend(UserAdminService $service): void
    {
        Gate::authorize('viewAny', User::class);

        $this->validate([
            'suspensionReason' => ['required', 'string', 'max:500'],
        ]);

        if ($this->suspendingUserId === null) {
            return;
        }

        $user = User::findOrFail($this->suspendingUserId);
        Gate::authorize('suspend', $user);

        $service->suspend($user, $this->suspensionReason);

        $this->suspendingUserId = null;
        $this->suspensionReason = '';

        $this->dispatch('notify', type: 'success', message: __('admin.user_suspended'));
    }

    public function reactivate(int $userId, UserAdminService $service): void
    {
        Gate::authorize('viewAny', User::class);

        $user = User::findOrFail($userId);
        Gate::authorize('reactivate', $user);

        $service->reactivate($user);

        $this->dispatch('notify', type: 'success', message: __('admin.user_reactivated'));
    }

    public function confirmChangeRole(int $userId): void
    {
        $this->changingRoleUserId = $userId;
        $this->newRole = '';
    }

    public function cancelChangeRole(): void
    {
        $this->changingRoleUserId = null;
        $this->newRole = '';
    }

    public function changeRole(UserAdminService $service): void
    {
        Gate::authorize('viewAny', User::class);

        $this->validate([
            'newRole' => ['required', Rule::in(
                array_map(fn (UserRole $r) => $r->value, UserRole::cases()),
            )],
        ]);

        if ($this->changingRoleUserId === null) {
            return;
        }

        $user = User::findOrFail($this->changingRoleUserId);
        Gate::authorize('changeRole', $user);

        $targetRole = UserRole::from($this->newRole);

        // Block direct Coach assignment; coach role must come from approveCoach()
        if ($targetRole === UserRole::Coach) {
            $this->addError('newRole', __('admin.role_change_coach_blocked'));

            return;
        }

        // Protect the last active admin from demotion
        if ($user->role === UserRole::Admin && $targetRole !== UserRole::Admin) {
            $activeAdminCount = User::where('role', UserRole::Admin)
                ->whereNull('suspended_at')
                ->count();

            if ($activeAdminCount <= 1) {
                $this->addError('newRole', __('admin.role_change_last_admin'));

                return;
            }
        }

        $service->changeRole($user, $targetRole);

        $this->changingRoleUserId = null;
        $this->newRole = '';

        $this->dispatch('notify', type: 'success', message: __('admin.role_changed'));
    }

    public function isMfaRequired(UserRole $role): bool
    {
        return in_array($role, [UserRole::Admin, UserRole::Accountant], true);
    }

    /**
     * Roles that can be assigned via this UI (Coach is excluded — must use coach-approval flow).
     *
     * @return array<string, string>
     */
    public function assignableRoles(): array
    {
        return collect(UserRole::cases())
            ->reject(fn (UserRole $r) => $r === UserRole::Coach)
            ->mapWithKeys(fn (UserRole $r) => [$r->value => __('common.roles.'.$r->value)])
            ->all();
    }

    /**
     * @return LengthAwarePaginator<User>
     */
    private function users(): LengthAwarePaginator
    {
        return User::query()
            ->when($this->search !== '', function ($q): void {
                $term = '%'.addcslashes($this->search, '%_').'%';
                $q->where(function ($q2) use ($term): void {
                    $q2->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            })
            ->when($this->roleFilter !== '', fn ($q) => $q->where('role', UserRole::from($this->roleFilter)))
            ->when($this->statusFilter === 'suspended', fn ($q) => $q->whereNotNull('suspended_at'))
            ->when($this->statusFilter === 'active', fn ($q) => $q->whereNull('suspended_at'))
            ->latest()
            ->paginate(20);
    }

    public function render(): View
    {
        return view('livewire.admin.users.index', [
            'users' => $this->users(),
            'roles' => UserRole::cases(),
            'assignableRoles' => $this->assignableRoles(),
        ])->title(__('admin.users_title'));
    }
}
