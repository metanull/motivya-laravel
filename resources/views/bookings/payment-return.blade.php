<x-layouts.app>
    <x-slot:title>{{ __('bookings.payment_return_title') }}</x-slot:title>

    <div class="mx-auto max-w-lg px-4 py-12">
        @if ($paymentStatus === 'success')
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
        @else
            <div class="rounded-lg bg-amber-50 p-8 text-center shadow-sm ring-1 ring-amber-100 dark:bg-gray-800 dark:ring-gray-700">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900">
                    <svg class="h-8 w-8 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                    {{ __('bookings.payment_return_cancel_title') }}
                </h1>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                    {{ __('bookings.payment_return_cancel_body') }}
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
