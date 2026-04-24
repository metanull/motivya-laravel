<div>
    @if ($booking)
        <section class="mt-6 rounded-lg bg-amber-50 p-6 shadow-sm ring-1 ring-amber-100 dark:bg-gray-800 dark:ring-gray-700">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="space-y-3">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {{ __('bookings.cancel_card_title') }}
                        </h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                            {{ $refundEligible
                                ? __('bookings.cancel_refund_eligible_notice', ['hours' => $refundWindowHours])
                                : __('bookings.cancel_refund_ineligible_notice', ['hours' => $refundWindowHours]) }}
                        </p>
                    </div>

                    <dl class="grid grid-cols-1 gap-3 text-sm text-gray-700 dark:text-gray-200 sm:grid-cols-2">
                        <div>
                            <dt class="font-medium text-gray-500 dark:text-gray-400">{{ __('bookings.booking_status_label') }}</dt>
                            <dd class="mt-1 font-medium">{{ $booking->status->label() }}</dd>
                        </div>

                        <div>
                            <dt class="font-medium text-gray-500 dark:text-gray-400">{{ __('bookings.refund_window_label') }}</dt>
                            <dd class="mt-1">
                                {{ __('bookings.refund_window_value', ['hours' => $refundWindowHours]) }}
                            </dd>
                        </div>
                    </dl>
                </div>

                @if ($canCancel)
                    <button
                        type="button"
                        wire:click="confirmCancellation"
                        class="inline-flex items-center justify-center rounded-md bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-500">
                        {{ __('bookings.cancel_action') }}
                    </button>
                @endif
            </div>

            @if ($confirmingCancellation)
                <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
                    <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl dark:bg-gray-900">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {{ __('bookings.cancel_confirmation_title') }}
                        </h3>

                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                            {{ $refundEligible
                                ? __('bookings.cancel_confirmation_body_refund')
                                : __('bookings.cancel_confirmation_body_no_refund') }}
                        </p>

                        <div class="mt-6 flex justify-end gap-3">
                            <button
                                type="button"
                                wire:click="closeConfirmation"
                                class="inline-flex items-center justify-center rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800">
                                {{ __('bookings.cancel_keep_action') }}
                            </button>

                            <button
                                type="button"
                                wire:click="cancel"
                                wire:loading.attr="disabled"
                                class="inline-flex items-center justify-center rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-500 disabled:cursor-not-allowed disabled:opacity-70">
                                <span wire:loading.remove wire:target="cancel">{{ __('bookings.cancel_confirm_action') }}</span>
                                <span wire:loading wire:target="cancel">{{ __('bookings.processing') }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            @endif
        </section>
    @endif
</div>
