<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

    {{-- Page heading --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                {{ __('admin.sessions_heading') }}
            </h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                {{ __('admin.sessions_subtitle') }}
            </p>
        </div>
        <a
            href="{{ route('admin.refunds.index') }}"
            class="inline-flex items-center gap-1.5 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
            wire:navigate
        >
            {{ __('admin.refunds_heading') }} &rarr;
        </a>
    </div>

    {{-- Filter bar --}}
    <div class="mb-6 rounded-lg bg-white p-4 shadow dark:bg-gray-800">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">

            {{-- Status --}}
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                    {{ __('admin.sessions_filter_status') }}
                </label>
                <select
                    wire:model.live="status"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                >
                    <option value="">{{ __('admin.sessions_filter_all_statuses') }}</option>
                    @foreach ($statuses as $s)
                        <option value="{{ $s->value }}">{{ $s->label() }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Coach name --}}
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                    {{ __('admin.sessions_filter_coach') }}
                </label>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="coachSearch"
                    placeholder="{{ __('admin.sessions_filter_coach_placeholder') }}"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                />
            </div>

            {{-- Activity type --}}
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                    {{ __('admin.sessions_filter_activity_type') }}
                </label>
                <select
                    wire:model.live="activityType"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                >
                    <option value="">{{ __('admin.sessions_filter_all_types') }}</option>
                    @foreach ($activityTypes as $type)
                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Date from --}}
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                    {{ __('admin.sessions_filter_date_from') }}
                </label>
                <input
                    type="date"
                    wire:model.live="dateFrom"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                />
            </div>

            {{-- Date to --}}
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                    {{ __('admin.sessions_filter_date_to') }}
                </label>
                <input
                    type="date"
                    wire:model.live="dateTo"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                />
            </div>

            {{-- Pending payment only --}}
            <div class="flex items-end">
                <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input
                        type="checkbox"
                        wire:model.live="pendingPaymentOnly"
                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600"
                    />
                    {{ __('admin.sessions_filter_pending_payment') }}
                </label>
            </div>

            {{-- Past end time only --}}
            <div class="flex items-end">
                <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input
                        type="checkbox"
                        wire:model.live="pastEndTimeOnly"
                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600"
                    />
                    {{ __('admin.sessions_filter_past_end_time') }}
                </label>
            </div>

            {{-- Reset filters --}}
            <div class="flex items-end">
                <button
                    type="button"
                    wire:click="resetFilters"
                    class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                >
                    {{ __('admin.sessions_filter_reset') }}
                </button>
            </div>

        </div>
    </div>

    {{-- Session list --}}
    @if ($sessions->isEmpty())
        <div class="rounded-lg bg-white p-8 text-center shadow dark:bg-gray-800">
            <p class="text-gray-500 dark:text-gray-400">{{ __('admin.sessions_no_results') }}</p>
        </div>
    @else

        {{-- Desktop table (hidden on mobile) --}}
        <div class="hidden overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800 lg:block">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('admin.sessions_col_title') }}
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('admin.sessions_col_coach') }}
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('admin.sessions_col_activity') }}
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('admin.sessions_col_datetime') }}
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('admin.sessions_col_status') }}
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('admin.sessions_col_participants') }}
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('admin.sessions_col_confirmed_bookings') }}
                        </th>
                        <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('admin.sessions_col_pending_bookings') }}
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('admin.sessions_col_actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($sessions as $session)
                        <tr wire:key="session-{{ $session->id }}" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">

                            {{-- Title --}}
                            <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ $session->title }}
                            </td>

                            {{-- Coach --}}
                            <td class="px-4 py-3 text-sm">
                                <div class="font-medium text-gray-900 dark:text-gray-100">
                                    {{ $session->coach?->name ?? '—' }}
                                </div>
                                @if ($session->coach?->coachProfile?->isStripeReady())
                                    <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-200">
                                        {{ __('admin.sessions_stripe_ready') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900 dark:text-red-200">
                                        {{ __('admin.sessions_stripe_not_ready') }}
                                    </span>
                                @endif
                            </td>

                            {{-- Activity --}}
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                {{ $session->activity_type?->label() ?? '—' }}
                            </td>

                            {{-- Date / Time --}}
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                <div>{{ $session->date->format('d/m/Y') }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ substr($session->start_time, 0, 5) }}–{{ substr($session->end_time, 0, 5) }}
                                </div>
                            </td>

                            {{-- Status badge --}}
                            <td class="px-4 py-3 text-sm">
                                @include('livewire.admin.sessions._status-badge', ['session' => $session])
                            </td>

                            {{-- Participants --}}
                            <td class="px-4 py-3 text-center text-sm text-gray-700 dark:text-gray-300">
                                {{ $session->current_participants }}/{{ $session->max_participants }}
                            </td>

                            {{-- Confirmed bookings --}}
                            <td class="px-4 py-3 text-center text-sm text-gray-700 dark:text-gray-300">
                                {{ $session->bookings_confirmed_count }}
                            </td>

                            {{-- Pending bookings --}}
                            <td class="px-4 py-3 text-center text-sm">
                                @if ($session->bookings_pending_count > 0)
                                    <span class="font-semibold text-yellow-600 dark:text-yellow-400">
                                        {{ $session->bookings_pending_count }}
                                    </span>
                                @else
                                    <span class="text-gray-400">0</span>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="px-4 py-3 text-right">
                                @include('livewire.admin.sessions._actions', ['session' => $session])
                            </td>
                        </tr>

                        {{-- Inline cancel form row (desktop) --}}
                        @if ($cancellingSessionId === $session->id)
                            <tr wire:key="cancel-form-{{ $session->id }}">
                                <td colspan="9" class="px-4 pb-4">
                                    @include('livewire.admin.sessions._cancel-form', ['session' => $session])
                                </td>
                            </tr>
                        @endif

                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Mobile card stack --}}
        <div class="space-y-4 lg:hidden">
            @foreach ($sessions as $session)
                <div
                    wire:key="mobile-session-{{ $session->id }}"
                    class="rounded-lg bg-white p-4 shadow dark:bg-gray-800"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex-1 min-w-0">
                            <p class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100">
                                {{ $session->title }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $session->coach?->name ?? '—' }}
                                @if ($session->coach?->coachProfile?->isStripeReady())
                                    · <span class="text-green-600 dark:text-green-400">{{ __('admin.sessions_stripe_ready') }}</span>
                                @else
                                    · <span class="text-red-600 dark:text-red-400">{{ __('admin.sessions_stripe_not_ready') }}</span>
                                @endif
                            </p>
                        </div>
                        <div class="ml-3 flex-shrink-0">
                            @include('livewire.admin.sessions._status-badge', ['session' => $session])
                        </div>
                    </div>

                    <dl class="mt-3 grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-400">
                        <div>
                            <dt class="font-medium">{{ __('admin.sessions_col_activity') }}</dt>
                            <dd>{{ $session->activity_type?->label() ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium">{{ __('admin.sessions_col_datetime') }}</dt>
                            <dd>{{ $session->date->format('d/m/Y') }} {{ substr($session->start_time, 0, 5) }}–{{ substr($session->end_time, 0, 5) }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium">{{ __('admin.sessions_col_participants') }}</dt>
                            <dd>{{ $session->current_participants }}/{{ $session->max_participants }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium">{{ __('admin.sessions_col_confirmed_bookings') }}/{{ __('admin.sessions_col_pending_bookings') }}</dt>
                            <dd>{{ $session->bookings_confirmed_count }} / {{ $session->bookings_pending_count }}</dd>
                        </div>
                    </dl>

                    <div class="mt-3">
                        @include('livewire.admin.sessions._actions', ['session' => $session])
                    </div>

                    {{-- Inline cancel form (mobile) --}}
                    @if ($cancellingSessionId === $session->id)
                        <div class="mt-3">
                            @include('livewire.admin.sessions._cancel-form', ['session' => $session])
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $sessions->links() }}
        </div>

    @endif
</div>
