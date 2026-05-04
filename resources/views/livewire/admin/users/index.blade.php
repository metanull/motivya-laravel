<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
            {{ __('admin.users_heading') }}
        </h1>
        <a
            href="{{ route('admin.users.create') }}"
            class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
            wire:navigate
        >
            {{ __('admin.create_user_submit') }}
        </a>
    </div>

    {{-- Filters --}}
    <div class="mt-6 flex flex-wrap gap-4">
        <input
            type="search"
            wire:model.live.debounce.300ms="search"
            placeholder="{{ __('common.form.search') }}"
            class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
        />
        <select
            wire:model.live="roleFilter"
            class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
        >
            <option value="">{{ __('admin.filter_all_roles') }}</option>
            @foreach($roles as $role)
                <option value="{{ $role->value }}">{{ __('common.roles.'.$role->value) }}</option>
            @endforeach
        </select>
        <select
            wire:model.live="statusFilter"
            class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
        >
            <option value="">{{ __('admin.filter_all_statuses') }}</option>
            <option value="active">{{ __('admin.filter_active') }}</option>
            <option value="suspended">{{ __('admin.filter_suspended') }}</option>
        </select>
    </div>

    {{-- Table --}}
    <div class="mt-6 overflow-x-auto rounded-lg shadow">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('common.form.name') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('common.form.email') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('admin.col_role') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('admin.col_email_verified') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('admin.col_mfa') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('admin.col_status') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('admin.col_created_at') }}</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('admin.col_actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                @forelse($users as $user)
                    <tr class="{{ $user->isSuspended() ? 'opacity-60' : '' }}">
                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">
                            {{ $user->name }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                            {{ $user->email }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                            {{ __('common.roles.'.$user->role->value) }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm">
                            @if($user->email_verified_at)
                                <span class="inline-flex rounded-full bg-green-100 px-2 text-xs font-semibold leading-5 text-green-800 dark:bg-green-900 dark:text-green-200">✓</span>
                            @else
                                <span class="inline-flex rounded-full bg-yellow-100 px-2 text-xs font-semibold leading-5 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">✗</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm">
                            @if(in_array($user->role, [\App\Enums\UserRole::Admin, \App\Enums\UserRole::Accountant]))
                                @if($user->two_factor_confirmed_at)
                                    <span class="inline-flex rounded-full bg-green-100 px-2 text-xs font-semibold leading-5 text-green-800 dark:bg-green-900 dark:text-green-200">✓</span>
                                @else
                                    <span class="inline-flex rounded-full bg-red-100 px-2 text-xs font-semibold leading-5 text-red-800 dark:bg-red-900 dark:text-red-200">✗</span>
                                @endif
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm">
                            @if($user->isSuspended())
                                <span class="inline-flex rounded-full bg-red-100 px-2 text-xs font-semibold leading-5 text-red-800 dark:bg-red-900 dark:text-red-200">
                                    {{ __('admin.status_suspended') }}
                                </span>
                            @else
                                <span class="inline-flex rounded-full bg-green-100 px-2 text-xs font-semibold leading-5 text-green-800 dark:bg-green-900 dark:text-green-200">
                                    {{ __('admin.status_active') }}
                                </span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                            {{ $user->created_at->translatedFormat('d/m/Y') }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                            <div class="flex items-center justify-end gap-2">
                                <button
                                    type="button"
                                    wire:click="sendPasswordReset({{ $user->id }})"
                                    class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-200"
                                    title="{{ __('admin.action_reset_password') }}"
                                >
                                    {{ __('admin.action_reset_password') }}
                                </button>
                                <button
                                    type="button"
                                    wire:click="confirmChangeRole({{ $user->id }})"
                                    class="text-yellow-600 hover:text-yellow-900 dark:text-yellow-400 dark:hover:text-yellow-200"
                                >
                                    {{ __('admin.action_change_role') }}
                                </button>
                                @if($user->isSuspended())
                                    <button
                                        type="button"
                                        wire:click="reactivate({{ $user->id }})"
                                        class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-200"
                                    >
                                        {{ __('admin.action_reactivate') }}
                                    </button>
                                @else
                                    <button
                                        type="button"
                                        wire:click="confirmSuspend({{ $user->id }})"
                                        class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-200"
                                    >
                                        {{ __('admin.action_suspend') }}
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                            {{ __('admin.no_users_found') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $users->links() }}
    </div>

    {{-- Suspend modal --}}
    @if($suspendingUserId !== null)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl dark:bg-gray-800">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    {{ __('admin.suspend_confirm_title') }}
                </h2>
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('admin.suspension_reason_label') }}
                    </label>
                    <textarea
                        wire:model="suspensionReason"
                        rows="3"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                        placeholder="{{ __('admin.suspension_reason_placeholder') }}"
                    ></textarea>
                    @error('suspensionReason')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                <div class="mt-4 flex justify-end gap-3">
                    <button
                        type="button"
                        wire:click="cancelSuspend"
                        class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                    >
                        {{ __('common.cancel') }}
                    </button>
                    <button
                        type="button"
                        wire:click="suspend"
                        class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700"
                    >
                        {{ __('admin.action_suspend') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Change role modal --}}
    @if($changingRoleUserId !== null)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl dark:bg-gray-800">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    {{ __('admin.change_role_title') }}
                </h2>
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('admin.col_role') }}
                    </label>
                    <select
                        wire:model="newRole"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                    >
                        <option value="">{{ __('common.form.select') }}</option>
                        @foreach(\App\Enums\UserRole::cases() as $role)
                            @if($role !== \App\Enums\UserRole::Coach)
                                <option value="{{ $role->value }}">{{ __('common.roles.'.$role->value) }}</option>
                            @endif
                        @endforeach
                    </select>
                    @error('newRole')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                <div class="mt-4 flex justify-end gap-3">
                    <button
                        type="button"
                        wire:click="cancelChangeRole"
                        class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                    >
                        {{ __('common.cancel') }}
                    </button>
                    <button
                        type="button"
                        wire:click="changeRole"
                        class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                    >
                        {{ __('common.confirm') }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
