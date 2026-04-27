<x-layouts.app>
    <x-slot:title>{{ __('common.welcome', ['app' => config('app.name')]) }}</x-slot:title>

    <div class="flex flex-col items-center justify-center gap-8 py-16">

        {{-- Hero --}}
        <div class="text-center">
            <h1 class="text-4xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-5xl">
                {{ config('app.name') }}
            </h1>
            <p class="mt-4 text-lg text-gray-600 dark:text-gray-300">
                {{ __('common.welcome', ['app' => config('app.name')]) }}
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

    </div>
</x-layouts.app>
