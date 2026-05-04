<x-layouts.app>
    <x-slot:title>{{ __('bookings.payment_return_title') }}</x-slot:title>

    <div class="mx-auto max-w-lg px-4 py-12">
        @if ($paymentStatus === 'success')
            {{-- ── Success ─────────────────────────────────────────────────── --}}
            <div class="rounded-lg bg-green-50 p-8 text-center shadow-sm ring-1 ring-green-100 dark:bg-gray-800 dark:ring-gray-700">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-green-100 dark:bg-green-900">
                    <svg class="h-8 w-8 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                </div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                    {{ __('bookings.payment_return_success_title') }}
                </h1>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                    {{ __('bookings.payment_return_success_body') }}
                </p>
            </div>

        @elseif ($canRetry || $canCancelHold)
            {{-- ── Pending: retry / cancel-hold options ─────────────────────── --}}
            <div class="rounded-lg bg-amber-50 p-8 shadow-sm ring-1 ring-amber-100 dark:bg-gray-800 dark:ring-gray-700">
                <div class="mb-4 flex justify-center">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900">
                        <svg class="h-8 w-8 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </div>
                </div>
                <h1 class="text-center text-xl font-semibold text-gray-900 dark:text-gray-100">
                    {{ __('bookings.payment_return_pending_title') }}
                </h1>
                <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-300">
                    {{ __('bookings.payment_return_pending_body') }}
                </p>

                @if ($booking->payment_expires_at)
                    <p class="mt-3 text-center text-xs text-amber-700 dark:text-amber-400">
                        {{ __('bookings.payment_return_expires_at') }}:
                        <span class="font-semibold">{{ $booking->payment_expires_at->format('H:i') }}</span>
                    </p>
                @endif

                <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-center">
                    @if ($canRetry)
                        <form method="POST" action="{{ route('bookings.retry-payment', $booking) }}">
                            @csrf
                            <button type="submit"
                                class="inline-flex w-full items-center justify-center rounded-md bg-indigo-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 sm:w-auto">
                                {{ __('bookings.payment_return_retry_action') }}
                            </button>
                        </form>
                    @endif
                    @if ($canCancelHold)
                        <form method="POST" action="{{ route('bookings.cancel-hold', $booking) }}">
                            @csrf
                            <button type="submit"
                                class="inline-flex w-full items-center justify-center rounded-md bg-white px-5 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 transition hover:bg-gray-50 sm:w-auto dark:bg-gray-700 dark:text-gray-200 dark:ring-gray-600 dark:hover:bg-gray-600">
                                {{ __('bookings.payment_return_cancel_hold_action') }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>

        @else
            {{-- ── Failed / expired / already cancelled ────────────────────── --}}
            <div class="rounded-lg bg-red-50 p-8 text-center shadow-sm ring-1 ring-red-100 dark:bg-gray-800 dark:ring-gray-700">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-red-100 dark:bg-red-900">
                    <svg class="h-8 w-8 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                    {{ __('bookings.payment_return_failed_title') }}
                </h1>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                    {{ __('bookings.payment_return_failed_body') }}
                </p>
            </div>
        @endif

        <div class="mt-6 text-center">
            <a href="{{ route('sessions.show', $booking->sportSession) }}"
               class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500">
                {{ __('bookings.payment_return_back_to_session') }}
            </a>
        </div>
    </div>
</x-layouts.app>
