<div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
        {{ __('sessions.edit_heading') }}
    </h1>

    {{-- Stripe readiness warning --}}
    @if (! $stripeReady)
        <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-700 dark:bg-amber-900/20">
            <div class="flex items-start gap-3">
                <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                </svg>
                <div>
                    <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">{{ __('sessions.stripe_not_ready_heading') }}</p>
                    <p class="mt-1 text-sm text-amber-700 dark:text-amber-400">{{ __('sessions.stripe_not_ready_body') }}</p>
                    <a href="{{ route('coach.stripe.onboard') }}" class="mt-2 inline-block text-sm font-medium text-amber-800 underline hover:text-amber-600 dark:text-amber-300 dark:hover:text-amber-200">
                        {{ __('sessions.stripe_not_ready_action') }}
                    </a>
                </div>
            </div>
        </div>
    @endif

    {{-- Recurring session edit scope --}}
    @if ($isRecurring)
        <div class="mt-4 rounded-lg border border-indigo-200 bg-indigo-50 p-4 dark:border-indigo-800 dark:bg-indigo-900/30">
            <p class="mb-3 text-sm font-medium text-indigo-800 dark:text-indigo-200">
                {{ __('sessions.recurring_edit_prompt') }}
            </p>
            <div class="flex gap-4">
                <label class="flex items-center gap-2">
                    <input type="radio" wire:model="editScope" value="this"
                        class="text-indigo-600 focus:ring-indigo-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('sessions.edit_this_only') }}</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="radio" wire:model="editScope" value="all_future"
                        class="text-indigo-600 focus:ring-indigo-500">
                    <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('sessions.edit_all_future') }}</span>
                </label>
            </div>
        </div>
    @endif

    <form wire:submit="save" class="mt-6 space-y-6">
        {{-- Activity type + level --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="activityType" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('sessions.activity_type_label') }}
                </label>
                <select wire:model.live="form.activityType" id="activityType"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    <option value="">{{ __('sessions.select_activity_type') }}</option>
                    @foreach ($activityTypes as $type)
                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                    @endforeach
                </select>
                @error('form.activityType')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="level" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('sessions.level_label') }}
                </label>
                <select wire:model="form.level" id="level"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    <option value="">{{ __('sessions.select_level') }}</option>
                    @foreach ($levels as $lvl)
                        <option value="{{ $lvl->value }}">{{ $lvl->label() }}</option>
                    @endforeach
                </select>
                @error('form.level')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Title --}}
        <div>
            <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ __('sessions.title_label') }}
            </label>
            <input type="text" wire:model="form.title" id="title"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                placeholder="{{ __('sessions.title_placeholder') }}">
            @error('form.title')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        {{-- Description --}}
        <div>
            <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ __('sessions.description_label') }}
            </label>
            <textarea wire:model="form.description" id="description" rows="4"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                placeholder="{{ __('sessions.description_placeholder') }}"></textarea>
            @error('form.description')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        {{-- Location + Postal code --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="location" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('sessions.location_label') }}
                </label>
                <input type="text" wire:model="form.location" id="location"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                    placeholder="{{ __('sessions.location_placeholder') }}">
                @error('form.location')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="postalCode" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('sessions.postal_code_label') }}
                </label>
                <input type="text" wire:model="form.postalCode" id="postalCode"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                    placeholder="1000">
                @error('form.postalCode')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Date + Times (locked when session has bookings) --}}
        @php $hasBookings = $sportSession->current_participants > 0; @endphp
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <label for="date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('sessions.date_label') }}
                </label>
                <input type="date" wire:model="form.date" id="date"
                    @if ($hasBookings) disabled @endif
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm disabled:cursor-not-allowed disabled:opacity-50">
                @error('form.date')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="startTime" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('sessions.start_time_label') }}
                </label>
                <input type="time" wire:model="form.startTime" id="startTime"
                    @if ($hasBookings) disabled @endif
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm disabled:cursor-not-allowed disabled:opacity-50">
                @error('form.startTime')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="endTime" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('sessions.end_time_label') }}
                </label>
                <input type="time" wire:model="form.endTime" id="endTime"
                    @if ($hasBookings) disabled @endif
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm disabled:cursor-not-allowed disabled:opacity-50">
                @error('form.endTime')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Price + Participants --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <label for="priceEuros" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('sessions.price_label') }}
                </label>
                <div class="relative mt-1">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">€</span>
                    <input type="number" wire:model="form.priceEuros" id="priceEuros" step="0.01" min="0.01"
                        class="block w-full rounded-md border-gray-300 pl-7 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                        placeholder="15.00">
                </div>
                @error('form.priceEuros')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="minParticipants" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('sessions.min_participants_label') }}
                </label>
                <input type="number" wire:model="form.minParticipants" id="minParticipants" min="1"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                @error('form.minParticipants')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="maxParticipants" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('sessions.max_participants_label') }}
                </label>
                <input type="number" wire:model="form.maxParticipants" id="maxParticipants" min="1"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                @error('form.maxParticipants')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Cover image picker --}}
        @if ($coverImages->isNotEmpty())
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('sessions.cover_image_label') }}
                </label>
                <div class="mt-2 grid grid-cols-3 gap-3 sm:grid-cols-4">
                    @foreach ($coverImages as $img)
                        <label class="cursor-pointer" wire:key="cover-{{ $img->id }}">
                            <input type="radio" wire:model="form.coverImageId" value="{{ $img->id }}" class="peer sr-only">
                            <div class="overflow-hidden rounded-lg border-2 border-transparent ring-offset-2 peer-checked:border-indigo-500 peer-checked:ring-2 peer-checked:ring-indigo-500">
                                <img src="{{ Storage::disk('public')->url($img->path) }}"
                                    alt="{{ $img->alt_text }}"
                                    class="h-24 w-full object-cover">
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Submit --}}
        <div class="flex justify-end gap-3">
            <a href="{{ route('sessions.show', $sportSession) }}"
                class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                {{ __('common.cancel') }}
            </a>
            <button type="submit"
                class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                {{ __('sessions.update_button') }}
            </button>
        </div>
    </form>
</div>
