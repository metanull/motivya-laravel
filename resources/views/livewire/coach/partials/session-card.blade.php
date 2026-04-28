<div class="mb-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        {{-- Left: session info --}}
        <div class="flex-1">
            <div class="flex items-center gap-2">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    <a href="{{ route('sessions.show', $session) }}" class="hover:text-indigo-600 dark:hover:text-indigo-400" wire:navigate>
                        {{ $session->title }}
                    </a>
                </h3>
                {{-- Status badge --}}
                @php
                    $badgeColors = match($session->status->value) {
                        'draft' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                        'published' => 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
                        'confirmed' => 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
                        'completed' => 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300',
                        'cancelled' => 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300',
                        default => 'bg-gray-100 text-gray-700',
                    };
                @endphp
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badgeColors }}">
                    {{ $session->status->label() }}
                </span>
            </div>

            <div class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-gray-500 dark:text-gray-400">
                <span>{{ $session->activity_type->label() }}</span>
                <span>{{ $session->date->translatedFormat('D j M Y') }}</span>
                <span>{{ substr($session->start_time, 0, 5) }} – {{ substr($session->end_time, 0, 5) }}</span>
                <span>{{ $session->location }}</span>
            </div>

            {{-- Participants + fill rate --}}
            <div class="mt-2 flex items-center gap-3">
                <span class="text-sm text-gray-600 dark:text-gray-300">
                    {{ $session->current_participants }} / {{ $session->max_participants }}
                </span>
                @php
                    $fillRate = $session->max_participants > 0
                        ? round(($session->current_participants / $session->max_participants) * 100)
                        : 0;
                @endphp
                <div class="h-2 w-24 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-600">
                    <div class="h-full rounded-full {{ $fillRate >= 80 ? 'bg-green-500' : ($fillRate >= 50 ? 'bg-yellow-500' : 'bg-indigo-500') }}"
                        style="width: {{ $fillRate }}%"></div>
                </div>
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $fillRate }}%</span>
            </div>
        </div>

        {{-- Right: price + actions --}}
        <div class="flex flex-col items-end gap-2">
            <span class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                <x-money :cents="$session->price_per_person" />
            </span>

            @if ($showActions)
                <div class="flex gap-2">
                    <a href="{{ route('sessions.show', $session) }}"
                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                        wire:navigate>
                        {{ __('coach.preview') }}
                    </a>

                    @can('update', $session)
                        <a href="{{ route('coach.sessions.edit', $session) }}"
                            class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                            wire:navigate>
                            {{ __('common.edit') }}
                        </a>
                    @endcan

                    @if ($session->status->value === 'draft')
                        @can('update', $session)
                            <button wire:click="publishSession({{ $session->id }})"
                                wire:confirm="{{ __('coach.confirm_publish') }}"
                                class="inline-flex items-center rounded-md bg-blue-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm hover:bg-blue-500">
                                {{ __('coach.publish') }}
                            </button>
                        @endcan
                        @can('delete', $session)
                            <button wire:click="deleteSession({{ $session->id }})"
                                wire:confirm="{{ __('coach.confirm_delete') }}"
                                class="inline-flex items-center rounded-md bg-red-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm hover:bg-red-500">
                                {{ __('common.delete') }}
                            </button>
                        @endcan
                    @endif

                    @if (in_array($session->status->value, ['published', 'confirmed']))
                        <a href="https://wa.me/?text={{ urlencode($session->title . ' — ' . route('sessions.show', $session)) }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center gap-1 rounded-md bg-green-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm hover:bg-green-500">
                            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                            </svg>
                            {{ __('coach.share_session') }}
                        </a>
                    @endif

                    @can('cancel', $session)
                        <button wire:click="cancelSession({{ $session->id }})"
                            wire:confirm="{{ __('coach.confirm_cancel') }}"
                            class="inline-flex items-center rounded-md bg-red-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm hover:bg-red-500">
                            {{ __('common.cancel') }}
                        </button>
                    @endcan
                </div>
            @endif
        </div>
    </div>
</div>
