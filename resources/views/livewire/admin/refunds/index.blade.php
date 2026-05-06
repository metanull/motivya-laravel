<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

    {{-- Page heading --}}
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                {{ __('admin.refunds_heading') }}
            </h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                {{ __('admin.refunds_subtitle') }}
            </p>
        </div>
        <a
            href="{{ route('admin.sessions.index') }}"
            class="inline-flex items-center gap-1.5 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
            wire:navigate
        >
            &larr; {{ __('admin.sessions_heading') }}
        </a>
    </div>

    {{-- Status filter --}}
    <div class="mb-6 rounded-lg bg-white p-4 shadow dark:bg-gray-800">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300">
                    {{ __('admin.refunds_filter_status') }}
                </label>
                <select
                    wire:model.live="statusFilter"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                >
                    <option value="">{{ __('admin.refunds_filter_all_statuses') }}</option>
                    @foreach ($statuses as $s)
                        <option value="{{ $s->value }}">{{ $s->label() }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- Booking list --}}
    @if ($bookings->isEmpty())
        <div class="rounded-lg bg-white p-8 text-center shadow dark:bg-gray-800">
            <p class="text-gray-500 dark:text-gray-400">{{ __('admin.refunds_no_results') }}</p>
        </div>
    @else

        {{-- Desktop table --}}
        <div class="hidden overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800 lg:block">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('admin.refunds_col_booking') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('admin.refunds_col_athlete') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('admin.refunds_col_session') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('admin.refunds_col_status') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('admin.refunds_col_eligibility') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('admin.refunds_col_amount') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('admin.refunds_col_date') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('admin.refunds_col_last_audit') }}
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            {{ __('admin.refunds_col_actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                    @foreach ($bookings as $booking)
                        @php
                            $eligibility = $this->eligibilityBadge($booking, $bookingFlags[$booking->id] ?? []);
                            $lastAudit = $lastAudits[$booking->id] ?? null;
                        @endphp
                        <tr wire:key="booking-row-{{ $booking->id }}">
                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">
                                #{{ $booking->id }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                {{ $booking->athlete?->name ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                {{ $booking->sportSession?->title ?? '—' }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <span @class([
                                    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                                    'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' => $booking->status === \App\Enums\BookingStatus::Confirmed,
                                    'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' => $booking->status === \App\Enums\BookingStatus::PendingPayment,
                                    'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' => $booking->status === \App\Enums\BookingStatus::Cancelled,
                                    'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' => $booking->status === \App\Enums\BookingStatus::Refunded,
                                ])>
                                    {{ $booking->status->label() }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <span @class([
                                    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                                    'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' => $eligibility === 'eligible',
                                    'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' => $eligibility === 'missing_payment_intent',
                                    'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' => in_array($eligibility, ['already_refunded', 'unpaid', 'pending_payment', 'cancelled']),
                                ])>
                                    {{ __('admin.refunds_badge_' . $eligibility) }}
                                </span>
                                @if ($eligibility === 'missing_payment_intent' && Route::has('admin.anomalies.index'))
                                    <a href="{{ route('admin.anomalies.index') }}"
                                       class="ml-1 text-xs text-indigo-600 hover:text-indigo-500 dark:text-indigo-400"
                                       wire:navigate>↗</a>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                &euro;{{ number_format($booking->amount_paid / 100, 2) }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                {{ $booking->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                @if ($lastAudit)
                                    <span @class([
                                        'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                                        'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' => $lastAudit->status === \App\Enums\RefundAuditStatus::Succeeded,
                                        'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' => $lastAudit->status === \App\Enums\RefundAuditStatus::Failed,
                                        'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' => $lastAudit->status === \App\Enums\RefundAuditStatus::Attempted,
                                    ])>
                                        {{ $lastAudit->status->label() }}
                                    </span>
                                    @if ($lastAudit->error_message)
                                        <p class="mt-0.5 text-xs text-red-600 dark:text-red-400">{{ Str::limit($lastAudit->error_message, 60) }}</p>
                                    @endif
                                @else
                                    <span class="text-xs text-gray-400 dark:text-gray-500">{{ __('admin.refunds_no_attempts') }}</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm">

                                {{-- Inline refund form --}}
                                @if ($refundingBookingId === $booking->id)
                                    <div class="mt-2 rounded-md border border-orange-200 bg-orange-50 p-4 text-left dark:border-orange-800 dark:bg-orange-900/20">
                                        <label
                                            for="refundReason-{{ $booking->id }}"
                                            class="block text-sm font-medium text-orange-800 dark:text-orange-200"
                                        >
                                            {{ __('admin.refunds_refund_reason_label') }}
                                        </label>
                                        <textarea
                                            wire:model="refundReason"
                                            id="refundReason-{{ $booking->id }}"
                                            rows="3"
                                            class="mt-1 block w-full rounded-md border-orange-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 dark:border-orange-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                                            placeholder="{{ __('admin.refunds_refund_reason_placeholder') }}"
                                        ></textarea>
                                        @error('refundReason')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                        <div class="mt-3 flex gap-2">
                                            <button
                                                type="button"
                                                wire:click="processRefund({{ $booking->id }})"
                                                class="rounded-md bg-orange-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-500"
                                            >
                                                {{ __('admin.refunds_confirm_btn') }}
                                            </button>
                                            <button
                                                type="button"
                                                wire:click="cancelRefundAction"
                                                class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300"
                                            >
                                                {{ __('common.cancel') }}
                                            </button>
                                        </div>
                                    </div>
                                @elseif ($eligibility === 'eligible')
                                    <button
                                        type="button"
                                        wire:click="confirmRefund({{ $booking->id }})"
                                        class="rounded-md bg-orange-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-500"
                                    >
                                        {{ __('admin.refunds_action_refund') }}
                                    </button>
                                @elseif ($eligibility === 'missing_payment_intent')
                                    {{-- Reconciliation needed — refund is technically possible but risky. --}}
                                    <button
                                        type="button"
                                        wire:click="confirmRefund({{ $booking->id }})"
                                        class="rounded-md border border-orange-300 bg-white px-2 py-1 text-xs font-medium text-orange-700 hover:bg-orange-50 dark:border-orange-700 dark:bg-gray-700 dark:text-orange-300"
                                    >
                                        {{ __('admin.refunds_action_refund') }}
                                    </button>
                                @else
                                    <span class="text-xs text-gray-400 dark:text-gray-500">
                                        {{ __('admin.refunds_not_eligible') }}
                                    </span>
                                @endif

                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Mobile card list --}}
        <div class="space-y-4 lg:hidden">
            @foreach ($bookings as $booking)
                @php
                    $eligibility = $this->eligibilityBadge($booking, $bookingFlags[$booking->id] ?? []);
                    $lastAudit = $lastAudits[$booking->id] ?? null;
                @endphp
                <div
                    class="rounded-lg bg-white p-4 shadow dark:bg-gray-800"
                    wire:key="booking-card-{{ $booking->id }}"
                >
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                #{{ $booking->id }} &mdash; {{ $booking->athlete?->name ?? '—' }}
                            </p>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                {{ $booking->sportSession?->title ?? '—' }}
                            </p>
                        </div>
                        <span @class([
                            'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                            'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' => $booking->status === \App\Enums\BookingStatus::Confirmed,
                            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' => $booking->status === \App\Enums\BookingStatus::PendingPayment,
                            'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' => $booking->status === \App\Enums\BookingStatus::Cancelled,
                            'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' => $booking->status === \App\Enums\BookingStatus::Refunded,
                        ])>
                            {{ $booking->status->label() }}
                        </span>
                    </div>

                    {{-- Eligibility badge --}}
                    <div class="mt-2">
                        <span @class([
                            'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                            'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' => $eligibility === 'eligible',
                            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' => $eligibility === 'missing_payment_intent',
                            'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' => in_array($eligibility, ['already_refunded', 'unpaid', 'pending_payment', 'cancelled']),
                        ])>
                            {{ __('admin.refunds_badge_' . $eligibility) }}
                        </span>
                    </div>

                    <dl class="mt-3 grid grid-cols-2 gap-2 text-xs">
                        <div>
                            <dt class="font-medium text-gray-500 dark:text-gray-400">{{ __('admin.refunds_col_amount') }}</dt>
                            <dd class="text-gray-900 dark:text-gray-100">&euro;{{ number_format($booking->amount_paid / 100, 2) }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-gray-500 dark:text-gray-400">{{ __('admin.refunds_col_date') }}</dt>
                            <dd class="text-gray-900 dark:text-gray-100">{{ $booking->created_at->format('d/m/Y') }}</dd>
                        </div>
                        <div class="col-span-2">
                            <dt class="font-medium text-gray-500 dark:text-gray-400">{{ __('admin.refunds_col_last_audit') }}</dt>
                            <dd class="text-gray-900 dark:text-gray-100">
                                @if ($lastAudit)
                                    <span @class([
                                        'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                                        'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' => $lastAudit->status === \App\Enums\RefundAuditStatus::Succeeded,
                                        'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' => $lastAudit->status === \App\Enums\RefundAuditStatus::Failed,
                                        'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' => $lastAudit->status === \App\Enums\RefundAuditStatus::Attempted,
                                    ])>
                                        {{ $lastAudit->status->label() }}
                                    </span>
                                    @if ($lastAudit->error_message)
                                        <p class="mt-0.5 text-xs text-red-600 dark:text-red-400">{{ Str::limit($lastAudit->error_message, 50) }}</p>
                                    @endif
                                @else
                                    <span class="text-xs text-gray-400 dark:text-gray-500">{{ __('admin.refunds_no_attempts') }}</span>
                                @endif
                            </dd>
                        </div>
                    </dl>

                    {{-- Inline refund form (mobile) --}}
                    @if ($refundingBookingId === $booking->id)
                        <div class="mt-3 rounded-md border border-orange-200 bg-orange-50 p-3 dark:border-orange-800 dark:bg-orange-900/20">
                            <label
                                for="refundReasonMobile-{{ $booking->id }}"
                                class="block text-sm font-medium text-orange-800 dark:text-orange-200"
                            >
                                {{ __('admin.refunds_refund_reason_label') }}
                            </label>
                            <textarea
                                wire:model="refundReason"
                                id="refundReasonMobile-{{ $booking->id }}"
                                rows="3"
                                class="mt-1 block w-full rounded-md border-orange-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 dark:border-orange-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                                placeholder="{{ __('admin.refunds_refund_reason_placeholder') }}"
                            ></textarea>
                            @error('refundReason')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                            <div class="mt-3 flex gap-2">
                                <button
                                    type="button"
                                    wire:click="processRefund({{ $booking->id }})"
                                    class="rounded-md bg-orange-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-500"
                                >
                                    {{ __('admin.refunds_confirm_btn') }}
                                </button>
                                <button
                                    type="button"
                                    wire:click="cancelRefundAction"
                                    class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300"
                                >
                                    {{ __('common.cancel') }}
                                </button>
                            </div>
                        </div>
                    @elseif ($eligibility === 'eligible')
                        <div class="mt-3">
                            <button
                                type="button"
                                wire:click="confirmRefund({{ $booking->id }})"
                                class="w-full rounded-md bg-orange-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-500"
                            >
                                {{ __('admin.refunds_action_refund') }}
                            </button>
                        </div>
                    @elseif ($eligibility === 'missing_payment_intent')
                        <div class="mt-3 flex flex-col gap-1">
                            <button
                                type="button"
                                wire:click="confirmRefund({{ $booking->id }})"
                                class="rounded-md border border-orange-300 bg-white px-2 py-1 text-xs font-medium text-orange-700 hover:bg-orange-50 dark:border-orange-700 dark:bg-gray-700 dark:text-orange-300"
                            >
                                {{ __('admin.refunds_action_refund') }}
                            </button>
                        </div>
                    @else
                        <p class="mt-3 text-xs text-gray-400 dark:text-gray-500">
                            {{ __('admin.refunds_not_eligible') }}
                        </p>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $bookings->links() }}
        </div>

    @endif
</div>
