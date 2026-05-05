<div class="mx-auto max-w-5xl px-4 py-6 sm:px-6 lg:px-8">

    {{-- Back link --}}
    <div class="mb-4">
        <a href="{{ route('admin.audit-events.index') }}" wire:navigate
            class="text-sm text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
            &larr; {{ __('admin.audit_events_back') }}
        </a>
    </div>

    <h1 class="mb-6 text-2xl font-bold text-gray-900 dark:text-gray-100">
        {{ __('admin.audit_events_detail_heading') }}
    </h1>

    {{-- Core fields --}}
    <div class="mb-6 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                <tr>
                    <th class="w-48 px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('admin.audit_events_col_occurred_at') }}</th>
                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $auditEvent->occurred_at->translatedFormat('d/m/Y H:i:s') }}</td>
                </tr>
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('admin.audit_events_col_event_type') }}</th>
                    <td class="px-4 py-3 text-sm">
                        <span class="inline-flex items-center rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">
                            {{ $auditEvent->event_type->value }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('admin.audit_events_col_operation') }}</th>
                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $auditEvent->operation->value }}</td>
                </tr>
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('admin.audit_events_detail_actor_type') }}</th>
                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $auditEvent->actor_type->value }}</td>
                </tr>
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('admin.audit_events_detail_actor_id') }}</th>
                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $auditEvent->actor_id ?? '—' }}</td>
                </tr>
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('admin.audit_events_detail_actor_role') }}</th>
                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $auditEvent->actor_role?->value ?? '—' }}</td>
                </tr>
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('admin.audit_events_col_source') }}</th>
                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $auditEvent->source->value }}</td>
                </tr>
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('admin.audit_events_detail_request_id') }}</th>
                    <td class="px-4 py-3 text-sm font-mono text-gray-700 dark:text-gray-300">{{ $auditEvent->request_id ?? '—' }}</td>
                </tr>
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('admin.audit_events_detail_ip_address') }}</th>
                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $auditEvent->ip_address ?? '—' }}</td>
                </tr>
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('admin.audit_events_detail_route_name') }}</th>
                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $auditEvent->route_name ?? '—' }}</td>
                </tr>
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('admin.audit_events_detail_job_uuid') }}</th>
                    <td class="px-4 py-3 text-sm font-mono text-gray-700 dark:text-gray-300">{{ $auditEvent->job_uuid ?? '—' }}</td>
                </tr>
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('admin.audit_events_col_primary_model') }}</th>
                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                        @if ($auditEvent->model_type)
                            {{ $auditEvent->model_type }}#{{ $auditEvent->model_id }}
                        @else
                            —
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Old values --}}
    <div class="mb-6">
        <h2 class="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('admin.audit_events_detail_old_values') }}</h2>
        <pre class="overflow-x-auto rounded-lg bg-gray-50 p-4 text-xs text-gray-700 dark:bg-gray-900 dark:text-gray-300">{{ $auditEvent->old_values ? json_encode($auditEvent->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '—' }}</pre>
    </div>

    {{-- New values --}}
    <div class="mb-6">
        <h2 class="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('admin.audit_events_detail_new_values') }}</h2>
        <pre class="overflow-x-auto rounded-lg bg-gray-50 p-4 text-xs text-gray-700 dark:bg-gray-900 dark:text-gray-300">{{ $auditEvent->new_values ? json_encode($auditEvent->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '—' }}</pre>
    </div>

    {{-- Metadata --}}
    <div class="mb-6">
        <h2 class="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('admin.audit_events_detail_metadata') }}</h2>
        <pre class="overflow-x-auto rounded-lg bg-gray-50 p-4 text-xs text-gray-700 dark:bg-gray-900 dark:text-gray-300">{{ $auditEvent->metadata ? json_encode($auditEvent->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '—' }}</pre>
    </div>

    {{-- Subjects grouped by relation --}}
    <div class="mb-6">
        <h2 class="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('admin.audit_events_detail_subjects') }}</h2>
        @if ($subjectsByRelation->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.audit_events_detail_no_subjects') }}</p>
        @else
            @foreach ($subjectsByRelation as $relation => $subjects)
                <div class="mb-4 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 bg-gray-50 px-4 py-2 dark:border-gray-700 dark:bg-gray-900">
                        <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('admin.audit_events_detail_subject_relation') }}: {{ $relation ?? __('admin.audit_events_detail_subject_relation_primary') }}
                        </span>
                    </div>
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('admin.audit_events_detail_subject_type') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('admin.audit_events_detail_subject_id') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($subjects as $subject)
                                <tr>
                                    <td class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">{{ $subject->subject_type }}</td>
                                    <td class="px-4 py-2 text-sm font-mono text-gray-600 dark:text-gray-400">{{ $subject->subject_id }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach
        @endif
    </div>

</div>
