<div class="flex min-h-[60vh] items-center justify-center">
    <div class="w-full max-w-md space-y-8 rounded-lg bg-white p-8 shadow dark:bg-gray-800">
        <div>
            <h2 class="text-center text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                {{ __('auth.register_heading') }}
            </h2>
        </div>

        <form wire:submit="register" class="space-y-6">
            {{-- Name --}}
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('auth.register_name') }}
                </label>
                <input
                    wire:model.blur="form.name"
                    type="text"
                    id="name"
                    autocomplete="name"
                    required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                />
                @error('form.name')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Email --}}
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('auth.register_email') }}
                </label>
                <input
                    wire:model.blur="form.email"
                    type="email"
                    id="email"
                    autocomplete="email"
                    required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                />
                @error('form.email')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Password --}}
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('auth.register_password') }}
                </label>
                <input
                    wire:model.blur="form.password"
                    type="password"
                    id="password"
                    autocomplete="new-password"
                    required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                />
                @error('form.password')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Password Confirmation --}}
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('auth.register_password_confirmation') }}
                </label>
                <input
                    wire:model.blur="form.password_confirmation"
                    type="password"
                    id="password_confirmation"
                    autocomplete="new-password"
                    required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                />
                @error('form.password_confirmation')
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
                <span wire:loading.remove>{{ __('auth.register_submit') }}</span>
                <span wire:loading class="inline-flex items-center">
                    <svg class="mr-2 h-4 w-4 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    {{ __('auth.register_submit') }}
                </span>
            </button>
        </form>

        {{-- Login link --}}
        <p class="text-center text-sm text-gray-600 dark:text-gray-400">
            {{ __('auth.register_has_account') }}
            <a href="{{ route('login') }}" class="font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400" wire:navigate>
                {{ __('auth.register_login_link') }}
            </a>
        </p>
    </div>
</div>
