<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Users;

use App\Enums\UserRole;
use App\Livewire\Forms\AdminUserCreateForm;
use App\Models\User;
use App\Notifications\AdminUserOnboardingNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Livewire\Component;

final class Create extends Component
{
    public AdminUserCreateForm $form;

    public function mount(): void
    {
        Gate::authorize('createPrivileged', User::class);
    }

    public function save(): void
    {
        Gate::authorize('createPrivileged', User::class);

        $this->form->validate();

        $user = User::create([
            'name' => $this->form->name,
            'email' => $this->form->email,
            'password' => bcrypt(Str::random(32)),
            'role' => UserRole::from($this->form->role),
        ]);

        // Mark email as verified — admin-created users don't need to verify themselves
        $user->forceFill(['email_verified_at' => now()])->save();

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
