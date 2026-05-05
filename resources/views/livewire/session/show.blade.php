<div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
    {{-- Cover image --}}
    @if ($sportSession->coverImage)
        <div class="overflow-hidden rounded-lg">
            <img src="{{ Storage::disk('public')->url($sportSession->coverImage->path) }}"
                alt="{{ $sportSession->coverImage->alt_text }}"
                class="h-64 w-full object-cover sm:h-80">
        </div>
    @endif

    {{-- Header --}}
    <div class="mt-6">
        <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                {{ $sportSession->activity_type->label() }}
            </span>
            <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                {{ $sportSession->level->label() }}
            </span>
            <span @class([
                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' => $sportSession->status === \App\Enums\SessionStatus::Draft,
                'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' => $sportSession->status === \App\Enums\SessionStatus::Published,
                'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' => $sportSession->status === \App\Enums\SessionStatus::Confirmed,
                'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' => $sportSession->status === \App\Enums\SessionStatus::Completed,
                'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' => $sportSession->status === \App\Enums\SessionStatus::Cancelled,
            ])>
                {{ $sportSession->status->label() }}
            </span>
        </div>

        <h1 class="mt-3 text-2xl font-bold text-gray-900 dark:text-gray-100 sm:text-3xl">
            {{ $sportSession->title }}
        </h1>

        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            {{ __('sessions.by_coach') }}
            <a href="{{ route('coaches.show', $sportSession->coach) }}" class="font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400" wire:navigate>
                {{ $sportSession->coach->name }}
            </a>
        </p>
    </div>

    {{-- Details grid --}}
    <div class="mt-8 rounded-lg bg-white p-6 shadow dark:bg-gray-800">
        <dl class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('sessions.date') }}</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                    {{ $sportSession->date->translatedFormat('l j F Y') }}
                </dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('sessions.time') }}</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                    {{ \Carbon\Carbon::parse($sportSession->start_time)->format('H:i') }}
                    –
                    {{ \Carbon\Carbon::parse($sportSession->end_time)->format('H:i') }}
                </dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('sessions.location') }}</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                    {{ $sportSession->location }}
                    @if ($sportSession->postal_code)
                        <span class="text-gray-500 dark:text-gray-400">({{ $sportSession->postal_code }})</span>
                    @endif
                </dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('sessions.price') }}</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                    <x-money :cents="$sportSession->price_per_person" class="text-lg font-semibold" />
                    <span class="text-gray-500 dark:text-gray-400">/ {{ __('sessions.per_person') }}</span>
                </dd>
            </div>

            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('sessions.spots') }}</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                    {{ trans_choice('sessions.spots_remaining', $spotsRemaining, ['count' => $spotsRemaining]) }}
                    <span class="text-gray-500 dark:text-gray-400">
                        ({{ $sportSession->current_participants }}/{{ $sportSession->max_participants }})
                    </span>
                </dd>
            </div>
        </dl>
    </div>

    <livewire:booking.book :sport-session="$sportSession" />
    <livewire:booking.cancel :sport-session="$sportSession" />

    {{-- Map preview --}}
    @if ($sportSession->latitude && $sportSession->longitude)
        <div class="mt-6">
            <x-session-map
                map-id="detail-map"
                :markers="collect([['id' => $sportSession->id, 'title' => $sportSession->title, 'latitude' => $sportSession->latitude, 'longitude' => $sportSession->longitude, 'coach' => $sportSession->coach->name, 'date' => $sportSession->date->format('d/m/Y'), 'time' => \Carbon\Carbon::parse($sportSession->start_time)->format('H:i'), 'price' => $sportSession->price_per_person, 'url' => route('sessions.show', $sportSession)]])"
                :fallback-center="[$sportSession->longitude, $sportSession->latitude]"
                :single-marker="true"
                height="300px"
            />
        </div>
    @endif

    {{-- Description --}}
    @if ($sportSession->description)
        <div class="mt-6 rounded-lg bg-white p-6 shadow dark:bg-gray-800">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('sessions.description') }}</h2>
            <div class="prose dark:prose-invert mt-3 max-w-none text-sm text-gray-700 dark:text-gray-300">
                {!! nl2br(e($sportSession->description)) !!}
            </div>
        </div>
    @endif

    {{-- Share buttons --}}
    <div class="mt-6 flex flex-wrap gap-3">
        {{-- Directions --}}
        @if ($sportSession->latitude && $sportSession->longitude)
            <a href="https://www.google.com/maps/dir/?api=1&amp;destination={{ $sportSession->latitude }},{{ $sportSession->longitude }}"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-9.253A1 1 0 014.5 9h15a1 1 0 01.947 1.316L15 20M12 9V3m0 0L9 6m3-3l3 3"/>
                </svg>
                {{ __('sessions.directions') }}
            </a>
        @else
            <a href="https://www.google.com/maps/dir/?api=1&amp;destination={{ urlencode(($sportSession->location ?? '') . ' ' . ($sportSession->postal_code ?? '') . ' Belgium') }}"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-9.253A1 1 0 014.5 9h15a1 1 0 01.947 1.316L15 20M12 9V3m0 0L9 6m3-3l3 3"/>
                </svg>
                {{ __('sessions.directions') }}
            </a>
        @endif

        <a href="https://wa.me/?text={{ urlencode($sportSession->title . ' — ' . request()->url()) }}"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex items-center gap-2 rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500">
            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
            </svg>
            {{ __('sessions.share_whatsapp') }}
        </a>

        <button
            x-data
            x-on:click="navigator.clipboard.writeText('{{ request()->url() }}').then(() => $dispatch('notify', { type: 'success', message: '{{ __('sessions.link_copied') }}' }))"
            type="button"
            class="inline-flex items-center gap-2 rounded-md bg-gray-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-500">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
            </svg>
            {{ __('sessions.copy_link') }}
        </button>

        @can('favourite', $sportSession)
                <button
                    wire:click="toggleFavourite"
                    type="button"
                    class="inline-flex items-center gap-2 rounded-md {{ $isFavourited ? 'bg-pink-600 hover:bg-pink-500' : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-600' }} px-4 py-2 text-sm font-semibold shadow-sm">
                    @if ($isFavourited)
                        {{-- Filled heart --}}
                        <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                        </svg>
                        <span class="text-white">{{ __('athlete.toggle_favourite_remove') }}</span>
                    @else
                        {{-- Outline heart --}}
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                        {{ __('athlete.toggle_favourite_add') }}
                    @endif
                </button>
        @endcan
    </div>
</div>
