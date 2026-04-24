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

        @if ($canBook)
            <button
                type="button"
                wire:click="book"
                wire:loading.attr="disabled"
                class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-70">
                <span wire:loading.remove wire:target="book">{{ __('bookings.book_action') }}</span>
                <span wire:loading wire:target="book">{{ __('bookings.processing') }}</span>
            </button>
        @endif
    </div>
</section>
