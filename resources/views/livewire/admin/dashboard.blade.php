<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
        {{ __('admin.dashboard_heading') }}
    </h1>
    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
        {{ __('admin.dashboard_subtitle') }}
    </p>

    <div class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">

        {{-- Pending Coach Applications --}}
        <a
            href="{{ route('admin.coach-approval') }}"
            class="block rounded-lg bg-white p-6 shadow transition hover:shadow-md dark:bg-gray-800"
            wire:navigate
        >
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    {{ __('admin.dashboard_card_coach_approval') }}
                </h2>
                @if($this->pendingCoachCount > 0)
                    <span class="inline-flex items-center justify-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-sm font-semibold text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                        {{ $this->pendingCoachCount }}
                    </span>
                @endif
            </div>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                {{ __('admin.dashboard_card_coach_approval_desc') }}
            </p>
        </a>

        {{-- User Management --}}
        <a
            href="{{ route('admin.users.index') }}"
            class="block rounded-lg bg-white p-6 shadow transition hover:shadow-md dark:bg-gray-800"
            wire:navigate
        >
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    {{ __('admin.dashboard_card_users') }}
                </h2>
                <span class="inline-flex items-center justify-center rounded-full bg-indigo-100 px-2.5 py-0.5 text-sm font-semibold text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">
                    {{ $this->totalUsersCount }}
                </span>
            </div>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                {{ __('admin.dashboard_card_users_desc') }}
            </p>
            @if($this->suspendedUsersCount > 0 || $this->mfaNotConfiguredCount > 0 || $this->unverifiedEmailCount > 0)
                <ul class="mt-3 space-y-1 text-xs text-gray-500 dark:text-gray-400">
                    @if($this->suspendedUsersCount > 0)
                        <li>{{ __('admin.dashboard_suspended_count', ['count' => $this->suspendedUsersCount]) }}</li>
                    @endif
                    @if($this->mfaNotConfiguredCount > 0)
                        <li>{{ __('admin.dashboard_mfa_missing_count', ['count' => $this->mfaNotConfiguredCount]) }}</li>
                    @endif
                    @if($this->unverifiedEmailCount > 0)
                        <li>{{ __('admin.dashboard_unverified_count', ['count' => $this->unverifiedEmailCount]) }}</li>
                    @endif
                </ul>
            @endif
        </a>

        {{-- Activity Images --}}
        <a
            href="{{ route('admin.activity-images') }}"
            class="block rounded-lg bg-white p-6 shadow transition hover:shadow-md dark:bg-gray-800"
            wire:navigate
        >
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                {{ __('admin.dashboard_card_activity_images') }}
            </h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                {{ __('admin.dashboard_card_activity_images_desc') }}
            </p>
        </a>

        {{-- Data Export --}}
        <a
            href="{{ route('admin.data-export') }}"
            class="block rounded-lg bg-white p-6 shadow transition hover:shadow-md dark:bg-gray-800"
            wire:navigate
        >
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                {{ __('admin.dashboard_card_data_export') }}
            </h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                {{ __('admin.dashboard_card_data_export_desc') }}
            </p>
        </a>

        {{-- Billing Configuration --}}
        <a
            href="{{ route('admin.configuration.billing') }}"
            class="block rounded-lg bg-white p-6 shadow transition hover:shadow-md dark:bg-gray-800"
            wire:navigate
        >
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                {{ __('admin.dashboard_card_billing_config') }}
            </h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                {{ __('admin.dashboard_card_billing_config_desc') }}
            </p>
        </a>

        {{-- Session Supervision --}}
        <a
            href="{{ route('admin.sessions.index') }}"
            class="block rounded-lg bg-white p-6 shadow transition hover:shadow-md dark:bg-gray-800"
            wire:navigate
        >
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                {{ __('admin.dashboard_card_sessions') }}
            </h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                {{ __('admin.dashboard_card_sessions_desc') }}
            </p>
        </a>

        {{-- Exceptional Refunds --}}
        <a
            href="{{ route('admin.refunds.index') }}"
            class="block rounded-lg bg-white p-6 shadow transition hover:shadow-md dark:bg-gray-800"
            wire:navigate
        >
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                {{ __('admin.dashboard_card_refunds') }}
            </h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                {{ __('admin.dashboard_card_refunds_desc') }}
            </p>
        </a>

        {{-- Payment Anomalies --}}
        @if (Route::has('admin.anomalies.index'))
            <a
                href="{{ route('admin.anomalies.index') }}"
                class="block rounded-lg bg-white p-6 shadow transition hover:shadow-md dark:bg-gray-800"
                wire:navigate
            >
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        {{ __('admin.dashboard_card_anomalies') }}
                    </h2>
                    @if ($this->anomalyCount > 0)
                        <span class="inline-flex items-center justify-center rounded-full bg-orange-100 px-2.5 py-0.5 text-sm font-semibold text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                            {{ $this->anomalyCount }}
                        </span>
                    @endif
                </div>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('admin.dashboard_card_anomalies_desc') }}
                </p>
            </a>
        @endif

    </div>
</div>
