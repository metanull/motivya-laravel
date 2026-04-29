<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name') }} — {{ config('app.name') }}</title>

    <x-seo.meta />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    {{ $head ?? '' }}
    @stack('head')
</head>
<body class="flex min-h-screen flex-col bg-gray-50 text-gray-900 antialiased dark:bg-gray-900 dark:text-gray-100">

    <x-nav.main />

    @auth
        @if (! auth()->user()->hasVerifiedEmail())
            <div class="bg-yellow-50 px-4 py-3 text-center text-sm text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">
                {!! __('auth.unverified_email_banner', ['url' => route('verification.notice')]) !!}
            </div>
        @endif
    @endauth

    <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-6 sm:px-6 lg:px-8">
        {{ $slot }}
    </main>

    <x-footer />
    <x-toast />

    @livewireScripts
    @stack('scripts')

</body>
</html>
