{{-- Top navigation bar --}}
<nav
    x-data="{ mobileMenuOpen: false }"
    class="border-b border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800"
    aria-label="{{ __('common.open_main_menu') }}"
>
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">

            {{-- Logo --}}
            <div class="flex items-center">
                <a href="{{ route('home') }}" class="text-xl font-bold text-indigo-600 dark:text-indigo-400">
                    {{ config('app.name') }}
                </a>
            </div>

            {{-- Desktop links --}}
            <div class="hidden items-center gap-6 sm:flex">
                @auth
                    @can('access-coach-panel')
                        <a
                            href="{{ route('coach.sessions.create') }}"
                            @class([
                                'text-sm font-medium',
                                'text-indigo-600 dark:text-indigo-400' => request()->routeIs('coach.sessions.*'),
                                'text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white' => ! request()->routeIs('coach.sessions.*'),
                            ])
                            wire:navigate
                        >
                            {{ __('common.nav.sessions') }}
                        </a>
                    @else
                        <a
                            href="{{ route('sessions.index') }}"
                            @class([
                                'text-sm font-medium',
                                'text-indigo-600 dark:text-indigo-400' => request()->routeIs('sessions.*'),
                                'text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white' => ! request()->routeIs('sessions.*'),
                            ])
                            wire:navigate
                        >
                            {{ __('common.nav.sessions') }}
                        </a>
                    @endcan
                @else
                    <a
                        href="{{ route('sessions.index') }}"
                        @class([
                            'text-sm font-medium',
                            'text-indigo-600 dark:text-indigo-400' => request()->routeIs('sessions.*'),
                            'text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white' => ! request()->routeIs('sessions.*'),
                        ])
                        wire:navigate
                    >
                        {{ __('common.nav.sessions') }}
                    </a>
                @endauth

                @auth
                    <x-nav.user-menu />
                @else
                    <a
                        href="{{ route('login') }}"
                        class="text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white"
                    >
                        {{ __('common.nav.login') }}
                    </a>
                    <a
                        href="{{ route('register') }}"
                        class="inline-flex h-9 items-center rounded-md bg-indigo-600 px-4 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        {{ __('common.nav.register') }}
                    </a>
                @endauth

                <x-nav.locale-switcher />
            </div>

            {{-- Mobile hamburger button --}}
            <div class="flex sm:hidden">
                <button
                    x-on:click="mobileMenuOpen = !mobileMenuOpen"
                    type="button"
                    class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500 dark:hover:bg-gray-700"
                    :aria-expanded="mobileMenuOpen"
                    aria-controls="mobile-menu"
                    aria-label="{{ __('common.open_main_menu') }}"
                >
                    {{-- Hamburger icon --}}
                    <svg x-show="!mobileMenuOpen" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                    {{-- Close icon --}}
                    <svg x-show="mobileMenuOpen" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" style="display: none;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Mobile menu panel (scoped inside x-data) --}}
    <x-nav.mobile-menu />
</nav>
