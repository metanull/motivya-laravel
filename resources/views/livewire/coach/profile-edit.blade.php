<div class="mx-auto max-w-2xl px-4 py-6 sm:px-6 lg:px-8">
    <h1 class="mb-6 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ __('coach.profile_edit_heading') }}</h1>

    <form wire:submit="save" class="space-y-6">
        {{-- Specialties --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('coach.specialties_label') }}</label>
            <p class="mb-2 text-xs text-gray-500 dark:text-gray-400">{{ __('coach.specialties_hint') }}</p>
            <div class="flex flex-wrap gap-2">
                @php
                    $allSpecialties = ['fitness', 'yoga', 'running', 'cycling', 'swimming', 'boxing', 'dance', 'pilates', 'crossfit', 'martial_arts', 'tennis', 'other'];
                @endphp
                @foreach ($allSpecialties as $specialty)
                    <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-full border px-3 py-1.5 text-sm transition
                        {{ in_array($specialty, $form->specialties) ? 'border-indigo-600 bg-indigo-50 text-indigo-700 dark:border-indigo-400 dark:bg-indigo-900 dark:text-indigo-300' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                        <input type="checkbox" wire:model.live="form.specialties" value="{{ $specialty }}" class="sr-only" />
                        {{ __('coach.specialty_' . $specialty) }}
                    </label>
                @endforeach
            </div>
            @error('form.specialties') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Bio --}}
        <div>
            <label for="bio" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('coach.bio_label') }}</label>
            <textarea id="bio" wire:model="form.bio" rows="4"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 sm:text-sm"
                placeholder="{{ __('coach.bio_placeholder') }}"></textarea>
            @error('form.bio') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Experience Level --}}
        <div>
            <label for="experience_level" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('coach.experience_level_label') }}</label>
            <select id="experience_level" wire:model="form.experienceLevel"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 sm:text-sm">
                <option value="">{{ __('coach.experience_select') }}</option>
                <option value="beginner">{{ __('coach.experience_beginner') }}</option>
                <option value="intermediate">{{ __('coach.experience_intermediate') }}</option>
                <option value="advanced">{{ __('coach.experience_advanced') }}</option>
                <option value="expert">{{ __('coach.experience_expert') }}</option>
            </select>
            @error('form.experienceLevel') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Postal Code --}}
        <div>
            <label for="postal_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('coach.postal_code_label') }}</label>
            <input id="postal_code" type="text" wire:model="form.postalCode"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 sm:text-sm" />
            @error('form.postalCode') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Enterprise Number --}}
        <div>
            <label for="enterprise_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('coach.enterprise_number_label') }}</label>
            <p class="mb-1 text-xs text-gray-500 dark:text-gray-400">{{ __('coach.enterprise_number_hint') }}</p>
            <input id="enterprise_number" type="text" wire:model="form.enterpriseNumber"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 sm:text-sm" />
            @error('form.enterpriseNumber') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- VAT Subject (read-only) --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('coach.vat_subject_label') }}</label>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ $isVatSubject ? __('coach.vat_subject_yes') : __('coach.vat_subject_no') }}
            </p>
            <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">{{ __('coach.vat_subject_hint') }}</p>
        </div>

        {{-- Submit --}}
        <div class="flex items-center gap-4">
            <button type="submit"
                class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                {{ __('common.save') }}
            </button>
            <a href="{{ route('coaches.show', auth()->user()) }}"
                class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300" wire:navigate>
                {{ __('common.cancel') }}
            </a>
        </div>
    </form>
</div>
