<section class="mt-6 rounded-lg bg-indigo-50 p-6 shadow-sm ring-1 ring-indigo-100 dark:bg-gray-800 dark:ring-gray-700">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="space-y-3">
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    {{ __('bookings.card_title') }}
                </h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                    {{ $availabilityMessage }}
                </p>
            </div>

            <dl class="grid grid-cols-1 gap-3 text-sm text-gray-700 dark:text-gray-200 sm:grid-cols-3">
                <div>
                    <dt class="font-medium text-gray-500 dark:text-gray-400">{{ __('bookings.price_label') }}</dt>
                    <dd class="mt-1">
                        <x-money :cents="$sportSession->price_per_person" class="font-semibold" />
                    </dd>
                </div>

                <div>
                    <dt class="font-medium text-gray-500 dark:text-gray-400">{{ __('bookings.spots_remaining_label') }}</dt>
                    <dd class="mt-1">
                        {{ trans_choice('sessions.spots_remaining', $spotsRemaining, ['count' => $spotsRemaining]) }}
                    </dd>
                </div>

                <div>
                    <dt class="font-medium text-gray-500 dark:text-gray-400">
                        {{ $existingBooking ? __('bookings.booking_status_label') : __('bookings.session_status_label') }}
                    </dt>
                    <dd class="mt-1 font-medium">
                        {{ $existingBooking ? $existingBooking->status->label() : $sportSession->status->label() }}
                    </dd>
                </div>
            </dl>
        </div>

        @if ($isGuest)
            {{-- Guest CTA: prompt to log in or register --}}
            <div class="flex flex-col items-end gap-2">
                <a href="{{ route('login') }}"
                   class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500">
                    {{ __('bookings.guest_login_cta') }}
                </a>
                <a href="{{ route('register') }}"
                   class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                    {{ __('bookings.guest_register_cta') }}
                </a>
            </div>
        @elseif ($canBook)
            <button
                type="button"
                wire:click="openConfirmModal"
                wire:loading.attr="disabled"
                class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-70">
                <span wire:loading.remove wire:target="openConfirmModal">{{ __('bookings.book_action') }}</span>
                <span wire:loading wire:target="openConfirmModal">{{ __('bookings.processing') }}</span>
            </button>
        @endif
    </div>
</section>

{{-- Booking Confirmation Modal --}}
@if ($showConfirmModal)
    <div
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="confirm-modal-title">
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-gray-900">
            <h2 id="confirm-modal-title" class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                {{ __('bookings.confirm_modal_title') }}
            </h2>

            <dl class="mt-4 space-y-3 text-sm text-gray-700 dark:text-gray-200">
                <div class="flex justify-between gap-4">
                    <dt class="font-medium text-gray-500 dark:text-gray-400">{{ __('bookings.confirm_modal_session_label') }}</dt>
                    <dd class="text-right">{{ $sportSession->title }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="font-medium text-gray-500 dark:text-gray-400">{{ __('bookings.confirm_modal_coach_label') }}</dt>
                    <dd class="text-right">{{ $sportSession->coach->name }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="font-medium text-gray-500 dark:text-gray-400">{{ __('bookings.confirm_modal_date_label') }}</dt>
                    <dd class="text-right">
                        {{ $sportSession->date->translatedFormat('D j M Y') }}
                        {{ substr($sportSession->start_time, 0, 5) }} – {{ substr($sportSession->end_time, 0, 5) }}
                    </dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="font-medium text-gray-500 dark:text-gray-400">{{ __('bookings.confirm_modal_location_label') }}</dt>
                    <dd class="text-right">{{ $sportSession->location }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="font-medium text-gray-500 dark:text-gray-400">{{ __('bookings.confirm_modal_price_label') }}</dt>
                    <dd class="text-right font-semibold"><x-money :cents="$sportSession->price_per_person" /></dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="font-medium text-gray-500 dark:text-gray-400">{{ __('bookings.confirm_modal_payment_methods_label') }}</dt>
                    <dd class="text-right">{{ __('bookings.confirm_modal_payment_methods_value') }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="font-medium text-gray-500 dark:text-gray-400">{{ __('bookings.confirm_modal_cancellation_label') }}</dt>
                    <dd class="text-right">{{ $cancellationPolicy }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="font-medium text-gray-500 dark:text-gray-400">{{ __('bookings.confirm_modal_hold_expiry_label') }}</dt>
                    <dd class="text-right">{{ $paymentHoldExpiry }}</dd>
                </div>
            </dl>

            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button
                    type="button"
                    wire:click="$set('showConfirmModal', false)"
                    class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                    {{ __('bookings.confirm_modal_cancel_action') }}
                </button>
                <button
                    type="button"
                    wire:click="book"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-70">
                    <span wire:loading.remove wire:target="book">{{ __('bookings.confirm_modal_confirm_action') }}</span>
                    <span wire:loading wire:target="book">{{ __('bookings.processing') }}</span>
                </button>
            </div>
        </div>
    </div>
@endif
