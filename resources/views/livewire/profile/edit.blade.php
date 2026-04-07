<div class="mx-auto max-w-3xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
        {{ __('profile.heading') }}
    </h1>

    {{-- Personal Information --}}
    <section class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('profile.info_heading') }}
        </h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('profile.info_description') }}
        </p>

        <form method="POST" action="{{ route('user-profile-information.update') }}" class="mt-6 space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('profile.info_name') }}
                </label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name', $user->name) }}"
                    required
                    autocomplete="name"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                />
                @error('name', 'updateProfileInformation')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('profile.info_email') }}
                </label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email', $user->email) }}"
                    required
                    autocomplete="email"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                />
                @error('email', 'updateProfileInformation')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-4">
                <button
                    type="submit"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    {{ __('profile.info_submit') }}
                </button>

                @if (session('status') === 'profile-information-updated')
                    <p class="text-sm text-green-600 dark:text-green-400">{{ __('profile.info_saved') }}</p>
                @endif
            </div>
        </form>
    </section>

    {{-- Update Password --}}
    <section class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('profile.password_heading') }}
        </h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('profile.password_description') }}
        </p>

        <form method="POST" action="{{ route('user-password.update') }}" class="mt-6 space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="current_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('profile.password_current') }}
                </label>
                <input
                    type="password"
                    id="current_password"
                    name="current_password"
                    required
                    autocomplete="current-password"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                />
                @error('current_password', 'updatePassword')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('profile.password_new') }}
                </label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    autocomplete="new-password"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                />
                @error('password', 'updatePassword')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('profile.password_confirm') }}
                </label>
                <input
                    type="password"
                    id="password_confirmation"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                />
                @error('password_confirmation', 'updatePassword')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-4">
                <button
                    type="submit"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    {{ __('profile.password_submit') }}
                </button>

                @if (session('status') === 'password-updated')
                    <p class="text-sm text-green-600 dark:text-green-400">{{ __('profile.password_saved') }}</p>
                @endif
            </div>
        </form>
    </section>

    {{-- Locale Preference --}}
    <section class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('profile.locale_heading') }}
        </h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('profile.locale_description') }}
        </p>

        <form wire:submit="updateLocale" class="mt-6 space-y-4">
            <div>
                <label for="locale" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('profile.locale_label') }}
                </label>
                <select
                    wire:model="locale"
                    id="locale"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                >
                    <option value="fr">{{ __('profile.locale_fr') }}</option>
                    <option value="en">{{ __('profile.locale_en') }}</option>
                    <option value="nl">{{ __('profile.locale_nl') }}</option>
                </select>
                @error('locale')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <button
                    type="submit"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    {{ __('profile.locale_submit') }}
                </button>
            </div>
        </form>
    </section>

    {{-- Two-Factor Authentication (placeholder) --}}
    <section class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('profile.twofa_heading') }}
        </h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('profile.twofa_description') }}
        </p>

        <div class="mt-4 rounded-md bg-gray-50 p-4 dark:bg-gray-700">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('profile.twofa_not_available') }}
            </p>
        </div>
    </section>
</div>
