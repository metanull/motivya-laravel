<div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ __('coach.dashboard_heading') }}</h1>
        <a href="{{ route('coach.sessions.create') }}"
            class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
            {{ __('coach.create_session') }}
        </a>
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
