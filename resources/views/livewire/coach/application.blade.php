<div class="mx-auto max-w-2xl px-4 py-8 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
        {{ __('coach.application_heading') }}
    </h1>

    {{-- Step indicator --}}
    <div class="mt-6 flex items-center justify-between">
        @for ($i = 1; $i <= 3; $i++)
            <div class="flex items-center">
                <span @class([
                    'flex h-8 w-8 items-center justify-center rounded-full text-sm font-semibold',
                    'bg-indigo-600 text-white' => $step >= $i,
                    'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-400' => $step < $i,
                ])>
                    {{ $i }}
                </span>
                <span class="ml-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('coach.step_' . $i) }}
                </span>
            </div>
            @if ($i < 3)
                <div @class([
                    'mx-2 h-0.5 flex-1',
                    'bg-indigo-600' => $step > $i,
                    'bg-gray-200 dark:bg-gray-700' => $step <= $i,
                ])></div>
            @endif
        @endfor
    </div>

    <form wire:submit="submit" class="mt-8 space-y-6">
        {{-- Step 1: Specialties, bio, experience --}}
        @if ($step === 1)
            <div class="space-y-4 rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    {{ __('coach.step_1_heading') }}
                </h2>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('coach.specialties_label') }}
                    </label>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('coach.specialties_hint') }}</p>
                    <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-3">
                        @foreach (['fitness', 'yoga', 'running', 'cycling', 'swimming', 'boxing', 'dance', 'pilates', 'crossfit', 'martial_arts', 'tennis', 'other'] as $specialty)
                            <label class="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    wire:model="form.specialties"
                                    value="{{ $specialty }}"
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600"
                                />
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('coach.specialty_' . $specialty) }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('form.specialties')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="bio" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('coach.bio_label') }}
                    </label>
                    <textarea
                        wire:model="form.bio"
                        id="bio"
                        rows="4"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                        placeholder="{{ __('coach.bio_placeholder') }}"
                    ></textarea>
                    @error('form.bio')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="experience_level" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('coach.experience_level_label') }}
                    </label>
                    <select
                        wire:model="form.experience_level"
                        id="experience_level"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                    >
                        <option value="">{{ __('coach.experience_select') }}</option>
                        <option value="beginner">{{ __('coach.experience_beginner') }}</option>
                        <option value="intermediate">{{ __('coach.experience_intermediate') }}</option>
                        <option value="advanced">{{ __('coach.experience_advanced') }}</option>
                        <option value="expert">{{ __('coach.experience_expert') }}</option>
                    </select>
                    @error('form.experience_level')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        @endif

        {{-- Step 2: Geographic zone, enterprise number --}}
        @if ($step === 2)
            <div class="space-y-4 rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    {{ __('coach.step_2_heading') }}
                </h2>

                <div>
                    <label for="postal_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('coach.postal_code_label') }}
                    </label>
                    <input
                        wire:model="form.postal_code"
                        type="text"
                        id="postal_code"
                        maxlength="4"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                        placeholder="1000"
                    />
                    @error('form.postal_code')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="country" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('coach.country_label') }}
                    </label>
                    <select
                        wire:model="form.country"
                        id="country"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                    >
                        <option value="BE">{{ __('coach.country_be') }}</option>
                    </select>
                    @error('form.country')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="enterprise_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('coach.enterprise_number_label') }}
                    </label>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('coach.enterprise_number_hint') }}</p>
                    <input
                        wire:model="form.enterprise_number"
                        type="text"
                        id="enterprise_number"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                        placeholder="0123.456.789"
                    />
                    @error('form.enterprise_number')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        @endif

        {{-- Step 3: Confirmation + terms --}}
        @if ($step === 3)
            <div class="space-y-4 rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    {{ __('coach.step_3_heading') }}
                </h2>

                <div class="rounded-md bg-gray-50 p-4 dark:bg-gray-700">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('coach.summary_title') }}</h3>
                    <dl class="mt-2 space-y-1 text-sm text-gray-600 dark:text-gray-300">
                        <div class="flex justify-between">
                            <dt>{{ __('coach.specialties_label') }}</dt>
                            <dd>{{ implode(', ', $form->specialties) }}</dd>
                        </div>
                        @if ($form->bio !== '')
                            <div class="flex justify-between">
                                <dt>{{ __('coach.bio_label') }}</dt>
                                <dd class="max-w-xs truncate">{{ $form->bio }}</dd>
                            </div>
                        @endif
                        @if ($form->experience_level !== '')
                            <div class="flex justify-between">
                                <dt>{{ __('coach.experience_level_label') }}</dt>
                                <dd>{{ __('coach.experience_' . $form->experience_level) }}</dd>
                            </div>
                        @endif
                        <div class="flex justify-between">
                            <dt>{{ __('coach.postal_code_label') }}</dt>
                            <dd>{{ $form->postal_code }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt>{{ __('coach.enterprise_number_label') }}</dt>
                            <dd>{{ $form->enterprise_number }}</dd>
                        </div>
                    </dl>
                </div>

                <div>
                    <label class="flex items-center gap-2">
                        <input
                            type="checkbox"
                            wire:model="form.terms_accepted"
                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600"
                        />
                        <span class="text-sm text-gray-700 dark:text-gray-300">
                            {{ __('coach.terms_label') }}
                        </span>
                    </label>
                    @error('form.terms_accepted')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        @endif

        {{-- Navigation buttons --}}
        <div class="flex justify-between">
            @if ($step > 1)
                <button
                    type="button"
                    wire:click="previousStep"
                    class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
                >
                    {{ __('common.previous') }}
                </button>
            @else
                <div></div>
            @endif

            @if ($step < 3)
                <button
                    type="button"
                    wire:click="nextStep"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    {{ __('common.next') }}
                </button>
            @else
                <button
                    type="submit"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    {{ __('coach.submit_application') }}
                </button>
            @endif
        </div>
    </form>
</div>
