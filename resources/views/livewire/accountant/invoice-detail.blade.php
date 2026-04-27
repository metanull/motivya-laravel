<div class="mx-auto max-w-4xl px-4 py-6 sm:px-6 lg:px-8">
    {{-- Page heading --}}
    <div class="mb-6 flex items-center justify-between">
        <div>
            <a href="{{ route('accountant.dashboard') }}"
               class="mb-2 inline-flex items-center text-sm text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-200">
                ← {{ __('accountant.detail_back') }}
            </a>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                {{ __('accountant.detail_heading', ['number' => $invoice->invoice_number]) }}
            </h1>
        </div>

        {{-- Type badge --}}
        <div>
            @if ($invoice->type === \App\Enums\InvoiceType::Invoice)
                <span class="inline-flex items-center rounded-full bg-indigo-100 px-3 py-1 text-sm font-medium text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">
                    {{ __('accountant.type_invoice') }}
                </span>
            @else
                <span class="inline-flex items-center rounded-full bg-purple-100 px-3 py-1 text-sm font-medium text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                    {{ __('accountant.type_credit_note') }}
                </span>
            @endif
        </div>
    </div>

    {{-- Discrepancy banner --}}
    @if ($this->hasDiscrepancies)
        <div class="mb-6 rounded-lg border border-red-300 bg-red-50 p-4 dark:border-red-700 dark:bg-red-900/20">
            <div class="flex items-start gap-3">
                <span class="mt-0.5 text-red-500 dark:text-red-400">⚠</span>
                <div>
                    <p class="font-semibold text-red-700 dark:text-red-300">{{ __('accountant.detail_discrepancy_title') }}</p>
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ __('accountant.detail_discrepancy_description') }}</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Meta --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('accountant.detail_coach') }}</p>
            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $invoice->coach?->name ?? '—' }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('accountant.detail_billing_period') }}</p>
            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">
                {{ $invoice->billing_period_start?->format('Y-m-d') ?? '—' }}
                @if ($invoice->billing_period_start?->ne($invoice->billing_period_end))
                    → {{ $invoice->billing_period_end?->format('Y-m-d') ?? '—' }}
                @endif
            </p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('accountant.col_status') }}</p>
            <p class="mt-1">
                @php
                    $statusClasses = match ($invoice->status) {
                        \App\Enums\InvoiceStatus::Draft  => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
                        \App\Enums\InvoiceStatus::Issued => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                        \App\Enums\InvoiceStatus::Sent   => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                        \App\Enums\InvoiceStatus::Paid   => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                    };
                    $statusLabel = match ($invoice->status) {
                        \App\Enums\InvoiceStatus::Draft  => __('accountant.status_draft'),
                        \App\Enums\InvoiceStatus::Issued => __('accountant.status_issued'),
                        \App\Enums\InvoiceStatus::Sent   => __('accountant.status_sent'),
                        \App\Enums\InvoiceStatus::Paid   => __('accountant.status_paid'),
                    };
                @endphp
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusClasses }}">
                    {{ $statusLabel }}
                </span>
            </p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('accountant.col_issued_at') }}</p>
            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">
                {{ $invoice->issued_at?->format('Y-m-d') ?? '—' }}
            </p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('accountant.detail_plan_applied') }}</p>
            <p class="mt-1 text-sm font-semibold uppercase text-gray-900 dark:text-gray-100">{{ $invoice->plan_applied ?? '—' }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('accountant.detail_tax_category') }}</p>
            <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $invoice->tax_category_code ?? '—' }}</p>
        </div>
    </div>

    {{-- Commission breakdown table --}}
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
            <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('accountant.detail_breakdown_heading') }}</h2>
        </div>
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('accountant.detail_col_field') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('accountant.detail_col_stored') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('accountant.detail_col_expected') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ __('accountant.detail_col_ok') }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                {{-- Revenue TTC --}}
                @php $expectedRevenueTtc = $invoice->revenue_ttc; @endphp
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="px-6 py-3 text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('accountant.detail_revenue_ttc') }}</td>
                    <td class="px-6 py-3 text-right text-sm text-gray-900 dark:text-gray-100"><x-money :cents="$invoice->revenue_ttc" /></td>
                    <td class="px-6 py-3 text-right text-sm text-gray-500 dark:text-gray-400">—</td>
                    <td class="px-6 py-3 text-center text-sm">✓</td>
                </tr>

                {{-- Revenue HTVA --}}
                @php $expectedHtva = intdiv($invoice->revenue_ttc * 100 + 60, 121); @endphp
                <tr class="{{ $this->discrepancies['revenue_htva'] ? 'bg-red-50 dark:bg-red-900/20' : 'hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                    <td class="px-6 py-3 text-sm font-medium {{ $this->discrepancies['revenue_htva'] ? 'text-red-700 dark:text-red-300' : 'text-gray-700 dark:text-gray-300' }}">
                        {{ __('accountant.detail_revenue_htva') }}
                    </td>
                    <td class="px-6 py-3 text-right text-sm {{ $this->discrepancies['revenue_htva'] ? 'font-semibold text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-gray-100' }}">
                        <x-money :cents="$invoice->revenue_htva" />
                    </td>
                    <td class="px-6 py-3 text-right text-sm text-gray-500 dark:text-gray-400"><x-money :cents="$expectedHtva" /></td>
                    <td class="px-6 py-3 text-center text-sm">
                        @if ($this->discrepancies['revenue_htva'])
                            <span class="font-bold text-red-600 dark:text-red-400">✗</span>
                        @else
                            <span class="text-green-600 dark:text-green-400">✓</span>
                        @endif
                    </td>
                </tr>

                {{-- VAT Amount --}}
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="px-6 py-3 text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('accountant.detail_vat_amount') }}</td>
                    <td class="px-6 py-3 text-right text-sm text-gray-900 dark:text-gray-100"><x-money :cents="$invoice->vat_amount" /></td>
                    <td class="px-6 py-3 text-right text-sm text-gray-500 dark:text-gray-400">—</td>
                    <td class="px-6 py-3 text-center text-sm">✓</td>
                </tr>

                {{-- Stripe Fee --}}
                @php $expectedStripeFee = (int) round($invoice->revenue_ttc * 15 / 1000); @endphp
                <tr class="{{ $this->discrepancies['stripe_fee'] ? 'bg-red-50 dark:bg-red-900/20' : 'hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                    <td class="px-6 py-3 text-sm font-medium {{ $this->discrepancies['stripe_fee'] ? 'text-red-700 dark:text-red-300' : 'text-gray-700 dark:text-gray-300' }}">
                        {{ __('accountant.detail_stripe_fee') }}
                    </td>
                    <td class="px-6 py-3 text-right text-sm {{ $this->discrepancies['stripe_fee'] ? 'font-semibold text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-gray-100' }}">
                        <x-money :cents="$invoice->stripe_fee" />
                    </td>
                    <td class="px-6 py-3 text-right text-sm text-gray-500 dark:text-gray-400"><x-money :cents="$expectedStripeFee" /></td>
                    <td class="px-6 py-3 text-center text-sm">
                        @if ($this->discrepancies['stripe_fee'])
                            <span class="font-bold text-red-600 dark:text-red-400">✗</span>
                        @else
                            <span class="text-green-600 dark:text-green-400">✓</span>
                        @endif
                    </td>
                </tr>

                {{-- Subscription Fee --}}
                <tr class="{{ $this->discrepancies['subscription_fee'] ? 'bg-red-50 dark:bg-red-900/20' : 'hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                    <td class="px-6 py-3 text-sm font-medium {{ $this->discrepancies['subscription_fee'] ? 'text-red-700 dark:text-red-300' : 'text-gray-700 dark:text-gray-300' }}">
                        {{ __('accountant.detail_subscription_fee') }}
                    </td>
                    <td class="px-6 py-3 text-right text-sm {{ $this->discrepancies['subscription_fee'] ? 'font-semibold text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-gray-100' }}">
                        <x-money :cents="$invoice->subscription_fee" />
                    </td>
                    <td class="px-6 py-3 text-right text-sm text-gray-500 dark:text-gray-400"><x-money :cents="$this->expectedSubscriptionFee" /></td>
                    <td class="px-6 py-3 text-center text-sm">
                        @if ($this->discrepancies['subscription_fee'])
                            <span class="font-bold text-red-600 dark:text-red-400">✗</span>
                        @else
                            <span class="text-green-600 dark:text-green-400">✓</span>
                        @endif
                    </td>
                </tr>

                {{-- Commission Rate --}}
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="px-6 py-3 text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('accountant.detail_commission_rate') }}</td>
                    <td class="px-6 py-3 text-right text-sm text-gray-900 dark:text-gray-100">—</td>
                    <td class="px-6 py-3 text-right text-sm text-gray-500 dark:text-gray-400">{{ $this->expectedCommissionRate }} %</td>
                    <td class="px-6 py-3 text-center text-sm">✓</td>
                </tr>

                {{-- Commission Amount --}}
                @php $expectedCommissionAmt = (int) round($expectedHtva * $this->expectedCommissionRate / 100); @endphp
                <tr class="{{ $this->discrepancies['commission_amount'] ? 'bg-red-50 dark:bg-red-900/20' : 'hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                    <td class="px-6 py-3 text-sm font-medium {{ $this->discrepancies['commission_amount'] ? 'text-red-700 dark:text-red-300' : 'text-gray-700 dark:text-gray-300' }}">
                        {{ __('accountant.detail_commission_amount') }}
                    </td>
                    <td class="px-6 py-3 text-right text-sm {{ $this->discrepancies['commission_amount'] ? 'font-semibold text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-gray-100' }}">
                        <x-money :cents="$invoice->commission_amount" />
                    </td>
                    <td class="px-6 py-3 text-right text-sm text-gray-500 dark:text-gray-400"><x-money :cents="$expectedCommissionAmt" /></td>
                    <td class="px-6 py-3 text-center text-sm">
                        @if ($this->discrepancies['commission_amount'])
                            <span class="font-bold text-red-600 dark:text-red-400">✗</span>
                        @else
                            <span class="text-green-600 dark:text-green-400">✓</span>
                        @endif
                    </td>
                </tr>

                {{-- Coach Payout --}}
                @php $expectedCoachPayout = $expectedHtva - $expectedCommissionAmt - $expectedStripeFee - $this->expectedSubscriptionFee; @endphp
                <tr class="{{ $this->discrepancies['coach_payout'] ? 'bg-red-50 dark:bg-red-900/20' : 'hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                    <td class="px-6 py-3 text-sm font-medium {{ $this->discrepancies['coach_payout'] ? 'text-red-700 dark:text-red-300' : 'text-gray-700 dark:text-gray-300' }}">
                        {{ __('accountant.detail_coach_payout') }}
                    </td>
                    <td class="px-6 py-3 text-right text-sm {{ $this->discrepancies['coach_payout'] ? 'font-semibold text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-gray-100' }}">
                        <x-money :cents="$invoice->coach_payout" />
                    </td>
                    <td class="px-6 py-3 text-right text-sm text-gray-500 dark:text-gray-400"><x-money :cents="$expectedCoachPayout" /></td>
                    <td class="px-6 py-3 text-center text-sm">
                        @if ($this->discrepancies['coach_payout'])
                            <span class="font-bold text-red-600 dark:text-red-400">✗</span>
                        @else
                            <span class="text-green-600 dark:text-green-400">✓</span>
                        @endif
                    </td>
                </tr>

                {{-- Platform Margin --}}
                <tr class="{{ $this->discrepancies['platform_margin'] ? 'bg-red-50 dark:bg-red-900/20' : 'hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                    <td class="px-6 py-3 text-sm font-medium {{ $this->discrepancies['platform_margin'] ? 'text-red-700 dark:text-red-300' : 'text-gray-700 dark:text-gray-300' }}">
                        {{ __('accountant.detail_platform_margin') }}
                    </td>
                    <td class="px-6 py-3 text-right text-sm {{ $this->discrepancies['platform_margin'] ? 'font-semibold text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-gray-100' }}">
                        <x-money :cents="$invoice->platform_margin" />
                    </td>
                    <td class="px-6 py-3 text-right text-sm text-gray-500 dark:text-gray-400"><x-money :cents="$expectedCommissionAmt" /></td>
                    <td class="px-6 py-3 text-center text-sm">
                        @if ($this->discrepancies['platform_margin'])
                            <span class="font-bold text-red-600 dark:text-red-400">✗</span>
                        @else
                            <span class="text-green-600 dark:text-green-400">✓</span>
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
