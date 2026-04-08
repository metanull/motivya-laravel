<div class="flex min-h-[60vh] items-center justify-center">
    <div class="w-full max-w-md space-y-8 rounded-lg bg-white p-8 shadow dark:bg-gray-800">
        <div>
            <h2 class="text-center text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                {{ __('auth.two_factor_heading') }}
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
                @if ($useRecoveryCode)
                    {{ __('auth.two_factor_recovery_description') }}
                @else
                    {{ __('auth.two_factor_description') }}
                @endif
            </p>
        </div>

        <form method="POST" action="{{ url('/two-factor-challenge') }}" class="space-y-6">
            @csrf

            @if ($useRecoveryCode)
                {{-- Recovery Code --}}
                <div>
                    <label for="recovery_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('auth.two_factor_recovery_code') }}
                    </label>
                    <input
                        type="text"
                        id="recovery_code"
                        name="recovery_code"
                        autocomplete="one-time-code"
                        required
                        autofocus
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                    />
                    @error('recovery_code')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            @else
                {{-- TOTP Code --}}
                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('auth.two_factor_code') }}
                    </label>
                    <input
                        type="text"
                        id="code"
                        name="code"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        required
                        autofocus
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                    />
                    @error('code')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            {{-- Submit --}}
            <button
                type="submit"
                class="flex w-full justify-center rounded-md bg-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
            >
                {{ __('auth.two_factor_submit') }}
            </button>
        </form>

        {{-- Toggle recovery code / TOTP --}}
        <p class="text-center text-sm">
            <button
                type="button"
                wire:click="toggleRecoveryCode"
                class="font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400"
            >
                @if ($useRecoveryCode)
                    {{ __('auth.two_factor_use_code') }}
                @else
                    {{ __('auth.two_factor_use_recovery') }}
                @endif
            </button>
        </p>
    </div>
</div>
