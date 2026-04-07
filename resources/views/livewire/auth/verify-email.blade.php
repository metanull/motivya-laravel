<div class="flex min-h-[60vh] items-center justify-center">
    <div class="w-full max-w-md space-y-8 rounded-lg bg-white p-8 shadow dark:bg-gray-800">
        <div>
            <h2 class="text-center text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                {{ __('auth.verify_heading') }}
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
                {{ __('auth.verify_description') }}
            </p>
        </div>

        @if ($resent)
            <div class="rounded-md bg-green-50 p-4 dark:bg-green-900/20">
                <p class="text-sm text-green-700 dark:text-green-400">
                    {{ __('auth.verify_resent') }}
                </p>
            </div>
        @endif

        <div class="flex items-center justify-between">
            <button
                wire:click="resend"
                wire:loading.attr="disabled"
                wire:loading.class="opacity-50 cursor-not-allowed"
                class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
            >
                <span wire:loading.remove wire:target="resend">{{ __('auth.verify_resend') }}</span>
                <span wire:loading wire:target="resend" class="inline-flex items-center">
                    <svg class="mr-2 h-4 w-4 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    {{ __('auth.verify_resend') }}
                </span>
            </button>

            <button
                wire:click="logout"
                class="text-sm font-medium text-gray-600 hover:text-gray-500 dark:text-gray-400 dark:hover:text-gray-300"
            >
                {{ __('auth.verify_logout') }}
            </button>
        </div>
    </div>
</div>
