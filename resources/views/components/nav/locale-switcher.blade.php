{{-- Locale switcher: FR / EN / NL --}}
<div class="flex items-center gap-1 text-sm">
    @foreach (['fr', 'en', 'nl'] as $locale)
        <a href="{{ route('locale.switch', $locale) }}"
           @class([
               'px-2 py-1 rounded font-bold text-indigo-600 dark:text-indigo-400' => app()->getLocale() === $locale,
               'px-2 py-1 rounded text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' => app()->getLocale() !== $locale,
           ])
           aria-current="{{ app()->getLocale() === $locale ? 'true' : 'false' }}">
            {{ strtoupper($locale) }}
        </a>
    @endforeach
</div>
