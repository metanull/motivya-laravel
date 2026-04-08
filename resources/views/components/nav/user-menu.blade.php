{{-- User avatar dropdown (logged-in state) --}}
<div x-data="{ open: false }" class="relative">
    <button
        x-on:click="open = !open"
        x-on:keydown.escape.window="open = false"
        type="button"
        class="flex items-center gap-2 rounded-full focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
        aria-haspopup="true"
        :aria-expanded="open"
        aria-label="{{ __('common.open_user_menu') }}"
    >
        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-indigo-600 text-sm font-medium text-white">
            {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
        </span>
        <span class="hidden text-sm font-medium text-gray-700 dark:text-gray-200 sm:block">
            {{ auth()->user()->name }}
        </span>
        <svg class="h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
        </svg>
    </button>

    {{-- Dropdown panel --}}
    <div
        x-show="open"
        x-on:click.outside="open = false"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black/5 focus:outline-none dark:bg-gray-800 dark:ring-white/10"
        role="menu"
        aria-orientation="vertical"
        aria-label="{{ __('common.user_menu') }}"
        style="display: none;"
    >
        @can('access-admin-panel')
            <a
                href="{{ route('admin.coach-approval') }}"
                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                role="menuitem"
                wire:navigate
            >
                {{ __('common.nav.admin_coach_approval') }}
            </a>
            <hr class="my-1 border-gray-200 dark:border-gray-700" />
        @endcan

        @can('apply-as-coach')
            <a
                href="{{ route('coach.apply') }}"
                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                role="menuitem"
                wire:navigate
            >
                {{ __('common.nav.become_coach') }}
            </a>
        @endcan

        @if(auth()->user()->role === \App\Enums\UserRole::Coach)
            {{-- TODO: E2 — replace href with route('coach.sessions.index') --}}
            <a
                href="#"
                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                role="menuitem"
            >
                {{ __('common.nav.my_sessions') }}
            </a>
        @endif

        <a
            href="{{ route('profile.edit') }}"
            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
            role="menuitem"
            wire:navigate
        >
            {{ __('common.nav.profile') }}
        </a>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button
                type="submit"
                class="block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                role="menuitem"
            >
                {{ __('common.nav.logout') }}
            </button>
        </form>
    </div>
</div>
