<div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    {{-- Page heading --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ __('coach.payout_history_heading') }}</h1>
    </div>

    {{-- Filters --}}
    <div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {{-- Status --}}
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('coach.payout_filter_status') }}
                </label>
                <select id="status"
                    wire:model.live="status"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    <option value="">{{ __('coach.payout_filter_all_statuses') }}</option>
                    <option value="draft">{{ __('coach.payout_status_draft') }}</option>
                    <option value="issued">{{ __('coach.payout_status_issued') }}</option>
                    <option value="sent">{{ __('coach.payout_status_sent') }}</option>
                    <option value="paid">{{ __('coach.payout_status_paid') }}</option>
                </select>
            </div>

            {{-- Reset button --}}
            <div class="flex items-end">
                <button wire:click="resetFilters"
                    class="inline-flex w-full items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                    {{ __('coach.payout_filter_reset') }}
                </button>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        {{-- Number (sortable) --}}
                        <th scope="col"
                            class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            <button wire:click="sort('invoice_number')" class="group inline-flex items-center gap-1">
                                {{ __('coach.payout_col_number') }}
                                <span class="text-gray-400 dark:text-gray-500">
                                    @if ($sortBy === 'invoice_number')
                                        @if ($sortDir === 'asc') ↑ @else ↓ @endif
                                    @else
                                        ↕
                                    @endif
                                </span>
                            </button>
                        </th>

                        {{-- Month (sortable: billing_period_start) --}}
                        <th scope="col"
                            class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            <button wire:click="sort('billing_period_start')" class="group inline-flex items-center gap-1">
                                {{ __('coach.payout_col_month') }}
                                <span class="text-gray-400 dark:text-gray-500">
                                    @if ($sortBy === 'billing_period_start')
                                        @if ($sortDir === 'asc') ↑ @else ↓ @endif
                                    @else
                                        ↕
                                    @endif
                                </span>
                            </button>
                        </th>

                        {{-- Revenue HTVA (sortable) --}}
                        <th scope="col"
                            class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            <button wire:click="sort('revenue_htva')" class="group inline-flex items-center gap-1">
                                {{ __('coach.payout_col_revenue') }}
                                <span class="text-gray-400 dark:text-gray-500">
                                    @if ($sortBy === 'revenue_htva')
                                        @if ($sortDir === 'asc') ↑ @else ↓ @endif
                                    @else
                                        ↕
                                    @endif
                                </span>
                            </button>
                        </th>

                        {{-- Commission --}}
                        <th scope="col"
                            class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('coach.payout_col_commission') }}
                        </th>

                        {{-- Payout (sortable) --}}
                        <th scope="col"
                            class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            <button wire:click="sort('coach_payout')" class="group inline-flex items-center gap-1">
                                {{ __('coach.payout_col_payout') }}
                                <span class="text-gray-400 dark:text-gray-500">
                                    @if ($sortBy === 'coach_payout')
                                        @if ($sortDir === 'asc') ↑ @else ↓ @endif
                                    @else
                                        ↕
                                    @endif
                                </span>
                            </button>
                        </th>

                        {{-- Plan applied --}}
                        <th scope="col"
                            class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('coach.payout_col_plan') }}
                        </th>

                        {{-- Status --}}
                        <th scope="col"
                            class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('coach.payout_col_status') }}
                        </th>

                        {{-- Actions --}}
                        <th scope="col" class="px-4 py-3"></th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                    @forelse ($invoices as $invoice)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            {{-- Invoice Number --}}
                            <td class="whitespace-nowrap px-4 py-3 text-sm font-mono text-gray-900 dark:text-gray-100">
                                {{ $invoice->invoice_number }}
                            </td>

                            {{-- Month --}}
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                {{ $invoice->billing_period_start?->format('Y-m') ?? '—' }}
                            </td>

                            {{-- Revenue HTVA --}}
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-900 dark:text-gray-100">
                                <x-money :cents="$invoice->revenue_htva" />
                            </td>

                            {{-- Commission --}}
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700 dark:text-gray-300">
                                <x-money :cents="$invoice->commission_amount" />
                            </td>

                            {{-- Payout --}}
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-medium text-gray-900 dark:text-gray-100">
                                <x-money :cents="$invoice->coach_payout" />
                            </td>

                            {{-- Plan applied --}}
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                {{ __('coach.plan_' . $invoice->plan_applied) }}
                            </td>

                            {{-- Status badge --}}
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                @php
                                    $statusClasses = match ($invoice->status) {
                                        \App\Enums\InvoiceStatus::Draft  => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
                                        \App\Enums\InvoiceStatus::Issued => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                        \App\Enums\InvoiceStatus::Sent   => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                        \App\Enums\InvoiceStatus::Paid   => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                    };
                                    $statusLabel = match ($invoice->status) {
                                        \App\Enums\InvoiceStatus::Draft  => __('coach.payout_status_draft'),
                                        \App\Enums\InvoiceStatus::Issued => __('coach.payout_status_issued'),
                                        \App\Enums\InvoiceStatus::Sent   => __('coach.payout_status_sent'),
                                        \App\Enums\InvoiceStatus::Paid   => __('coach.payout_status_paid'),
                                    };
                                @endphp
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusClasses }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>

                            {{-- Download XML --}}
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                @if ($invoice->xml_path !== null)
                                    <button wire:click="downloadXml({{ $invoice->id }})"
                                        class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-200">
                                        {{ __('coach.payout_download_xml') }}
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8"
                                class="px-4 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                {{ __('coach.payout_no_invoices') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($invoices->hasPages())
            <div class="border-t border-gray-200 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-800">
                {{ $invoices->links() }}
            </div>
        @endif
    </div>
</div>
