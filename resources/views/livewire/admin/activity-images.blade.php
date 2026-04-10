<div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
        {{ __('admin.activity_images_heading') }}
    </h1>

    {{-- Upload form --}}
    <form wire:submit="save" class="mt-6 rounded-lg bg-white p-6 shadow dark:bg-gray-800">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            {{ __('admin.upload_image') }}
        </h2>

        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="activityType" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('admin.activity_type_label') }}
                </label>
                <select wire:model="activityType" id="activityType"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm">
                    <option value="">{{ __('admin.select_activity_type') }}</option>
                    @foreach ($activityTypes as $type)
                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                    @endforeach
                </select>
                @error('activityType')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="altText" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('admin.alt_text_label') }}
                </label>
                <input type="text" wire:model="altText" id="altText"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                    placeholder="{{ __('admin.alt_text_placeholder') }}">
                @error('altText')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div class="sm:col-span-2">
                <label for="image" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('admin.image_file_label') }}
                </label>
                <input type="file" wire:model="image" id="image" accept="image/jpeg,image/png,image/webp"
                    class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100 dark:text-gray-400 dark:file:bg-indigo-900 dark:file:text-indigo-300">
                @error('image')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="mt-4 flex justify-end">
            <button type="submit"
                class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                {{ __('admin.upload_button') }}
            </button>
        </div>
    </form>

    {{-- Image gallery --}}
    <div class="mt-8">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
            {{ __('admin.existing_images') }}
        </h2>

        @if ($images->isEmpty())
            <div class="mt-4 rounded-lg bg-white p-6 text-center shadow dark:bg-gray-800">
                <p class="text-gray-500 dark:text-gray-400">{{ __('admin.no_images') }}</p>
            </div>
        @else
            <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($images as $img)
                    <div class="group relative overflow-hidden rounded-lg bg-white shadow dark:bg-gray-800" wire:key="image-{{ $img->id }}">
                        <img src="{{ Storage::disk('public')->url($img->path) }}"
                            alt="{{ $img->alt_text }}"
                            class="h-40 w-full object-cover">
                        <div class="p-3">
                            <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                {{ $img->activity_type->label() }}
                            </span>
                            @if ($img->alt_text)
                                <p class="mt-1 truncate text-xs text-gray-500 dark:text-gray-400">{{ $img->alt_text }}</p>
                            @endif
                        </div>
                        <button wire:click="deleteImage({{ $img->id }})"
                            wire:confirm="{{ __('admin.confirm_delete_image') }}"
                            class="absolute right-2 top-2 rounded-full bg-red-600 p-1 text-white opacity-0 shadow transition group-hover:opacity-100">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $images->links() }}
            </div>
        @endif
    </div>
</div>
