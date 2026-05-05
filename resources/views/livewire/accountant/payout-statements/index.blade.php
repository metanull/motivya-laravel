<div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                {{ __('accountant.payout_statements_heading') }}
            </h1>
        </div>
    </div>

    {{-- Filters --}}
    <div class="mb-4 flex flex-wrap gap-3">
        <select
            wire:model.live="filterStatus"
            class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
            <option value="">{{ __('accountant.payout_statements_filter_all_statuses') }}</option>
            @foreach ($this->statusOptions() as $option)
                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
            @endforeach
        </select>

        <select
            wire:model.live="filterCoach"
            class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
            <option value="">{{ __('accountant.payout_statements_filter_all_coaches') }}</option>
            @foreach ($this->coaches as $coach)
                <option value="{{ $coach->id }}">{{ $coach->name }}</option>
            @endforeach
        </select>

        @if ($filterStatus !== '' || $filterCoach !== '')
            <button wire:click="resetFilters"
                class="rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                ✕ Reset
            </button>
        @endif
    </div>

    {{-- Block Modal --}}
    @if ($blockingStatementId !== null)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl dark:bg-gray-800">
                <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">
                    {{ __('accountant.payout_statements_action_block') }}
                </h2>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('accountant.payout_statements_block_reason_label') }}
                </label>
                <textarea
                    wire:model="blockReason"
                    rows="3"
                    class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"
                    placeholder="{{ __('accountant.payout_statements_block_reason_placeholder') }}"></textarea>
                @error('blockReason') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                <div class="mt-4 flex justify-end gap-3">
                    <button wire:click="$set('blockingStatementId', null)"
                        class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300">
                        {{ __('common.cancel') }}
                    </button>
                    <button wire:click="block({{ $blockingStatementId }})"
                        class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-500">
                        {{ __('accountant.payout_statements_action_block') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Table --}}
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('accountant.payout_statements_col_coach') }}
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('accountant.payout_statements_col_period') }}
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('accountant.payout_statements_col_status') }}
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('accountant.payout_statements_col_sessions') }}
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('accountant.payout_statements_col_payout') }}
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('accountant.payout_statements_col_actions') }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($this->statements as $statement)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                            {{ $statement->coach?->name ?? '—' }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                            {{ sprintf('%04d-%02d', $statement->period_year, $statement->period_month) }}
                        </td>
                        <td class="px-4 py-3 text-sm">
                            @php
                                $statusColors = [
                                    'draft' => 'bg-gray-100 text-gray-700',
                                    'ready_for_invoice' => 'bg-blue-100 text-blue-700',
                                    'invoice_submitted' => 'bg-yellow-100 text-yellow-700',
                                    'approved' => 'bg-green-100 text-green-700',
                                    'paid' => 'bg-emerald-100 text-emerald-700',
                                    'blocked' => 'bg-red-100 text-red-700',
                                ];
                                $color = $statusColors[$statement->status->value] ?? 'bg-gray-100 text-gray-700';
                            @endphp
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $color }}">
                                {{ $statement->status->label() }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700 dark:text-gray-300">
                            {{ $statement->sessions_count }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-gray-900 dark:text-gray-100">
                            <x-money :cents="$statement->coach_payout" />
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                            <div class="flex items-center justify-end gap-2">
                                @if ($statement->status === \App\Enums\CoachPayoutStatementStatus::InvoiceSubmitted)
                                    <button
                                        wire:click="approve({{ $statement->id }})"
                                        wire:confirm="{{ __('accountant.payout_statements_confirm_approve') }}"
                                        class="rounded-md bg-green-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-green-500">
                                        {{ __('accountant.payout_statements_action_approve') }}
                                    </button>
                                    <button
                                        wire:click="openBlockModal({{ $statement->id }})"
                                        class="rounded-md bg-red-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-red-500">
                                        {{ __('accountant.payout_statements_action_block') }}
                                    </button>
                                @elseif ($statement->status === \App\Enums\CoachPayoutStatementStatus::Approved)
                                    <button
                                        wire:click="markPaid({{ $statement->id }})"
                                        wire:confirm="{{ __('accountant.payout_statements_confirm_paid') }}"
                                        class="rounded-md bg-indigo-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-indigo-500">
                                        {{ __('accountant.payout_statements_action_mark_paid') }}
                                    </button>
                                @elseif (in_array($statement->status->value, ['draft', 'ready_for_invoice']))
                                    <button
                                        wire:click="openBlockModal({{ $statement->id }})"
                                        class="rounded-md bg-red-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-red-500">
                                        {{ __('accountant.payout_statements_action_block') }}
                                    </button>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                            {{ __('accountant.payout_statements_no_results') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if ($this->statements->hasPages())
            <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700">
                {{ $this->statements->links() }}
            </div>
        @endif
    </div>

</div>
