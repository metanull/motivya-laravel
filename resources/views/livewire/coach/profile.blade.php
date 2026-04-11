<div class="mx-auto max-w-4xl px-4 py-6 sm:px-6 lg:px-8">
    {{-- Coach header --}}
    <div class="mb-8 rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
            <div class="flex h-20 w-20 items-center justify-center rounded-full bg-indigo-100 text-2xl font-bold text-indigo-600 dark:bg-indigo-900 dark:text-indigo-300">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div class="flex-1">
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $user->name }}</h1>
                    @if ($isVerified)
                        <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900 dark:text-green-300">
                            {{ __('coach.verified_badge') }}
                        </span>
                    @endif
                </div>
                @if ($profile?->bio)
                    <p class="mt-2 text-gray-600 dark:text-gray-300">{{ $profile->bio }}</p>
                @endif
            </div>
        </div>

        @if ($profile)
            <div class="mt-4 flex flex-wrap gap-4 border-t border-gray-200 pt-4 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                @if ($profile->specialties)
                    <div>
                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ __('coach.specialties_label') }}:</span>
                        @foreach ($profile->specialties as $specialty)
                            <span class="ml-1 inline-flex items-center rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300">
                                {{ __('coach.specialty_' . $specialty) }}
                            </span>
                        @endforeach
                    </div>
                @endif
                @if ($profile->experience_level)
                    <div>
                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ __('coach.experience_level_label') }}:</span>
                        {{ __('coach.experience_' . $profile->experience_level) }}
                    </div>
                @endif
                @if ($profile->postal_code)
                    <div>
                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ __('coach.postal_code_label') }}:</span>
                        {{ $profile->postal_code }}
                    </div>
                @endif
            </div>
        @endif
    </div>

    {{-- Upcoming sessions --}}
    @if ($upcomingSessions->isNotEmpty())
        <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('coach.upcoming_sessions') }}</h2>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($upcomingSessions as $session)
                <a href="{{ route('sessions.show', $session) }}" wire:navigate
                    class="block rounded-lg border border-gray-200 bg-white p-4 shadow-sm transition hover:shadow-md dark:border-gray-700 dark:bg-gray-800">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ $session->title }}</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $session->activity_type->label() }}</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $session->date->translatedFormat('D j M Y') }}</p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ substr($session->start_time, 0, 5) }} – {{ substr($session->end_time, 0, 5) }}</p>
                    <p class="mt-2 font-semibold text-gray-900 dark:text-gray-100"><x-money :cents="$session->price_per_person" /></p>
                </a>
            @endforeach
        </div>
    @else
        <p class="py-8 text-center text-gray-500 dark:text-gray-400">{{ __('coach.no_upcoming_public') }}</p>
    @endif
</div>
