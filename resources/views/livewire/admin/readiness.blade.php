<div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
        {{ __('admin.readiness_heading') }}
    </h1>
    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
        {{ __('admin.readiness_subtitle') }}
    </p>

    <div class="mt-8 space-y-4">

        @foreach ($this->checks as $key => $check)
            <div class="flex items-start gap-4 rounded-lg bg-white p-4 shadow dark:bg-gray-800">
                {{-- Status dot --}}
                <div class="mt-0.5 flex-shrink-0">
                    @if ($check['status'] === 'green')
                        <span class="inline-block h-4 w-4 rounded-full bg-green-500" title="{{ __('admin.readiness_status_ok') }}"></span>
                    @elseif ($check['status'] === 'yellow')
                        <span class="inline-block h-4 w-4 rounded-full bg-yellow-400" title="{{ __('admin.readiness_status_warning') }}"></span>
                    @else
                        <span class="inline-block h-4 w-4 rounded-full bg-red-500" title="{{ __('admin.readiness_status_error') }}"></span>
                    @endif
                </div>

                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                        {{ __('admin.readiness_check_' . $key) }}
                    </p>
                    <p class="mt-0.5 text-sm text-gray-600 dark:text-gray-400">
                        {{ $check['message'] }}
                    </p>
                </div>

                {{-- Link hint for actionable checks --}}
                @if ($check['status'] !== 'green')
                    @if ($key === 'stripe' && Route::has('admin.configuration.billing'))
                        <a href="{{ route('admin.configuration.billing') }}" wire:navigate
                            class="flex-shrink-0 text-xs font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                            {{ __('admin.readiness_action_billing') }}
                        </a>
                    @elseif ($key === 'admin_mfa' && Route::has('admin.users.index'))
                        <a href="{{ route('admin.users.index') }}" wire:navigate
                            class="flex-shrink-0 text-xs font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                            {{ __('admin.readiness_action_users') }}
                        </a>
                    @elseif ($key === 'accountant' && Route::has('admin.users.index'))
                        <a href="{{ route('admin.users.index') }}" wire:navigate
                            class="flex-shrink-0 text-xs font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                            {{ __('admin.readiness_action_users') }}
                        </a>
                    @elseif ($key === 'activity_images' && Route::has('admin.activity-images'))
                        <a href="{{ route('admin.activity-images') }}" wire:navigate
                            class="flex-shrink-0 text-xs font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                            {{ __('admin.readiness_action_activity_images') }}
                        </a>
                    @elseif ($key === 'billing_config' && Route::has('admin.configuration.billing'))
                        <a href="{{ route('admin.configuration.billing') }}" wire:navigate
                            class="flex-shrink-0 text-xs font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                            {{ __('admin.readiness_action_billing') }}
                        </a>
                    @elseif (in_array($key, ['payment_anomalies']) && Route::has('admin.anomalies.index'))
                        <a href="{{ route('admin.anomalies.index') }}" wire:navigate
                            class="flex-shrink-0 text-xs font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                            {{ __('admin.readiness_action_anomalies') }}
                        </a>
                    @elseif ($key === 'stripe_connect' && Route::has('admin.coach-approval'))
                        <a href="{{ route('admin.coach-approval') }}" wire:navigate
                            class="flex-shrink-0 text-xs font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                            {{ __('admin.readiness_action_coach_approval') }}
                        </a>
                    @endif
                @endif
            </div>
        @endforeach

    </div>

    {{-- Scheduler details --}}
    <div class="mt-10">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            {{ __('admin.readiness_scheduler_detail_heading') }}
        </h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('admin.readiness_scheduler_detail_subtitle') }}
        </p>

        <div class="mt-4 overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-300">
                            {{ __('admin.readiness_scheduler_col_command') }}
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-300">
                            {{ __('admin.readiness_scheduler_col_status') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($this->schedulerChecks as $command => $check)
                        <tr>
                            <td class="px-4 py-3 font-mono text-sm text-gray-700 dark:text-gray-300">
                                {{ $command }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    @if ($check['status'] === 'green')
                                        <span class="inline-block h-3 w-3 rounded-full bg-green-500"></span>
                                    @elseif ($check['status'] === 'yellow')
                                        <span class="inline-block h-3 w-3 rounded-full bg-yellow-400"></span>
                                    @else
                                        <span class="inline-block h-3 w-3 rounded-full bg-red-500"></span>
                                    @endif
                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ $check['message'] }}</span>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Operational repair tools panel --}}
    <div class="mt-10">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            {{ __('admin.readiness_operations_heading') }}
        </h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('admin.readiness_operations_subtitle') }}
        </p>

        <div class="mt-4 space-y-3">
            @foreach ([
                'readiness_operations_scheduler_status'   => 'systemctl status motivya-scheduler.timer && journalctl -u motivya-scheduler.service --since "1 hour ago"',
                'readiness_operations_load_postal_codes'  => 'php artisan geo:load-postal-codes',
                'readiness_operations_backfill_coordinates' => 'php artisan sessions:backfill-coordinates',
                'readiness_operations_reconcile_dry_run'  => 'php artisan payments:reconcile-bookings --dry-run',
                'readiness_operations_reconcile_repair'   => 'php artisan payments:reconcile-bookings --repair',
                'readiness_operations_health_snapshot'    => 'php artisan mvp:health-snapshot',
            ] as $labelKey => $command)
                <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                    <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        {{ __('admin.' . $labelKey) }}
                    </p>
                    <code class="block break-all rounded bg-gray-100 px-3 py-2 font-mono text-sm text-gray-800 dark:bg-gray-900 dark:text-gray-200">
                        {{ $command }}
                    </code>
                </div>
            @endforeach
        </div>
    </div>

    <div class="mt-6">
        <a href="{{ route('admin.dashboard') }}" wire:navigate
            class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
            ← {{ __('admin.readiness_back_to_dashboard') }}
        </a>
    </div>
</div>
