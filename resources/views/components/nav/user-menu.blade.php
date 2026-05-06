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
        class="absolute right-0 z-10 mt-2 w-56 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black/5 focus:outline-none dark:bg-gray-800 dark:ring-white/10"
        role="menu"
        aria-orientation="vertical"
        aria-label="{{ __('common.user_menu') }}"
        style="display: none;"
    >
        @can('access-admin-panel')
            <a
                href="{{ route('admin.dashboard') }}"
                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                role="menuitem"
                wire:navigate
            >
                {{ __('common.nav.admin_dashboard') }}
            </a>
            <a
                href="{{ route('admin.users.index') }}"
                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                role="menuitem"
                wire:navigate
            >
                {{ __('common.nav.admin_users') }}
            </a>
            <a
                href="{{ route('admin.coach-approval') }}"
                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                role="menuitem"
                wire:navigate
            >
                {{ __('common.nav.admin_coach_approval') }}
            </a>
            <a
                href="{{ route('admin.sessions.index') }}"
                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                role="menuitem"
                wire:navigate
            >
                {{ __('common.nav.admin_sessions') }}
            </a>
            <a
                href="{{ route('admin.refunds.index') }}"
                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                role="menuitem"
                wire:navigate
            >
                {{ __('common.nav.admin_refunds') }}
            </a>
            @if (Route::has('admin.anomalies.index'))
                <a
                    href="{{ route('admin.anomalies.index') }}"
                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                    role="menuitem"
                    wire:navigate
                >
                    {{ __('common.nav.admin_anomalies') }}
                </a>
            @endif
            <a
                href="{{ route('admin.audit-events.index') }}"
                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                role="menuitem"
                wire:navigate
            >
                {{ __('common.nav.admin_audit_events') }}
            </a>
            <a
                href="{{ route('admin.activity-images') }}"
                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                role="menuitem"
                wire:navigate
            >
                {{ __('common.nav.admin_activity_images') }}
            </a>
            <a
                href="{{ route('admin.data-export') }}"
                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                role="menuitem"
                wire:navigate
            >
                {{ __('common.nav.admin_data_export') }}
            </a>
            <a
                href="{{ route('admin.configuration.billing') }}"
                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                role="menuitem"
                wire:navigate
            >
                {{ __('common.nav.admin_billing_config') }}
            </a>
            <a
                href="{{ route('admin.readiness') }}"
                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                role="menuitem"
                wire:navigate
            >
                {{ __('common.nav.admin_readiness') }}
            </a>
            <hr class="my-1 border-gray-200 dark:border-gray-700" />
        @endcan

        @can('access-accountant-panel')
            <p class="block px-4 py-1 text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">
                {{ __('common.nav.finance') }}
            </p>
            <a
                href="{{ route('accountant.dashboard') }}"
                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                role="menuitem"
                wire:navigate
            >
                {{ __('common.nav.accountant_dashboard') }}
            </a>
            <a
                href="{{ route('accountant.transactions.index') }}"
                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                role="menuitem"
                wire:navigate
            >
                {{ __('common.nav.accountant_transactions') }}
            </a>
            <a
                href="{{ route('accountant.payout-statements.index') }}"
                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                role="menuitem"
                wire:navigate
            >
                {{ __('common.nav.accountant_payout_statements') }}
            </a>
            @if (Route::has('accountant.anomalies.index'))
                <a
                    href="{{ route('accountant.anomalies.index') }}"
                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                    role="menuitem"
                    wire:navigate
                >
                    {{ __('common.nav.accountant_anomalies') }}
                </a>
            @endif
            <a
                href="{{ route('accountant.audit-events.index') }}"
                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                role="menuitem"
                wire:navigate
            >
                {{ __('common.nav.accountant_audit_events') }}
            </a>
            <a
                href="{{ route('accountant.export') }}"
                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                role="menuitem"
                wire:navigate
            >
                {{ __('common.nav.accountant_export') }}
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

        @can('access-coach-panel')
            <a
                href="{{ route('coach.dashboard') }}"
                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                role="menuitem"
                wire:navigate
            >
                {{ __('common.nav.coach_dashboard') }}
            </a>
            <a
                href="{{ route('coach.sessions.create') }}"
                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                role="menuitem"
                wire:navigate
            >
                {{ __('common.nav.create_session') }}
            </a>
            <a
                href="{{ route('coach.profile.edit') }}"
                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                role="menuitem"
                wire:navigate
            >
                {{ __('common.nav.coach_profile') }}
            </a>
            <a
                href="{{ route('coach.payout-history') }}"
                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                role="menuitem"
                wire:navigate
            >
                {{ __('common.nav.payout_history') }}
            </a>
            @if (auth()->user()->coachProfile?->stripe_account_id)
                <a
                    href="{{ route('coach.stripe.refresh') }}"
                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                    role="menuitem"
                    wire:navigate
                >
                    {{ __('common.nav.coach_stripe_refresh') }}
                </a>
            @else
                <a
                    href="{{ route('coach.stripe.onboard') }}"
                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                    role="menuitem"
                    wire:navigate
                >
                    {{ __('common.nav.coach_stripe_onboard') }}
                </a>
            @endif
            <hr class="my-1 border-gray-200 dark:border-gray-700" />
        @endcan

        @can('access-athlete-panel')
            <a
                href="{{ route('athlete.dashboard') }}"
                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                role="menuitem"
                wire:navigate
            >
                {{ __('common.nav.athlete_dashboard') }}
            </a>
            <a
                href="{{ route('athlete.favourites') }}"
                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                role="menuitem"
                wire:navigate
            >
                {{ __('common.nav.athlete_favourites') }}
            </a>
            <a
                href="{{ route('sessions.index') }}"
                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700"
                role="menuitem"
                wire:navigate
            >
                {{ __('common.nav.athlete_sessions') }}
            </a>
            <hr class="my-1 border-gray-200 dark:border-gray-700" />
        @endcan

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
