<x-layouts.app>
    <x-slot:title>{{ __('privacy.title') }}</x-slot:title>

    <x-slot:head>
        <style>
            @media print {
                nav, footer, .no-print, [x-data] { display: none !important; }
                main { max-width: 100% !important; padding: 0 !important; margin: 0 !important; }
                body { background: white !important; color: black !important; }
                a { text-decoration: underline; color: black !important; }
                a::after { content: " (" attr(href) ")"; font-size: 0.8em; }
            }
        </style>
    </x-slot:head>

    <article class="prose mx-auto max-w-3xl dark:prose-invert lg:prose-lg">

        <div class="mb-8 flex items-center justify-between">
            <h1>{{ __('privacy.title') }}</h1>
            <button type="button"
                    onclick="window.print()"
                    class="no-print inline-flex items-center gap-2 rounded-md bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                {{ __('privacy.print') }}
            </button>
        </div>

        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ __('privacy.last_updated', ['date' => '2026-04-07']) }}
        </p>

        {{-- Introduction --}}
        <h2>{{ __('privacy.intro.heading') }}</h2>
        <p>{{ __('privacy.intro.body') }}</p>

        {{-- Data Controller --}}
        <h2>{{ __('privacy.controller.heading') }}</h2>
        <p>{{ __('privacy.controller.body') }}</p>

        {{-- Data Collected --}}
        <h2>{{ __('privacy.data_collected.heading') }}</h2>
        <p>{{ __('privacy.data_collected.body') }}</p>
        <ul>
            @foreach (__('privacy.data_collected.items') as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>

        {{-- Purpose --}}
        <h2>{{ __('privacy.purpose.heading') }}</h2>
        <p>{{ __('privacy.purpose.body') }}</p>
        <ul>
            @foreach (__('privacy.purpose.items') as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>

        {{-- Legal Basis --}}
        <h2>{{ __('privacy.legal_basis.heading') }}</h2>
        <p>{{ __('privacy.legal_basis.body') }}</p>

        {{-- Retention --}}
        <h2>{{ __('privacy.retention.heading') }}</h2>
        <p>{{ __('privacy.retention.body') }}</p>

        {{-- Your Rights --}}
        <h2>{{ __('privacy.rights.heading') }}</h2>
        <p>{{ __('privacy.rights.body') }}</p>
        <ul>
            @foreach (__('privacy.rights.items') as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>
        <p>{{ __('privacy.rights.contact') }}</p>

        {{-- Third Parties --}}
        <h2>{{ __('privacy.third_parties.heading') }}</h2>
        <p>{{ __('privacy.third_parties.body') }}</p>
        <ul>
            @foreach (__('privacy.third_parties.items') as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>

        {{-- Cookies --}}
        <h2>{{ __('privacy.cookies.heading') }}</h2>
        <p>{{ __('privacy.cookies.body') }}</p>

        {{-- Security --}}
        <h2>{{ __('privacy.security.heading') }}</h2>
        <p>{{ __('privacy.security.body') }}</p>

        {{-- Changes --}}
        <h2>{{ __('privacy.changes.heading') }}</h2>
        <p>{{ __('privacy.changes.body') }}</p>

    </article>

</x-layouts.app>
