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
