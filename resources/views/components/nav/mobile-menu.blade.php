{{-- Off-canvas mobile navigation --}}
<div
    x-show="mobileMenuOpen"
    x-on:click.outside="mobileMenuOpen = false"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 -translate-y-2"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 -translate-y-2"
    class="sm:hidden"
    id="mobile-menu"
    style="display: none;"
>
    <div class="space-y-1 border-t border-gray-200 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-800">
        {{-- Main links --}}
        <a href="#"
           class="block rounded-md px-3 py-2 text-base font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-200 dark:hover:bg-gray-700">
            {{ __('common.nav.sessions') }}
        </a>

        @auth
            <a href="#"
               class="block rounded-md px-3 py-2 text-base font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-200 dark:hover:bg-gray-700">
                {{ __('common.nav.bookings') }}
            </a>
            <a href="#"
               class="block rounded-md px-3 py-2 text-base font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-200 dark:hover:bg-gray-700">
                {{ __('common.nav.profile') }}
            </a>

            <div class="border-t border-gray-200 pt-3 dark:border-gray-700">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button
                        type="submit"
                        class="block w-full rounded-md px-3 py-2 text-left text-base font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-200 dark:hover:bg-gray-700"
                    >
                        {{ __('common.nav.logout') }}
                    </button>
                </form>
            </div>
        @else
            <div class="border-t border-gray-200 pt-3 dark:border-gray-700">
                <a href="{{ route('login') }}"
                   class="block rounded-md px-3 py-2 text-base font-medium text-gray-700 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-200 dark:hover:bg-gray-700">
                    {{ __('common.nav.login') }}
                </a>
                <a href="{{ route('register') }}"
                   class="block rounded-md px-3 py-2 text-base font-medium text-indigo-600 hover:bg-indigo-50 dark:text-indigo-400 dark:hover:bg-gray-700">
                    {{ __('common.nav.register') }}
                </a>
            </div>
        @endauth

        {{-- Locale switcher --}}
        <div class="border-t border-gray-200 pt-3 dark:border-gray-700">
            <x-nav.locale-switcher />
        </div>
    </div>
</div>
