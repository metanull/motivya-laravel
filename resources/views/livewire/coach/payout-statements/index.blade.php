<div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">

    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
            {{ __('coach.payout_statement_heading') }}
        </h1>
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('coach.payout_statement_col_period') }}
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('coach.payout_statement_col_status') }}
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('coach.payout_statement_col_sessions') }}
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('coach.payout_statement_col_bookings') }}
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('coach.payout_statement_col_revenue_ttc') }}
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('coach.payout_statement_col_payout') }}
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('coach.payout_statement_col_actions') }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($this->statements as $statement)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                            {{ sprintf('%04d-%02d', $statement->period_year, $statement->period_month) }}
                        </td>
                        <td class="px-4 py-3 text-sm">
                            @php
                                $statusColors = [
                                    'draft' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                    'ready_for_invoice' => 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-200',
                                    'invoice_submitted' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-200',
                                    'approved' => 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-200',
                                    'paid' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-200',
                                    'blocked' => 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-200',
                                ];
                                $color = $statusColors[$statement->status->value] ?? 'bg-gray-100 text-gray-700';
                            @endphp
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $color }}">
                                {{ $statement->status->label() }}
                            </span>
                            @if ($statement->status === \App\Enums\CoachPayoutStatementStatus::Blocked && $statement->block_reason)
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $statement->block_reason }}</p>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700 dark:text-gray-300">
                            {{ $statement->sessions_count }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700 dark:text-gray-300">
                            {{ $statement->paid_bookings_count }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700 dark:text-gray-300">
                            <x-money :cents="$statement->revenue_ttc" />
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-gray-900 dark:text-gray-100">
                            <x-money :cents="$statement->coach_payout" />
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                            @if ($statement->status === \App\Enums\CoachPayoutStatementStatus::Draft)
                                <button
                                    wire:click="requestPayout({{ $statement->id }})"
                                    wire:confirm="{{ __('coach.payout_statement_action_request_payout') }}?"
                                    class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500">
                                    {{ __('coach.payout_statement_action_request_payout') }}
                                </button>
                            @elseif ($statement->status === \App\Enums\CoachPayoutStatementStatus::ReadyForInvoice)
                                <button
                                    wire:click="markInvoiceSubmitted({{ $statement->id }})"
                                    wire:confirm="{{ __('coach.payout_statement_action_submit_invoice') }}?"
                                    class="inline-flex items-center rounded-md bg-yellow-500 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-yellow-400">
                                    {{ __('coach.payout_statement_action_submit_invoice') }}
                                </button>
                            @else
                                <span class="text-xs text-gray-400">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                            {{ __('coach.payout_statement_no_results') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
