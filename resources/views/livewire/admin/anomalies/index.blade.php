<div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">

    <div class="mb-4">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
            {{ __('admin.anomalies_heading') }}
        </h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            {{ __('admin.anomalies_subtitle') }}
        </p>
    </div>

    {{-- Type filter --}}
    <div class="mb-4">
        <select
            wire:model.live="filterType"
            class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
            <option value="">{{ __('admin.anomalies_filter_all_types') }}</option>
            @foreach ($this->typeOptions() as $option)
                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
            @endforeach
        </select>
    </div>

    {{-- Resolve Modal --}}
    @if ($resolvingAnomalyId !== null)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl dark:bg-gray-800">
                <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">
                    {{ __('admin.anomalies_confirm_resolve') }}
                </h2>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('admin.anomalies_resolve_reason_label') }}
                </label>
                <textarea
                    wire:model="resolveReason"
                    rows="3"
                    class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"
                    placeholder="{{ __('admin.anomalies_resolve_reason_placeholder') }}"></textarea>
                @error('resolveReason') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                <div class="mt-4 flex justify-end gap-3">
                    <button wire:click="$set('resolvingAnomalyId', null)"
                        class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300">
                        Cancel
                    </button>
                    <button wire:click="resolve({{ $resolvingAnomalyId }})"
                        class="rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-500">
                        {{ __('admin.anomalies_action_resolve') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Ignore Modal --}}
    @if ($ignoringAnomalyId !== null)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl dark:bg-gray-800">
                <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">
                    {{ __('admin.anomalies_confirm_ignore') }}
                </h2>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('admin.anomalies_ignore_reason_label') }}
                </label>
                <textarea
                    wire:model="ignoreReason"
                    rows="3"
                    class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"
                    placeholder="{{ __('admin.anomalies_ignore_reason_placeholder') }}"></textarea>
                @error('ignoreReason') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                <div class="mt-4 flex justify-end gap-3">
                    <button wire:click="$set('ignoringAnomalyId', null)"
                        class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300">
                        Cancel
                    </button>
                    <button wire:click="ignore({{ $ignoringAnomalyId }})"
                        class="rounded-md bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-500">
                        {{ __('admin.anomalies_action_ignore') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Anomalies Table --}}
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('admin.anomalies_col_type') }}
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('admin.anomalies_col_description') }}
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('admin.anomalies_col_recommended_action') }}
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('admin.anomalies_col_actions') }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($this->anomalies as $anomaly)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                        <td class="px-4 py-3 text-sm">
                            <span class="inline-flex items-center rounded-full bg-orange-100 px-2.5 py-0.5 text-xs font-medium text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                                {{ $anomaly->anomaly_type->label() }}
                            </span>
                            @if ($anomaly->coach)
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $anomaly->coach->name }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                            {{ $anomaly->description }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                            {{ $anomaly->recommended_action }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                            <div class="flex items-center justify-end gap-2">
                                <button
                                    wire:click="openResolveModal({{ $anomaly->id }})"
                                    class="rounded-md bg-green-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-green-500">
                                    {{ __('admin.anomalies_action_resolve') }}
                                </button>
                                <button
                                    wire:click="openIgnoreModal({{ $anomaly->id }})"
                                    class="rounded-md bg-gray-500 px-2.5 py-1 text-xs font-semibold text-white hover:bg-gray-400">
                                    {{ __('admin.anomalies_action_ignore') }}
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                            {{ __('admin.anomalies_no_results') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if ($this->anomalies->hasPages())
            <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700">
                {{ $this->anomalies->links() }}
            </div>
        @endif
    </div>

</div>
