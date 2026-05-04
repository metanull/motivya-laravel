<div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">

    {{-- Page heading --}}
    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
        {{ __('admin.billing_config_heading') }}
    </h1>
    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
        {{ __('admin.billing_config_subtitle') }}
    </p>

    {{-- Read-only notice banner --}}
    <div class="mt-6 flex items-start gap-3 rounded-lg border border-amber-300 bg-amber-50 px-4 py-4 dark:border-amber-700 dark:bg-amber-900/20">
        <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
        </svg>
        <p class="text-sm font-medium text-amber-800 dark:text-amber-200">
            {{ __('admin.billing_config_read_only_notice') }}
        </p>
    </div>

    {{-- ------------------------------------------------------------------ --}}
    {{-- Section 1 — Subscription Plans                                      --}}
    {{-- ------------------------------------------------------------------ --}}
    <section class="mt-10" aria-labelledby="plans-heading">
        <h2 id="plans-heading" class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            {{ __('admin.billing_config_plan_heading') }}
        </h2>

        {{-- Mobile-first card stack (hidden on md+) --}}
        <div class="mt-4 space-y-4 md:hidden">
            @foreach ($this->plans as $planKey => $plan)
                <div class="rounded-lg bg-white p-5 shadow dark:bg-gray-800">
                    <div class="flex items-center justify-between">
                        <span class="text-base font-semibold capitalize text-gray-900 dark:text-gray-100">
                            {{ ucfirst($planKey) }}
                        </span>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold
                            {{ $planKey === 'freemium' ? 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' : ($planKey === 'active' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-200' : 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-200') }}">
                            {{ ucfirst($planKey) }}
                        </span>
                    </div>
                    <dl class="mt-3 grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <dt class="font-medium text-gray-500 dark:text-gray-400">{{ __('admin.billing_config_col_commission') }}</dt>
                            <dd class="mt-0.5 font-mono text-gray-900 dark:text-gray-100">{{ $plan['commission_rate'] }}%</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-gray-500 dark:text-gray-400">{{ __('admin.billing_config_col_subscription_fee') }}</dt>
                            <dd class="mt-0.5 font-mono text-gray-900 dark:text-gray-100">
                                @if ($plan['subscription_fee'] === 0)
                                    {{ __('admin.billing_config_free') }}
                                @else
                                    €{{ number_format($plan['subscription_fee'] / 100, 2, ',', '.') }}
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
            @endforeach
        </div>

        {{-- Desktop table (hidden on mobile) --}}
        <div class="mt-4 hidden overflow-hidden rounded-lg shadow md:block">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-300">
                            {{ __('admin.billing_config_col_plan') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-300">
                            {{ __('admin.billing_config_col_commission') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-300">
                            {{ __('admin.billing_config_col_subscription_fee') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-300">
                            {{ __('admin.billing_config_col_description') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                    @foreach ($this->plans as $planKey => $plan)
                        <tr>
                            <td class="whitespace-nowrap px-6 py-4">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold
                                    {{ $planKey === 'freemium' ? 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' : ($planKey === 'active' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-200' : 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-200') }}">
                                    {{ ucfirst($planKey) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right font-mono text-sm text-gray-900 dark:text-gray-100">
                                {{ $plan['commission_rate'] }}%
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right font-mono text-sm text-gray-900 dark:text-gray-100">
                                @if ($plan['subscription_fee'] === 0)
                                    {{ __('admin.billing_config_free') }}
                                @else
                                    €{{ number_format($plan['subscription_fee'] / 100, 2, ',', '.') }}
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                {{ __('admin.billing_config_plan_desc_' . $planKey) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    {{-- ------------------------------------------------------------------ --}}
    {{-- Section 2 — VAT Configuration                                       --}}
    {{-- ------------------------------------------------------------------ --}}
    <section class="mt-10" aria-labelledby="vat-heading">
        <h2 id="vat-heading" class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            {{ __('admin.billing_config_vat_heading') }}
        </h2>

        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="rounded-lg bg-white p-5 shadow dark:bg-gray-800">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    {{ __('admin.billing_config_vat_rate_label') }} — {{ __('admin.billing_config_vat_subject') }}
                </dt>
                <dd class="mt-1 font-mono text-2xl font-bold text-gray-900 dark:text-gray-100">
                    {{ $this->vatRates['subject'] }}%
                </dd>
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ __('admin.billing_config_vat_subject_note') }}</p>
            </div>
            <div class="rounded-lg bg-white p-5 shadow dark:bg-gray-800">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    {{ __('admin.billing_config_vat_rate_label') }} — {{ __('admin.billing_config_vat_franchise') }}
                </dt>
                <dd class="mt-1 font-mono text-2xl font-bold text-gray-900 dark:text-gray-100">
                    {{ $this->vatRates['franchise'] }}%
                </dd>
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ __('admin.billing_config_vat_franchise_note') }}</p>
            </div>
        </div>

        <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">
            {{ __('admin.billing_config_vat_source') }}
        </p>
    </section>

    {{-- ------------------------------------------------------------------ --}}
    {{-- Section 3 — Stripe Fee Rate                                         --}}
    {{-- ------------------------------------------------------------------ --}}
    <section class="mt-10" aria-labelledby="stripe-heading">
        <h2 id="stripe-heading" class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            {{ __('admin.billing_config_stripe_heading') }}
        </h2>

        <div class="mt-4 rounded-lg bg-white p-5 shadow dark:bg-gray-800 sm:max-w-xs">
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                {{ __('admin.billing_config_stripe_fee_label') }}
            </dt>
            <dd class="mt-1 font-mono text-2xl font-bold text-gray-900 dark:text-gray-100">
                {{ number_format($this->stripeFeeRate, 1) }}%
            </dd>
            <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">
                {{ __('admin.billing_config_stripe_source') }}
            </p>
        </div>
    </section>

    {{-- ------------------------------------------------------------------ --}}
    {{-- Section 4 — Payout Formula                                          --}}
    {{-- ------------------------------------------------------------------ --}}
    <section class="mt-10" aria-labelledby="payout-heading">
        <h2 id="payout-heading" class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            {{ __('admin.billing_config_payout_heading') }}
        </h2>

        <div class="mt-4 rounded-lg bg-white p-5 shadow dark:bg-gray-800">
            <p class="text-sm leading-relaxed text-gray-700 dark:text-gray-300">
                {{ __('admin.billing_config_payout_description') }}
            </p>

            {{-- Formula display --}}
            <div class="mt-4 overflow-x-auto rounded-md bg-gray-50 px-4 py-3 dark:bg-gray-900">
                <code class="text-sm text-gray-800 dark:text-gray-200">
                    Net Payout = Revenue (HTVA) &minus; Commission &minus; Subscription Fee &minus; Stripe Fees
                </code>
            </div>

            <p class="mt-3 text-xs text-gray-400 dark:text-gray-500">
                {{ __('admin.billing_config_payout_note') }}
            </p>
        </div>
    </section>

</div>
