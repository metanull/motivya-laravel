<div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    {{-- Page heading --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ __('accountant.transactions_heading') }}</h1>
    </div>

    {{-- Filters --}}
    <div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-7">
            {{-- Date From --}}
            <div>
                <label for="txn-dateFrom" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('accountant.filter_date_from') }}
                </label>
                <input id="txn-dateFrom"
                    type="date"
                    wire:model.live="dateFrom"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm" />
            </div>

            {{-- Date To --}}
            <div>
                <label for="txn-dateTo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('accountant.filter_date_to') }}
                </label>
                <input id="txn-dateTo"
                    type="date"
                    wire:model.live="dateTo"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm" />
            </div>

            {{-- Coach --}}
            <div>
                <label for="txn-coachId" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('accountant.filter_coach') }}
                </label>
                <select id="txn-coachId"
                    wire:model.live="coachId"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    <option value="">{{ __('accountant.transactions_filter_all_coaches') }}</option>
                    @foreach ($this->coaches as $coach)
                        <option value="{{ $coach->id }}">{{ $coach->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Session Status --}}
            <div>
                <label for="txn-sessionStatus" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('accountant.filter_status') }}
                </label>
                <select id="txn-sessionStatus"
                    wire:model.live="sessionStatus"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    <option value="">{{ __('accountant.transactions_filter_all_session_statuses') }}</option>
                    @foreach (\App\Enums\SessionStatus::cases() as $s)
                        <option value="{{ $s->value }}">{{ $s->label() }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Booking/Payment Status --}}
            <div>
                <label for="txn-bookingStatus" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('accountant.transactions_col_booking_status') }}
                </label>
                <select id="txn-bookingStatus"
                    wire:model.live="bookingStatus"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    <option value="">{{ __('accountant.transactions_filter_all_booking_statuses') }}</option>
                    @foreach (\App\Enums\BookingStatus::cases() as $s)
                        <option value="{{ $s->value }}">{{ $s->label() }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Anomaly flag (placeholder) --}}
            <div>
                <label for="txn-anomalyFlag" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('accountant.transactions_filter_anomaly') }}
                </label>
                <select id="txn-anomalyFlag"
                    wire:model.live="anomalyFlag"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    <option value="">{{ __('accountant.transactions_filter_all') }}</option>
                    <option value="anomalies_only">{{ __('accountant.transactions_filter_anomalies_only') }}</option>
                    <option value="paid_without_invoice">{{ __('accountant.transactions_filter_paid_without_invoice') }}</option>
                    <option value="paid_without_payment_intent">{{ __('accountant.transactions_filter_paid_without_payment_intent') }}</option>
                </select>
            </div>

            {{-- Reset button --}}
            <div class="flex items-end">
                <button wire:click="resetFilters"
                    class="inline-flex w-full items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                    {{ __('accountant.transactions_filter_reset') }}
                </button>
            </div>
        </div>
    </div>

    {{-- Export --}}
    <div class="mb-6 flex items-center justify-end gap-3">
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('accountant.transactions_export_label') }}</span>
        <button wire:click="export('csv')"
            class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
            {{ __('accountant.transactions_export_csv') }}
        </button>
        <button wire:click="export('excel')"
            class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
            {{ __('accountant.transactions_export_excel') }}
        </button>
    </div>

    {{-- Ledger table --}}
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('accountant.transactions_col_date') }}
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('accountant.transactions_col_type') }}
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('accountant.transactions_col_athlete') }}
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('accountant.transactions_col_coach') }}
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('accountant.transactions_col_session') }}
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('accountant.transactions_col_booking_status') }}
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('accountant.transactions_col_session_status') }}
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('accountant.transactions_col_invoice_exists') }}
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('accountant.transactions_col_payout_stmt_exists') }}
                        </th>
                        <th scope="col" class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('accountant.transactions_col_amount_ttc') }}
                        </th>
                        <th scope="col" class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('accountant.transactions_col_commission') }}
                        </th>
                        <th scope="col" class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('accountant.transactions_col_payment_fee') }}
                        </th>
                        <th scope="col" class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('accountant.transactions_col_payout') }}
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('accountant.transactions_col_stripe_pi') }}
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('accountant.transactions_col_stripe_cs') }}
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('accountant.transactions_col_refund_status') }}
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('accountant.transactions_col_anomaly') }}
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                    @forelse ($bookings as $booking)
                        @php
                            $invoice = $booking->sportSession?->invoices?->first();
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            {{-- Date --}}
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                {{ $booking->created_at->format('Y-m-d') }}
                            </td>

                            {{-- Type --}}
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                {{ $booking->status === \App\Enums\BookingStatus::Refunded ? 'refund' : 'booking' }}
                            </td>

                            {{-- Athlete --}}
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                {{ $booking->athlete?->name ?? __('accountant.transactions_missing_value') }}
                            </td>

                            {{-- Coach --}}
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                {{ $booking->sportSession?->coach?->name ?? __('accountant.transactions_missing_value') }}
                            </td>

                            {{-- Session title --}}
                            <td class="max-w-[180px] truncate px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                {{ $booking->sportSession?->title ?? __('accountant.transactions_missing_value') }}
                            </td>

                            {{-- Booking status --}}
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                @php
                                    $statusClasses = match ($booking->status) {
                                        \App\Enums\BookingStatus::PendingPayment => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                        \App\Enums\BookingStatus::Confirmed => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                        \App\Enums\BookingStatus::Cancelled => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
                                        \App\Enums\BookingStatus::Refunded => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                    };
                                @endphp
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusClasses }}">
                                    {{ $booking->status->label() }}
                                </span>
                            </td>

                            {{-- Session status --}}
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                {{ $booking->sportSession?->status?->label() ?? __('accountant.transactions_missing_value') }}
                            </td>

                            {{-- Invoice exists --}}
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                @if ($invoice !== null)
                                    <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-200">{{ __('common.yes') }}</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">{{ __('common.no') }}</span>
                                @endif
                            </td>

                            {{-- Payout statement exists --}}
                            @php
                                $hasPayoutStmt = $booking->sportSession?->coach_id !== null
                                    && \App\Models\CoachPayoutStatement::where('coach_id', $booking->sportSession->coach_id)
                                        ->where('period_month', $booking->created_at->month)
                                        ->where('period_year', $booking->created_at->year)
                                        ->exists();
                            @endphp
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                @if ($hasPayoutStmt)
                                    <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-200">{{ __('common.yes') }}</span>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">{{ __('accountant.transactions_missing_value') }}</span>
                                @endif
                            </td>

                            {{-- Gross TTC --}}
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-900 dark:text-gray-100">
                                @if ($booking->amount_paid !== null)
                                    <x-money :cents="$booking->amount_paid" />
                                @else
                                    {{ __('accountant.transactions_missing_value') }}
                                @endif
                            </td>

                            {{-- Commission --}}
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700 dark:text-gray-300">
                                @if ($invoice !== null)
                                    <x-money :cents="$invoice->commission_amount" />
                                @else
                                    {{ __('accountant.transactions_missing_value') }}
                                @endif
                            </td>

                            {{-- Payment fee (stripe_fee) --}}
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700 dark:text-gray-300">
                                @if ($invoice !== null)
                                    <x-money :cents="$invoice->stripe_fee" />
                                @else
                                    {{ __('accountant.transactions_missing_value') }}
                                @endif
                            </td>

                            {{-- Coach payout --}}
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700 dark:text-gray-300">
                                @if ($invoice !== null)
                                    <x-money :cents="$invoice->coach_payout" />
                                @else
                                    {{ __('accountant.transactions_missing_value') }}
                                @endif
                            </td>

                            {{-- Stripe Payment Intent ID --}}
                            <td class="max-w-[120px] truncate px-4 py-3 font-mono text-xs text-gray-500 dark:text-gray-400">
                                {{ $booking->stripe_payment_intent_id ?? __('accountant.transactions_missing_value') }}
                            </td>

                            {{-- Stripe Checkout Session ID --}}
                            <td class="max-w-[120px] truncate px-4 py-3 font-mono text-xs text-gray-500 dark:text-gray-400">
                                {{ $booking->stripe_checkout_session_id ?? __('accountant.transactions_missing_value') }}
                            </td>

                            {{-- Refund status --}}
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                @if ($booking->refunded_at !== null)
                                    {{ __('accountant.transactions_refunded_at', ['date' => $booking->refunded_at->format('Y-m-d')]) }}
                                @else
                                    {{ __('accountant.transactions_missing_value') }}
                                @endif
                            </td>

                            {{-- Anomaly flags (Story 1.5) --}}
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                @php $flags = $bookingFlags[$booking->id] ?? []; @endphp
                                @if (!empty($flags['has_anomaly']))
                                    <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900 dark:text-red-200">
                                        @if (!empty($flags['missing_payment_intent']))
                                            {{ __('accountant.anomaly_missing_payment_intent') }}
                                        @elseif (!empty($flags['confirmed_without_payment']))
                                            {{ __('accountant.anomaly_confirmed_without_payment') }}
                                        @elseif (!empty($flags['paid_cancelled_without_refund']))
                                            {{ __('accountant.anomaly_paid_cancelled_without_refund') }}
                                        @endif
                                    </span>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500">{{ __('accountant.transactions_missing_value') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="17"
                                class="px-4 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                {{ __('accountant.transactions_no_results') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($bookings->hasPages())
            <div class="border-t border-gray-200 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-800">
                {{ $bookings->links() }}
            </div>
        @endif
    </div>
</div>
