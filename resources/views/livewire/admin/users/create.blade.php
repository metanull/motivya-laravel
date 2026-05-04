<div class="mx-auto max-w-lg px-4 py-8 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
            {{ __('admin.create_user_heading') }}
        </h1>
        <a
            href="{{ route('admin.users.index') }}"
            class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400"
            wire:navigate
        >
            &larr; {{ __('common.back') }}
        </a>
    </div>

    <form wire:submit="save" class="mt-6 space-y-5">
        {{-- Name --}}
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ __('common.form.name') }}
            </label>
            <input
                id="name"
                type="text"
                wire:model="form.name"
                autocomplete="name"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
            />
            @error('form.name')
                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        {{-- Email --}}
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ __('common.form.email') }}
            </label>
            <input
                id="email"
                type="email"
                wire:model="form.email"
                autocomplete="email"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
            />
            @error('form.email')
                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        {{-- Role --}}
        <div>
            <label for="role" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ __('admin.create_user_role_label') }}
            </label>
            <select
                id="role"
                wire:model="form.role"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
            >
                <option value="">{{ __('common.form.select') }}</option>
                @foreach($availableRoles as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            @error('form.role')
                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center justify-end gap-4 pt-2">
            <a
                href="{{ route('admin.users.index') }}"
                class="text-sm font-medium text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200"
                wire:navigate
            >
                {{ __('common.cancel') }}
            </a>
            <button
                type="submit"
                wire:loading.attr="disabled"
                class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50"
            >
                <span wire:loading.remove>{{ __('admin.create_user_submit') }}</span>
                <span wire:loading>{{ __('common.saving') }}</span>
            </button>
        </div>
    </form>
</div>
