<div class="flex min-h-[60vh] items-center justify-center">
    <div class="w-full max-w-md space-y-8 rounded-lg bg-white p-8 shadow dark:bg-gray-800">
        <div>
            <h2 class="text-center text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                {{ __('auth.forgot_heading') }}
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
                {{ __('auth.forgot_description') }}
            </p>
        </div>

        @if ($linkSent)
            <div class="rounded-md bg-green-50 p-4 dark:bg-green-900/20">
                <p class="text-sm text-green-700 dark:text-green-400">
                    {{ __('auth.forgot_link_sent') }}
                </p>
            </div>
        @endif

        <form wire:submit="sendResetLink" class="space-y-6">
            {{-- Email --}}
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('auth.forgot_email') }}
                </label>
                <input
                    wire:model.blur="email"
                    type="email"
                    id="email"
                    autocomplete="email"
                    required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                />
                @error('email')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Submit --}}
            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:loading.class="opacity-50 cursor-not-allowed"
                class="flex w-full justify-center rounded-md bg-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
            >
                <span wire:loading.remove>{{ __('auth.forgot_submit') }}</span>
                <span wire:loading class="inline-flex items-center">
                    <svg class="mr-2 h-4 w-4 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    {{ __('auth.forgot_submit') }}
                </span>
            </button>
        </form>

        {{-- Back to login --}}
        <p class="text-center text-sm">
            <a href="{{ route('login') }}" class="font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400" wire:navigate>
                {{ __('auth.forgot_back_to_login') }}
            </a>
        </p>
    </div>
</div>
