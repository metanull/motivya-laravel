<div class="space-y-6">

    {{-- Page header --}}
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
            {{ __('sessions.discovery_heading') }}
        </h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            {{ __('sessions.discovery_subtitle') }}
        </p>
    </div>

    {{-- Geolocation-denied banner --}}
    @if ($geoDenied)
        <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-700 dark:bg-yellow-900/20">
            <p class="text-sm text-yellow-800 dark:text-yellow-200">
                {{ __('sessions.geo_denied_state') }}
            </p>
        </div>
    @endif

    {{-- Filter panel --}}
    <div class="rounded-lg bg-white p-4 shadow dark:bg-gray-800 sm:p-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">

            {{-- Activity type --}}
            <div>
                <label for="activityType" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('sessions.activity_type_label') }}
                </label>
                <select wire:model.live="activityType" id="activityType"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    <option value="">{{ __('sessions.all_activities') }}</option>
                    @foreach ($activityTypes as $type)
                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Level --}}
            <div>
                <label for="level" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('sessions.level_label') }}
                </label>
                <select wire:model.live="level" id="level"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    <option value="">{{ __('sessions.all_levels') }}</option>
                    @foreach ($levels as $lvl)
                        <option value="{{ $lvl->value }}">{{ $lvl->label() }}</option>
                    @endforeach
                </select>
            </div>

            {{-- City / postal code + geolocation button --}}
            <div>
                <label for="locationQuery" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('sessions.location_query_label') }}
                </label>
                <div class="mt-1 flex rounded-md shadow-sm">
                    <input type="text" wire:model.live="locationQuery" id="locationQuery"
                        :class="{ 'opacity-50': useGeolocation }"
                        :disabled="useGeolocation"
                        class="block w-full rounded-l-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                        placeholder="{{ __('sessions.location_query_placeholder') }}">
                    {{-- Geolocation button --}}
                    <button
                        type="button"
                        id="geo-btn"
                        x-data="sessionGeolocator($wire)"
                        x-on:click="requestGeolocation()"
                        :class="useGeolocation ? 'bg-indigo-600 text-white hover:bg-indigo-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-600 dark:text-gray-200'"
                        class="inline-flex items-center rounded-r-md border border-l-0 border-gray-300 px-3 transition dark:border-gray-600"
                        :title="useGeolocation ? '{{ __('sessions.geo_active') }}' : '{{ __('sessions.geo_use') }}'">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </button>
                </div>

                {{-- Active location indicator --}}
                @if ($useGeolocation)
                    <div class="mt-1 flex items-center gap-1 text-xs text-indigo-600 dark:text-indigo-400">
                        <span>
                            {{ __('sessions.active_location_radius', [
                                'location' => __('sessions.geo_active'),
                                'km' => $radius,
                            ]) }}
                        </span>
                        <button wire:click="clearGeolocation" type="button"
                            class="font-medium underline hover:no-underline">
                            {{ __('sessions.geo_clear') }}
                        </button>
                    </div>
                @elseif ($effectiveQuery !== '' && ! $locationInvalid)
                    <div class="mt-1 text-xs text-indigo-600 dark:text-indigo-400">
                        {{ __('sessions.active_location_radius', [
                            'location' => $effectiveQuery,
                            'km' => $radius,
                        ]) }}
                    </div>
                @endif
            </div>

            {{-- Radius selector — shown when a location source is active --}}
            @if ($useGeolocation || $locationQuery !== '' || $postalCode !== '')
                <div>
                    <label for="radiusKm" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('sessions.radius_label') }}
                    </label>
                    <select wire:model.live="radiusKm" id="radiusKm"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                        @foreach ($validRadii as $r)
                            <option value="{{ $r }}">{{ __('sessions.radius_km', ['km' => $r]) }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            {{-- Date from --}}
            <div>
                <label for="dateFrom" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('sessions.date_from_label') }}
                </label>
                <input type="date" wire:model.live="dateFrom" id="dateFrom"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
            </div>

            {{-- Date to --}}
            <div>
                <label for="dateTo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('sessions.date_to_label') }}
                </label>
                <input type="date" wire:model.live="dateTo" id="dateTo"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
            </div>

            {{-- Time range --}}
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label for="timeFrom" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('sessions.time_from_label') }}
                    </label>
                    <input type="time" wire:model.live="timeFrom" id="timeFrom"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                </div>
                <div>
                    <label for="timeTo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('sessions.time_to_label') }}
                    </label>
                    <input type="time" wire:model.live="timeTo" id="timeTo"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                </div>
            </div>

        </div>

        {{-- Reset filters --}}
        <div class="mt-4 flex justify-end">
            <button wire:click="resetFilters" type="button"
                class="text-sm text-gray-500 underline hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                {{ __('sessions.reset_filters') }}
            </button>
        </div>
    </div>

    {{-- Map — hidden when there are no markers --}}
    @if ($markers->isNotEmpty())
        <x-session-map :markers="$markers" />
    @endif

    {{-- Results --}}
    <div>
        @if ($locationInvalid)
            {{-- Unresolvable location input --}}
            <div class="rounded-lg bg-white py-12 text-center shadow dark:bg-gray-800">
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('sessions.invalid_location') }}</p>
                <button wire:click="resetFilters" type="button"
                    class="mt-4 text-sm text-indigo-600 underline hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-200">
                    {{ __('sessions.reset_filters') }}
                </button>
            </div>
        @elseif ($sessions->isEmpty() && $hasActiveLocation)
            {{-- No sessions within the selected radius --}}
            <div class="rounded-lg bg-white py-12 text-center shadow dark:bg-gray-800">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('sessions.no_results_radius', ['km' => $radius]) }}
                </p>
                <button wire:click="resetFilters" type="button"
                    class="mt-4 text-sm text-indigo-600 underline hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-200">
                    {{ __('sessions.reset_filters') }}
                </button>
            </div>
        @elseif ($sessions->isEmpty())
            {{-- Generic no-results (no location active) --}}
            <div class="rounded-lg bg-white py-12 text-center shadow dark:bg-gray-800">
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('sessions.no_results') }}</p>
                <button wire:click="resetFilters" type="button"
                    class="mt-4 text-sm text-indigo-600 underline hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-200">
                    {{ __('sessions.reset_filters') }}
                </button>
            </div>
        @else
            <p class="mb-3 text-sm text-gray-500 dark:text-gray-400">
                {{ trans_choice('sessions.result_count', $sessions->total(), ['count' => $sessions->total()]) }}
            </p>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($sessions as $session)
                    <a href="{{ route('sessions.show', $session) }}" wire:navigate
                        class="group flex flex-col overflow-hidden rounded-lg bg-white shadow transition hover:shadow-md dark:bg-gray-800">

                        {{-- Cover image --}}
                        @if ($session->coverImage)
                            <div class="h-40 overflow-hidden">
                                <img src="{{ Storage::disk('public')->url($session->coverImage->path) }}"
                                    alt="{{ $session->coverImage->alt_text }}"
                                    class="h-full w-full object-cover transition group-hover:scale-105">
                            </div>
                        @else
                            <div class="flex h-40 items-center justify-center bg-indigo-50 dark:bg-indigo-900">
                                <span class="text-3xl">🏃</span>
                            </div>
                        @endif

                        <div class="flex flex-1 flex-col p-4">
                            {{-- Badges --}}
                            <div class="flex flex-wrap gap-1">
                                <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                    {{ $session->activity_type->label() }}
                                </span>
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                    {{ $session->level->label() }}
                                </span>
                                @if ($session->status === \App\Enums\SessionStatus::Confirmed)
                                    <span class="inline-flex items-center rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                        {{ $session->status->label() }}
                                    </span>
                                @endif
                            </div>

                            {{-- Title --}}
                            <h2 class="mt-2 text-sm font-semibold text-gray-900 group-hover:text-indigo-600 dark:text-gray-100 dark:group-hover:text-indigo-400">
                                {{ $session->title }}
                            </h2>

                            {{-- Coach --}}
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ __('sessions.by_coach') }} {{ $session->coach->name }}
                            </p>

                            {{-- Meta --}}
                            <dl class="mt-3 grid grid-cols-2 gap-y-1 text-xs text-gray-600 dark:text-gray-400">
                                <div>
                                    <dt class="sr-only">{{ __('sessions.date') }}</dt>
                                    <dd>📅 {{ $session->date->translatedFormat('d/m/Y') }}</dd>
                                </div>
                                <div>
                                    <dt class="sr-only">{{ __('sessions.time') }}</dt>
                                    <dd>🕐 {{ \Carbon\Carbon::parse($session->start_time)->format('H:i') }}</dd>
                                </div>
                                <div>
                                    <dt class="sr-only">{{ __('sessions.price') }}</dt>
                                    <dd>💶 <x-money :cents="$session->price_per_person" /></dd>
                                </div>
                                <div>
                                    <dt class="sr-only">{{ __('sessions.spots') }}</dt>
                                    @php $remaining = $session->max_participants - $session->current_participants; @endphp
                                    <dd @class([
                                        'font-medium',
                                        'text-red-600 dark:text-red-400' => $remaining === 0,
                                        'text-green-600 dark:text-green-400' => $remaining > 0,
                                    ])>
                                        {{ trans_choice('sessions.spots_remaining', $remaining, ['count' => $remaining]) }}
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $sessions->links() }}
            </div>
        @endif
    </div>

</div>

@push('scripts')
<script>
    function sessionGeolocator(wire) {
        return {
            requestGeolocation() {
                if (!navigator.geolocation) {
                    wire.dispatch('notify', {
                        type: 'error',
                        message: '{{ __('sessions.geo_not_supported') }}'
                    });
                    return;
                }

                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        wire.setGeolocation(
                            position.coords.latitude,
                            position.coords.longitude
                        );
                    },
                    () => {
                        wire.setGeolocationDenied();
                    }
                );
            }
        };
    }
</script>
@endpush

