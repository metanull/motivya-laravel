<div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ __('coach.dashboard_heading') }}</h1>
        <a href="{{ route('coach.sessions.create') }}"
            class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
            {{ __('coach.create_session') }}
        </a>
    </div>

    {{-- Stripe readiness checklist --}}
    @if ($coachProfile !== null && ! $coachProfile->stripe_onboarding_complete)
        <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-700 dark:bg-amber-900/20">
            <div class="flex items-start gap-3">
                <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                </svg>
                <div class="flex-1">
                    <h3 class="text-sm font-semibold text-amber-800 dark:text-amber-300">
                        {{ __('coach.stripe_setup_required_heading') }}
                    </h3>
                    <p class="mt-1 text-sm text-amber-700 dark:text-amber-400">
                        {{ __('coach.stripe_setup_required_body') }}
                    </p>
                    <div class="mt-3">
                        @if ($coachProfile->stripe_account_id === null || $coachProfile->stripe_account_id === '')
                            <a href="{{ route('coach.stripe.onboard') }}"
                                class="inline-flex items-center rounded-md bg-amber-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-600">
                                {{ __('coach.stripe_setup_start') }}
                            </a>
                        @else
                            <a href="{{ route('coach.stripe.refresh') }}"
                                class="inline-flex items-center rounded-md bg-amber-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-600">
                                {{ __('coach.stripe_setup_continue') }}
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Stats cards --}}
    <div class="mb-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('coach.stat_total_sessions') }}</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $totalSessions }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('coach.stat_sessions_this_month') }}</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $sessionsThisMonth }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('coach.stat_total_bookings') }}</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $totalBookings }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('coach.stat_avg_fill_rate') }}</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $avgFillRate }}%</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('coach.stat_total_revenue') }}</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100"><x-money :cents="$totalRevenueCents" /></p>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="-mb-px flex space-x-8" aria-label="{{ __('coach.tabs_label') }}">
            <button wire:click="$set('tab', 'upcoming')"
                class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium {{ $tab === 'upcoming' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}">
                {{ __('coach.tab_upcoming') }}
                <span class="ml-1 rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-600 dark:bg-indigo-900 dark:text-indigo-300">{{ $upcoming->count() }}</span>
            </button>
            <button wire:click="$set('tab', 'drafts')"
                class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium {{ $tab === 'drafts' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}">
                {{ __('coach.tab_drafts') }}
                <span class="ml-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">{{ $drafts->count() }}</span>
            </button>
            <button wire:click="$set('tab', 'past')"
                class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium {{ $tab === 'past' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}">
                {{ __('coach.tab_past') }}
                <span class="ml-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">{{ $past->count() }}</span>
            </button>
        </nav>
    </div>

    {{-- Tab content --}}
    <div class="mt-6">
        @if ($tab === 'upcoming')
            @forelse ($upcoming as $session)
                @include('livewire.coach.partials.session-card', ['session' => $session, 'showActions' => true])
            @empty
                <p class="py-8 text-center text-gray-500 dark:text-gray-400">{{ __('coach.no_upcoming') }}</p>
            @endforelse
        @elseif ($tab === 'drafts')
            @forelse ($drafts as $session)
                @include('livewire.coach.partials.session-card', ['session' => $session, 'showActions' => true])
            @empty
                <p class="py-8 text-center text-gray-500 dark:text-gray-400">{{ __('coach.no_drafts') }}</p>
            @endforelse
        @elseif ($tab === 'past')
            @forelse ($past as $session)
                @include('livewire.coach.partials.session-card', ['session' => $session, 'showActions' => false])
            @empty
                <p class="py-8 text-center text-gray-500 dark:text-gray-400">{{ __('coach.no_past') }}</p>
            @endforelse
        @endif
    </div>
</div>
