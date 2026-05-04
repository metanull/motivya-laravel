<div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ __('coach.dashboard_heading') }}</h1>
        <a href="{{ route('coach.sessions.create') }}"
            class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
            {{ __('coach.create_session') }}
        </a>
    </div>

    {{-- Onboarding Checklist --}}
    <div class="mb-6 rounded-lg border border-indigo-200 bg-indigo-50 shadow-sm dark:border-indigo-800 dark:bg-gray-800">
        <div class="flex items-center justify-between px-5 py-4">
            <div class="flex items-center gap-3">
                @if ($allChecklistDone)
                    <span class="flex h-7 w-7 items-center justify-center rounded-full bg-green-100 dark:bg-green-900">
                        <svg class="h-4 w-4 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-green-800 dark:text-green-300">{{ __('coach.onboarding_checklist_heading') }}</p>
                        <p class="text-xs text-green-700 dark:text-green-400">{{ __('coach.onboarding_all_done') }}</p>
                    </div>
                @else
                    <span class="flex h-7 w-7 items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-900">
                        <svg class="h-4 w-4 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-indigo-900 dark:text-indigo-200">{{ __('coach.onboarding_checklist_heading') }}</p>
                        <p class="text-xs text-indigo-700 dark:text-indigo-400">{{ __('coach.onboarding_checklist_subtitle', ['done' => collect($checklistItems)->filter(fn ($i) => $i['done'])->count(), 'total' => count($checklistItems)]) }}</p>
                    </div>
                @endif
            </div>
            <button
                wire:click="toggleChecklist"
                class="text-xs font-medium text-indigo-700 hover:text-indigo-600 dark:text-indigo-400 dark:hover:text-indigo-300"
                aria-expanded="{{ $showChecklist ? 'true' : 'false' }}">
                {{ $showChecklist ? __('coach.onboarding_hide_checklist') : __('coach.onboarding_show_checklist') }}
            </button>
        </div>

        @if ($showChecklist)
            <div class="border-t border-indigo-100 px-5 py-4 dark:border-indigo-900">
                <ul class="space-y-3">
                    @foreach ($checklistItems as $item)
                        <li class="flex items-start gap-3">
                            @if ($item['done'])
                                <span class="mt-0.5 flex-shrink-0">
                                    <svg class="h-5 w-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </span>
                                <span class="text-sm text-gray-500 line-through dark:text-gray-400">{{ $item['label'] }}</span>
                            @else
                                <span class="mt-0.5 flex-shrink-0">
                                    <svg class="h-5 w-5 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </span>
                                <div class="flex min-w-0 flex-1 items-center justify-between gap-2">
                                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $item['label'] }}</span>
                                    @if ($item['url'])
                                        <a href="{{ $item['url'] }}" wire:navigate
                                            class="flex-shrink-0 text-xs font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                                            {{ __('coach.onboarding_item_action_fix') }}
                                        </a>
                                    @endif
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    {{-- Stripe readiness checklist --}}
    @if ($coachProfile !== null && ! $coachProfile->isStripeReady())
        @php
            $stripeOnboardUrl = $coachProfile->stripe_account_id
                ? route('coach.stripe.refresh')
                : route('coach.stripe.onboard');
            $stripeOnboardLabel = $coachProfile->stripe_account_id
                ? __('coach.stripe_setup_continue')
                : __('coach.stripe_setup_start');
        @endphp
        <x-stripe-setup-warning
            class="mb-6"
            :onboard-url="$stripeOnboardUrl"
            :message="$stripeOnboardLabel">
            <h3 class="text-sm font-semibold text-amber-800 dark:text-amber-300">
                {{ __('coach.stripe_setup_required_heading') }}
            </h3>
            <p class="mt-1 text-sm text-amber-700 dark:text-amber-400">
                {{ __('coach.stripe_setup_required_body') }}
            </p>
        </x-stripe-setup-warning>
    @endif

    {{-- Warning: already-published sessions are not bookable because Stripe is not ready --}}
    @if ($publishedWithoutStripe)
        <div class="mb-6 rounded-md bg-red-50 p-4 ring-1 ring-red-200 dark:bg-red-900/20 dark:ring-red-700">
            <p class="text-sm font-medium text-red-800 dark:text-red-300">
                {{ __('coach.published_sessions_not_stripe_ready') }}
            </p>
        </div>
    @endif

    {{-- Stats cards --}}
    <div class="mb-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('coach.stat_total_sessions') }}</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $totalSessions }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('coach.stat_sessions_this_month') }}</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $sessionsThisMonth }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('coach.stat_confirmed_participants') }}</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $totalConfirmedParticipants }}</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('coach.stat_pending_holds') }}</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $totalPendingPaymentHolds }}</p>
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
                <div class="py-8 text-center">
                    <p class="text-gray-500 dark:text-gray-400">{{ __('coach.no_drafts') }}</p>
                    <a href="{{ route('coach.sessions.create') }}" wire:navigate
                        class="mt-4 inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                        {{ __('coach.no_drafts_cta') }}
                    </a>
                </div>
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
