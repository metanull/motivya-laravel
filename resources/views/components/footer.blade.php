{{-- Site footer --}}
<footer class="mt-auto border-t border-gray-200 bg-white py-6 dark:border-gray-700 dark:bg-gray-800">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col items-center gap-2 text-center text-sm text-gray-500 dark:text-gray-400 sm:flex-row sm:justify-between">
            <div class="flex flex-col items-center gap-1 sm:flex-row sm:gap-3">
                <p>
                    &copy; {{ date('Y') }} {{ config('app.name') }}.
                    {{ __('common.footer.rights') }}
                </p>
                <a href="{{ route('privacy') }}" class="hover:text-gray-700 dark:hover:text-gray-200">
                    {{ __('common.footer.privacy') }}
                </a>
            </div>
            <x-nav.locale-switcher />
        </div>
    </div>
</footer>
