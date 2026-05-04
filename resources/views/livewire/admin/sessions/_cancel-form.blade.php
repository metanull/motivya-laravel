<div class="rounded-md border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
    <label
        for="cancelReason-{{ $session->id }}"
        class="block text-sm font-medium text-red-800 dark:text-red-200"
    >
        {{ __('admin.sessions_cancel_reason_label') }}
    </label>

    <textarea
        wire:model="cancelReason"
        id="cancelReason-{{ $session->id }}"
        rows="3"
        class="mt-1 block w-full rounded-md border-red-300 shadow-sm focus:border-red-500 focus:ring-red-500 dark:border-red-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
        placeholder="{{ __('admin.sessions_cancel_reason_placeholder') }}"
    ></textarea>

    @error('cancelReason')
        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror

    <div class="mt-3 flex gap-2">
        <button
            type="button"
            wire:click="cancelSession({{ $session->id }})"
            class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500"
        >
            {{ __('admin.sessions_cancel_confirm_btn') }}
        </button>
        <button
            type="button"
            wire:click="cancelCancelAction"
            class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300"
        >
            {{ __('common.cancel') }}
        </button>
    </div>
</div>
