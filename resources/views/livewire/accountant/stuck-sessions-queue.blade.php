<div>
    @if ($this->stuckSessions->isNotEmpty())
        <div class="mb-8 rounded-lg border border-amber-300 bg-amber-50 shadow-sm dark:border-amber-700 dark:bg-amber-900/20">
            <div class="flex items-center gap-3 border-b border-amber-300 px-6 py-4 dark:border-amber-700">
                <span class="text-amber-600 dark:text-amber-400">⚠</span>
                <h2 class="text-base font-semibold text-amber-800 dark:text-amber-200">
                    {{ __('accountant.stuck_sessions_heading') }}
                    <span class="ml-2 inline-flex items-center rounded-full bg-amber-200 px-2.5 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-800 dark:text-amber-200">
                        {{ $this->stuckSessions->count() }}
                    </span>
                </h2>
            </div>
            <p class="px-6 py-3 text-sm text-amber-700 dark:text-amber-300">
                {{ __('accountant.stuck_sessions_description') }}
            </p>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-amber-200 dark:divide-amber-700">
                    <thead class="bg-amber-100 dark:bg-amber-900/40">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-amber-700 dark:text-amber-300">
                                {{ __('accountant.stuck_col_coach') }}
                            </th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-amber-700 dark:text-amber-300">
                                {{ __('accountant.stuck_col_title') }}
                            </th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-amber-700 dark:text-amber-300">
                                {{ __('accountant.stuck_col_date') }}
                            </th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-amber-700 dark:text-amber-300">
                                {{ __('accountant.stuck_col_time') }}
                            </th>
                            <th scope="col" class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-amber-700 dark:text-amber-300">
                                {{ __('accountant.stuck_col_participants') }}
                            </th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-amber-700 dark:text-amber-300">
                                {{ __('accountant.stuck_col_invoice') }}
                            </th>
                            <th scope="col" class="px-4 py-3"></th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-amber-100 bg-white dark:divide-amber-800 dark:bg-gray-800">
                        @foreach ($this->stuckSessions as $session)
                            @php
                                $invoice = $session->invoices->first();
                            @endphp
                            <tr class="hover:bg-amber-50 dark:hover:bg-amber-900/10">
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                    {{ $session->coach?->name ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                                    {{ $session->title }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                    {{ $session->date->format('Y-m-d') }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                    {{ substr($session->start_time, 0, 5) }} – {{ substr($session->end_time, 0, 5) }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-center text-sm text-gray-700 dark:text-gray-300">
                                    {{ $session->current_participants }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm">
                                    @if ($invoice !== null)
                                        <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-200">
                                            {{ $invoice->invoice_number }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                            {{ __('accountant.stuck_no_invoice') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm">
                                    @can('manualComplete', $session)
                                        <button
                                            wire:click="complete({{ $session->id }})"
                                            wire:confirm="{{ __('accountant.stuck_sessions_confirm') }}"
                                            class="inline-flex items-center rounded-md border border-amber-400 bg-amber-100 px-3 py-1 text-xs font-medium text-amber-800 hover:bg-amber-200 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:border-amber-600 dark:bg-amber-900/40 dark:text-amber-200 dark:hover:bg-amber-800/60">
                                            {{ __('accountant.stuck_sessions_complete_action') }}
                                        </button>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
