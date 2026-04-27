<div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
        {{ __('admin.data_export_heading') }}
    </h1>
    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
        {{ __('admin.data_export_description') }}
    </p>

    <div class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-3">
        {{-- Coaches Export --}}
        <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                {{ __('admin.export_coaches_title') }}
            </h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ __('admin.export_coaches_description') }}
            </p>
            <div class="mt-4">
                <button
                    type="button"
                    wire:click="exportCoaches"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                >
                    {{ __('admin.export_csv') }}
                </button>
            </div>
        </div>

        {{-- Sessions Export --}}
        <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                {{ __('admin.export_sessions_title') }}
            </h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ __('admin.export_sessions_description') }}
            </p>
            <div class="mt-4">
                <button
                    type="button"
                    wire:click="exportSessions"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                >
                    {{ __('admin.export_csv') }}
                </button>
            </div>
        </div>

        {{-- Payments Export --}}
        <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                {{ __('admin.export_payments_title') }}
            </h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ __('admin.export_payments_description') }}
            </p>
            <div class="mt-4">
                <button
                    type="button"
                    wire:click="exportPayments"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                >
                    {{ __('admin.export_csv') }}
                </button>
            </div>
        </div>
    </div>
</div>
