<div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">

    <div class="mb-4">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
            {{ __('admin.audit_events_heading') }}
        </h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            {{ __('admin.audit_events_subtitle') }}
        </p>
    </div>

    {{-- Filters --}}
    <div class="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">

        <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('admin.audit_events_filter_occurred_from') }}</label>
            <input type="date" wire:model.live="occurredFrom"
                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('admin.audit_events_filter_occurred_to') }}</label>
            <input type="date" wire:model.live="occurredTo"
                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('admin.audit_events_filter_event_type') }}</label>
            <select wire:model.live="eventType"
                class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                <option value="">{{ __('admin.audit_events_filter_all') }}</option>
                @foreach ($this->eventTypeOptions() as $type)
                    <option value="{{ $type->value }}">{{ $type->value }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('admin.audit_events_filter_operation') }}</label>
            <select wire:model.live="operation"
                class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                <option value="">{{ __('admin.audit_events_filter_all') }}</option>
                @foreach ($this->operationOptions() as $op)
                    <option value="{{ $op->value }}">{{ $op->value }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('admin.audit_events_filter_actor_type') }}</label>
            <select wire:model.live="actorType"
                class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                <option value="">{{ __('admin.audit_events_filter_all') }}</option>
                @foreach ($this->actorTypeOptions() as $at)
                    <option value="{{ $at->value }}">{{ $at->value }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('admin.audit_events_filter_actor_id') }}</label>
            <input type="text" wire:model.live.debounce.300ms="actorId"
                placeholder="{{ __('admin.audit_events_filter_actor_id_placeholder') }}"
                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('admin.audit_events_filter_actor_role') }}</label>
            <select wire:model.live="actorRole"
                class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                <option value="">{{ __('admin.audit_events_filter_all') }}</option>
                @foreach ($this->actorRoleOptions() as $role)
                    <option value="{{ $role->value }}">{{ $role->value }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('admin.audit_events_filter_source') }}</label>
            <select wire:model.live="source"
                class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                <option value="">{{ __('admin.audit_events_filter_all') }}</option>
                @foreach ($this->sourceOptions() as $src)
                    <option value="{{ $src->value }}">{{ $src->value }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('admin.audit_events_filter_model_type') }}</label>
            <input type="text" wire:model.live.debounce.300ms="modelType"
                placeholder="{{ __('admin.audit_events_filter_model_type_placeholder') }}"
                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('admin.audit_events_filter_model_id') }}</label>
            <input type="text" wire:model.live.debounce.300ms="modelId"
                placeholder="{{ __('admin.audit_events_filter_model_id_placeholder') }}"
                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('admin.audit_events_filter_subject_type') }}</label>
            <input type="text" wire:model.live.debounce.300ms="subjectType"
                placeholder="{{ __('admin.audit_events_filter_subject_type_placeholder') }}"
                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('admin.audit_events_filter_subject_id') }}</label>
            <input type="text" wire:model.live.debounce.300ms="subjectId"
                placeholder="{{ __('admin.audit_events_filter_subject_id_placeholder') }}"
                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('admin.audit_events_filter_subject_relation') }}</label>
            <input type="text" wire:model.live.debounce.300ms="subjectRelation"
                placeholder="{{ __('admin.audit_events_filter_subject_relation_placeholder') }}"
                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('admin.audit_events_filter_request_id') }}</label>
            <input type="text" wire:model.live.debounce.300ms="requestId"
                placeholder="{{ __('admin.audit_events_filter_request_id_placeholder') }}"
                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
        </div>

        <div class="flex items-end">
            <button wire:click="resetFilters"
                class="rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                {{ __('admin.audit_events_filter_reset') }}
            </button>
        </div>

    </div>

    {{-- Table --}}
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('admin.audit_events_col_occurred_at') }}
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('admin.audit_events_col_event_type') }}
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('admin.audit_events_col_operation') }}
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('admin.audit_events_col_actor') }}
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('admin.audit_events_col_source') }}
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('admin.audit_events_col_primary_model') }}
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('admin.audit_events_col_subjects') }}
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('admin.audit_events_col_request_id') }}
                        </th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($this->auditEvents as $event)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                {{ $event->occurred_at->translatedFormat('d/m/Y H:i:s') }}
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <span class="inline-flex items-center rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">
                                    {{ $event->event_type->value }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                {{ $event->operation->value }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                <span class="font-medium">{{ $event->actor_type->value }}</span>
                                @if ($event->actor_id)
                                    <span class="text-gray-500 dark:text-gray-400"> #{{ $event->actor_id }}</span>
                                @endif
                                @if ($event->actor_role)
                                    <span class="text-xs text-gray-400">({{ $event->actor_role->value }})</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                {{ $event->source->value }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                @if ($event->model_type)
                                    <span class="text-xs">{{ class_basename($event->model_type) }}#{{ $event->model_id }}</span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                {{ $event->subjects_count }}
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-400 dark:text-gray-500 font-mono">
                                @if ($event->request_id)
                                    {{ substr($event->request_id, 0, 8) }}…
                                @else
                                    <span>—</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                <a href="{{ route('admin.audit-events.show', $event) }}"
                                    wire:navigate
                                    class="rounded-md bg-indigo-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-indigo-500">
                                    {{ __('admin.audit_events_action_view') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                {{ __('admin.audit_events_no_results') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($this->auditEvents->hasPages())
            <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700">
                {{ $this->auditEvents->links() }}
            </div>
        @endif
    </div>

</div>
