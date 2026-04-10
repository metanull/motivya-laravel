<div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
        {{ __('sessions.create_heading') }}
    </h1>

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

        {{-- Date + Times --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <label for="date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('sessions.date_label') }}
                </label>
                <input type="date" wire:model="form.date" id="date"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                @error('form.date')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="startTime" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('sessions.start_time_label') }}
                </label>
                <input type="time" wire:model="form.startTime" id="startTime"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                @error('form.startTime')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="endTime" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('sessions.end_time_label') }}
                </label>
                <input type="time" wire:model="form.endTime" id="endTime"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
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
            <a href="{{ url()->previous() }}"
                class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                {{ __('common.cancel') }}
            </a>
            <button type="submit"
                class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                {{ __('sessions.create_button') }}
            </button>
        </div>
    </form>
</div>
