<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Users;

use App\Enums\UserRole;
use App\Livewire\Forms\AdminUserCreateForm;
use App\Models\User;
use App\Notifications\AdminUserOnboardingNotification;
use App\Services\UserAdminService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Password;
use Livewire\Component;

final class Create extends Component
{
    public AdminUserCreateForm $form;

    public function mount(): void
    {
        Gate::authorize('createPrivileged', User::class);
    }

    public function save(UserAdminService $service): void
    {
        Gate::authorize('createPrivileged', User::class);

        $this->form->validate();

        $user = $service->create(
            $this->form->name,
            $this->form->email,
            UserRole::from($this->form->role),
        );

        $token = Password::broker()->createToken($user);
        $resetUrl = url(route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ], false));

        $user->notify(new AdminUserOnboardingNotification($resetUrl));

        $this->dispatch('notify', type: 'success', message: __('admin.user_created'));

        $this->redirect(route('admin.users.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.users.create', [
            'availableRoles' => [
                UserRole::Accountant->value => __('common.roles.accountant'),
                UserRole::Admin->value => __('common.roles.admin'),
            ],
        ])->title(__('admin.create_user_title'));
    }
}
