<div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ __('athlete.dashboard_heading') }}</h1>
    </div>

    {{-- Quick links --}}
    <div class="mb-8 flex flex-wrap gap-3">
        <a href="{{ route('sessions.index') }}" wire:navigate
            class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
            {{ __('athlete.quick_link_explore') }}
        </a>
        @if (Route::has('athlete.favourites'))
            <a href="{{ route('athlete.favourites') }}" wire:navigate
                class="inline-flex items-center rounded-md bg-pink-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-pink-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-pink-600">
                {{ __('athlete.quick_link_favourites') }}
            </a>
        @endif
        <a href="{{ route('profile.edit') }}" wire:navigate
            class="inline-flex items-center rounded-md bg-gray-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-600">
            {{ __('athlete.quick_link_profile') }}
        </a>
    </div>

    {{-- Tabs --}}
    <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="-mb-px flex space-x-8" aria-label="{{ __('athlete.tabs_label') }}">
            <button wire:click="$set('tab', 'upcoming')"
                class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium {{ $tab === 'upcoming' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}">
                {{ __('athlete.tab_upcoming') }}
                <span
                    class="ml-1 rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-600 dark:bg-indigo-900 dark:text-indigo-300">{{ $upcoming->count() }}</span>
            </button>
            <button wire:click="$set('tab', 'past')"
                class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium {{ $tab === 'past' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}">
                {{ __('athlete.tab_past') }}
                <span
                    class="ml-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">{{ $past->count() }}</span>
            </button>
        </nav>
    </div>

    {{-- Tab content --}}
    <div class="mt-6">
        @if ($tab === 'upcoming')
            @forelse ($upcoming as $booking)
                <div
                    class="mb-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="space-y-1">
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100">
                                <a href="{{ route('sessions.show', $booking->sportSession) }}" wire:navigate
                                    class="hover:underline">
                                    {{ $booking->sportSession->title }}
                                </a>
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ __('athlete.booking_coach_label') }}: {{ $booking->sportSession->coach->name }}
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $booking->sportSession->date->translatedFormat('l j F Y') }}
                                &middot; {{ \Carbon\Carbon::parse($booking->sportSession->start_time)->format('H:i') }}
                                &ndash; {{ \Carbon\Carbon::parse($booking->sportSession->end_time)->format('H:i') }}
                            </p>
                        </div>
                        <div class="flex items-center gap-4">
                            <x-money :cents="$booking->sportSession->price_per_person"
                                class="font-semibold text-gray-900 dark:text-gray-100" />
                            <span @class([
                                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                                'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' => $booking->status === \App\Enums\BookingStatus::PendingPayment,
                                'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' => $booking->status === \App\Enums\BookingStatus::Confirmed,
                            ])>
                                {{ $booking->status->label() }}
                            </span>
                            <a href="{{ route('sessions.show', $booking->sportSession) }}" wire:navigate
                                class="text-sm font-medium text-red-600 hover:text-red-500 dark:text-red-400">
                                {{ __('athlete.cancel_booking_link') }}
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <p class="py-8 text-center text-gray-500 dark:text-gray-400">{{ __('athlete.no_upcoming') }}</p>
            @endforelse
        @elseif ($tab === 'past')
            @forelse ($past as $booking)
                <div
                    class="mb-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="space-y-1">
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100">
                                <a href="{{ route('sessions.show', $booking->sportSession) }}" wire:navigate
                                    class="hover:underline">
                                    {{ $booking->sportSession->title }}
                                </a>
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ __('athlete.booking_coach_label') }}: {{ $booking->sportSession->coach->name }}
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $booking->sportSession->date->translatedFormat('l j F Y') }}
                                &middot; {{ \Carbon\Carbon::parse($booking->sportSession->start_time)->format('H:i') }}
                                &ndash; {{ \Carbon\Carbon::parse($booking->sportSession->end_time)->format('H:i') }}
                            </p>
                        </div>
                        <div class="flex items-center gap-4">
                            <x-money :cents="$booking->sportSession->price_per_person"
                                class="font-semibold text-gray-900 dark:text-gray-100" />
                            <span @class([
                                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                                'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' => $booking->status === \App\Enums\BookingStatus::Confirmed,
                                'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' => $booking->status === \App\Enums\BookingStatus::Cancelled,
                                'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' => $booking->status === \App\Enums\BookingStatus::Refunded,
                                'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' => $booking->status === \App\Enums\BookingStatus::PendingPayment,
                            ])>
                                {{ $booking->status->label() }}
                            </span>
                        </div>
                    </div>
                </div>
            @empty
                <p class="py-8 text-center text-gray-500 dark:text-gray-400">{{ __('athlete.no_past') }}</p>
            @endforelse
        @endif
    </div>
</div>
