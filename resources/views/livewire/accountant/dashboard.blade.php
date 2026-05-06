<div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    {{-- Page heading --}}
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ __('accountant.dashboard_heading') }}</h1>
        @if (Route::has('accountant.anomalies.index'))
            <a href="{{ route('accountant.anomalies.index') }}"
               class="inline-flex items-center rounded-md border border-orange-300 bg-orange-50 px-4 py-2 text-sm font-medium text-orange-700 shadow-sm hover:bg-orange-100 dark:border-orange-600 dark:bg-orange-900/30 dark:text-orange-300 dark:hover:bg-orange-900/50">
                {{ __('accountant.summary_view_anomalies') }}
            </a>
        @endif
    </div>

    {{-- Summary cards --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Revenue TTC --}}
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                {{ __('accountant.summary_revenue_ttc') }}
            </p>
            <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                <x-money :cents="$this->summaryRevenueTtc" />
            </p>
        </div>

        {{-- Revenue HTVA --}}
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                {{ __('accountant.summary_revenue_htva') }}
            </p>
            <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                <x-money :cents="$this->summaryRevenueHtva" />
            </p>
        </div>

        {{-- VAT --}}
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                {{ __('accountant.summary_vat') }}
            </p>
            <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                <x-money :cents="$this->summaryVat" />
            </p>
        </div>

        {{-- Payout Pending --}}
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 shadow-sm dark:border-amber-700 dark:bg-amber-900/20">
            <p class="text-xs font-medium uppercase tracking-wider text-amber-600 dark:text-amber-400">
                {{ __('accountant.summary_payout_pending') }}
            </p>
            <p class="mt-2 text-2xl font-semibold text-amber-700 dark:text-amber-300">
                <x-money :cents="$this->summaryPayoutPending" />
            </p>
        </div>

        {{-- Invoices count --}}
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                {{ __('accountant.summary_invoices') }}
            </p>
            <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                {{ $this->summaryInvoicesCount }}
            </p>
        </div>

        {{-- Credit notes count --}}
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                {{ __('accountant.summary_credit_notes') }}
            </p>
            <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                {{ $this->summaryCreditNotesCount }}
            </p>
        </div>

        {{-- Refunds count --}}
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                {{ __('accountant.summary_refunds') }}
            </p>
            <p class="mt-2 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                {{ $this->summaryRefundsCount }}
            </p>
        </div>

        {{-- Stuck sessions count --}}
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 shadow-sm dark:border-red-700 dark:bg-red-900/20">
            <p class="text-xs font-medium uppercase tracking-wider text-red-600 dark:text-red-400">
                {{ __('accountant.summary_stuck_sessions') }}
            </p>
            <p class="mt-2 text-2xl font-semibold text-red-700 dark:text-red-300">
                {{ $this->summaryStuckSessionsCount }}
            </p>
        </div>

        {{-- Open Payment Anomalies --}}
        @if (Route::has('accountant.anomalies.index'))
            <a href="{{ route('accountant.anomalies.index') }}"
               class="block rounded-lg border border-orange-200 bg-orange-50 p-4 shadow-sm transition hover:shadow-md dark:border-orange-700 dark:bg-orange-900/20">
                <div class="flex items-start justify-between">
                    <p class="text-xs font-medium uppercase tracking-wider text-orange-600 dark:text-orange-400">
                        {{ __('accountant.summary_open_anomalies') }}
                    </p>
                    @if ($this->summaryOpenAnomalyCount > 0)
                        <span class="inline-flex items-center justify-center rounded-full bg-orange-100 px-2 py-0.5 text-xs font-semibold text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                            {{ $this->summaryOpenAnomalyCount }}
                        </span>
                    @endif
                </div>
                <p class="mt-2 text-2xl font-semibold text-orange-700 dark:text-orange-300">
                    {{ $this->summaryOpenAnomalyCount }}
                </p>
            </a>
        @else
            <div class="rounded-lg border border-orange-200 bg-orange-50 p-4 shadow-sm dark:border-orange-700 dark:bg-orange-900/20">
                <p class="text-xs font-medium uppercase tracking-wider text-orange-600 dark:text-orange-400">
                    {{ __('accountant.summary_open_anomalies') }}
                </p>
                <p class="mt-2 text-2xl font-semibold text-orange-700 dark:text-orange-300">
                    {{ $this->summaryOpenAnomalyCount }}
                </p>
            </div>
        @endif
    </div>

    {{-- Audit Log Card --}}
    @if (Route::has('accountant.audit-events.index'))
        <div class="mb-6">
            <a
                href="{{ route('accountant.audit-events.index') }}"
                class="block rounded-lg border border-gray-200 bg-white p-5 shadow-sm transition hover:shadow-md dark:border-gray-700 dark:bg-gray-800"
                wire:navigate
            >
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">
                        {{ __('accountant.dashboard_card_audit_events') }}
                    </h2>
                    @if ($this->recentFinancialAuditEventCount > 0)
                        <span class="inline-flex items-center justify-center rounded-full bg-blue-100 px-2.5 py-0.5 text-sm font-semibold text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                            {{ $this->recentFinancialAuditEventCount }}
                        </span>
                    @endif
                </div>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('accountant.dashboard_card_audit_events_desc') }}
                </p>
            </a>
        </div>
    @endif

    {{-- Stuck sessions queue --}}
    <livewire:accountant.stuck-sessions-queue />

    {{-- Filters --}}
    <div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
            {{-- Date From --}}
            <div>
                <label for="dateFrom" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('accountant.filter_date_from') }}
                </label>
                <input id="dateFrom"
                    type="date"
                    wire:model.live="dateFrom"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm" />
            </div>

            {{-- Date To --}}
            <div>
                <label for="dateTo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('accountant.filter_date_to') }}
                </label>
                <input id="dateTo"
                    type="date"
                    wire:model.live="dateTo"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm" />
            </div>

            {{-- Coach --}}
            <div>
                <label for="coachId" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('accountant.filter_coach') }}
                </label>
                <select id="coachId"
                    wire:model.live="coachId"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    <option value="">{{ __('accountant.filter_all_coaches') }}</option>
                    @foreach ($this->coaches as $coach)
                        <option value="{{ $coach->id }}">{{ $coach->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Type --}}
            <div>
                <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('accountant.filter_type') }}
                </label>
                <select id="type"
                    wire:model.live="type"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    <option value="">{{ __('accountant.filter_all_types') }}</option>
                    <option value="invoice">{{ __('accountant.type_invoice') }}</option>
                    <option value="credit_note">{{ __('accountant.type_credit_note') }}</option>
                </select>
            </div>

            {{-- Status --}}
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('accountant.filter_status') }}
                </label>
                <select id="status"
                    wire:model.live="status"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    <option value="">{{ __('accountant.filter_all_statuses') }}</option>
                    <option value="draft">{{ __('accountant.status_draft') }}</option>
                    <option value="issued">{{ __('accountant.status_issued') }}</option>
                    <option value="sent">{{ __('accountant.status_sent') }}</option>
                    <option value="paid">{{ __('accountant.status_paid') }}</option>
                </select>
            </div>

            {{-- Reset button --}}
            <div class="flex items-end">
                <button wire:click="resetFilters"
                    class="inline-flex w-full items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                    {{ __('accountant.filter_reset') }}
                </button>
            </div>
        </div>
    </div>

    {{-- Export --}}
    <div class="mb-6 flex items-center justify-end gap-3">
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('accountant.export_label') }}</span>
        <button wire:click="export('csv')"
            class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
            {{ __('accountant.export_csv') }}
        </button>
        <button wire:click="export('excel')"
            class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
            {{ __('accountant.export_excel') }}
        </button>
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
                                {{ __('accountant.col_number') }}
                                <span class="text-gray-400 dark:text-gray-500">
                                    @if ($sortBy === 'invoice_number')
                                        @if ($sortDir === 'asc')
                                            ↑
                                        @else
                                            ↓
                                        @endif
                                    @else
                                        ↕
                                    @endif
                                </span>
                            </button>
                        </th>

                        {{-- Coach --}}
                        <th scope="col"
                            class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('accountant.col_coach') }}
                        </th>

                        {{-- Date (sortable: billing_period_start) --}}
                        <th scope="col"
                            class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            <button wire:click="sort('billing_period_start')" class="group inline-flex items-center gap-1">
                                {{ __('accountant.col_date') }}
                                <span class="text-gray-400 dark:text-gray-500">
                                    @if ($sortBy === 'billing_period_start')
                                        @if ($sortDir === 'asc')
                                            ↑
                                        @else
                                            ↓
                                        @endif
                                    @else
                                        ↕
                                    @endif
                                </span>
                            </button>
                        </th>

                        {{-- Type --}}
                        <th scope="col"
                            class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('accountant.col_type') }}
                        </th>

                        {{-- Amount TTC --}}
                        <th scope="col"
                            class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('accountant.col_amount_ttc') }}
                        </th>

                        {{-- Amount HTVA --}}
                        <th scope="col"
                            class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('accountant.col_amount_htva') }}
                        </th>

                        {{-- VAT --}}
                        <th scope="col"
                            class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('accountant.col_vat') }}
                        </th>

                        {{-- Payout --}}
                        <th scope="col"
                            class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('accountant.col_payout') }}
                        </th>

                        {{-- Status --}}
                        <th scope="col"
                            class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('accountant.col_status') }}
                        </th>

                        {{-- Issued At (sortable) --}}
                        <th scope="col"
                            class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            <button wire:click="sort('issued_at')" class="group inline-flex items-center gap-1">
                                {{ __('accountant.col_issued_at') }}
                                <span class="text-gray-400 dark:text-gray-500">
                                    @if ($sortBy === 'issued_at')
                                        @if ($sortDir === 'asc')
                                            ↑
                                        @else
                                            ↓
                                        @endif
                                    @else
                                        ↕
                                    @endif
                                </span>
                            </button>
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

                            {{-- Coach Name --}}
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                {{ $invoice->coach?->name ?? '—' }}
                            </td>

                            {{-- Date --}}
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                {{ $invoice->billing_period_start?->format('Y-m-d') ?? '—' }}
                            </td>

                            {{-- Type badge --}}
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                @if ($invoice->type === \App\Enums\InvoiceType::Invoice)
                                    <span class="inline-flex items-center rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">
                                        {{ __('accountant.type_invoice') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-medium text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                        {{ __('accountant.type_credit_note') }}
                                    </span>
                                @endif
                            </td>

                            {{-- Amount TTC --}}
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-900 dark:text-gray-100">
                                <x-money :cents="$invoice->revenue_ttc" />
                            </td>

                            {{-- Amount HTVA --}}
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700 dark:text-gray-300">
                                <x-money :cents="$invoice->revenue_htva" />
                            </td>

                            {{-- VAT --}}
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700 dark:text-gray-300">
                                <x-money :cents="$invoice->vat_amount" />
                            </td>

                            {{-- Coach Payout --}}
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700 dark:text-gray-300">
                                <x-money :cents="$invoice->coach_payout" />
                            </td>

                            {{-- Status badge --}}
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                @php
                                    $statusClasses = match ($invoice->status) {
                                        \App\Enums\InvoiceStatus::Draft => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
                                        \App\Enums\InvoiceStatus::Issued => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                        \App\Enums\InvoiceStatus::Sent => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                        \App\Enums\InvoiceStatus::Paid => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                    };
                                    $statusLabel = match ($invoice->status) {
                                        \App\Enums\InvoiceStatus::Draft => __('accountant.status_draft'),
                                        \App\Enums\InvoiceStatus::Issued => __('accountant.status_issued'),
                                        \App\Enums\InvoiceStatus::Sent => __('accountant.status_sent'),
                                        \App\Enums\InvoiceStatus::Paid => __('accountant.status_paid'),
                                    };
                                @endphp
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusClasses }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>

                            {{-- Issued At --}}
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                {{ $invoice->issued_at?->format('Y-m-d') ?? '—' }}
                            </td>

                            {{-- Detail link --}}
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                <a href="{{ route('accountant.invoices.show', $invoice) }}"
                                   class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-200">
                                    {{ __('accountant.detail_view') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11"
                                class="px-4 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                {{ __('accountant.no_transactions') }}
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
