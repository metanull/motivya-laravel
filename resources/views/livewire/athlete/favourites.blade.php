<div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ __('athlete.favourites_heading') }}</h1>
        <a href="{{ route('athlete.dashboard') }}" wire:navigate
            class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
            ← {{ __('athlete.quick_link_explore') }}
        </a>
    </div>

    @forelse ($favourites as $session)
        <div class="mb-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="space-y-1">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100">
                        <a href="{{ route('sessions.show', $session) }}" wire:navigate class="hover:underline">
                            {{ $session->title }}
                        </a>
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ __('athlete.booking_coach_label') }}: {{ $session->coach->name }}
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $session->date->translatedFormat('l j F Y') }}
                        · {{ \Carbon\Carbon::parse($session->start_time)->format('H:i') }}
                        – {{ \Carbon\Carbon::parse($session->end_time)->format('H:i') }}
                    </p>
                </div>
                <div class="flex items-center gap-4">
                    <x-money :cents="$session->price_per_person" class="font-semibold text-gray-900 dark:text-gray-100" />
                    <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-200">
                        {{ $session->status->label() }}
                    </span>
                    <a href="{{ route('sessions.show', $session) }}" wire:navigate
                        class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                        {{ __('sessions.view_session') }}
                    </a>
                </div>
            </div>
        </div>
    @empty
        <p class="py-8 text-center text-gray-500 dark:text-gray-400">{{ __('athlete.no_favourites') }}</p>
    @endforelse
</div>
