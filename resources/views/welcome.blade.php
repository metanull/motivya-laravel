<x-layouts.app>
    <x-slot:title>{{ __('common.welcome', ['app' => config('app.name')]) }}</x-slot:title>

    <div class="flex flex-col items-center justify-center gap-10 py-16 px-4">

        {{-- Hero --}}
        <div class="text-center">
            <h1 class="text-4xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-5xl">
                {{ config('app.name') }}
            </h1>
            <p class="mt-4 text-lg text-gray-600 dark:text-gray-300">
                {{ __('common.welcome_subtitle') }}
            </p>
        </div>

        {{-- Call to action --}}
        <div class="flex flex-col gap-3 sm:flex-row">
            <a
                href="{{ route('sessions.index') }}"
                class="inline-flex h-12 items-center justify-center rounded-md bg-indigo-600 px-6 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                wire:navigate
            >
                {{ __('common.nav.browse_sessions') }}
            </a>
            @guest
            <a
                href="{{ route('register') }}"
                class="inline-flex h-12 items-center justify-center rounded-md border border-gray-300 bg-white px-6 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
            >
                {{ __('common.nav.register') }}
            </a>
            <a
                href="{{ route('login') }}"
                class="inline-flex h-12 items-center justify-center rounded-md border border-gray-300 bg-white px-6 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
            >
                {{ __('common.nav.login') }}
            </a>
            @endguest
        </div>

        {{-- Role value propositions --}}
        <div class="mt-4 grid w-full max-w-3xl grid-cols-1 gap-6 sm:grid-cols-2">
            {{-- For athletes --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="mb-3 inline-flex h-10 w-10 items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-900">
                    <svg class="h-5 w-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('common.welcome_for_athletes') }}</p>
            </div>

            {{-- For coaches --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="mb-3 inline-flex h-10 w-10 items-center justify-center rounded-full bg-green-100 dark:bg-green-900">
                    <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                    </svg>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('common.welcome_for_coaches') }}</p>
                @can('apply-as-coach')
                    @if (Route::has('coach.apply'))
                        <a href="{{ route('coach.apply') }}" wire:navigate
                            class="mt-3 inline-block text-sm font-medium text-green-600 hover:text-green-500 dark:text-green-400">
                            {{ __('common.welcome_become_coach') }} →
                        </a>
                    @endif
                @endcan
            </div>
        </div>

    </div>
</x-layouts.app>
