<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ isset($title) ? $title . ' — ' . config('app.name') : config('app.name') }}</title>

    <x-seo.meta :title="isset($title) ? $title . ' — ' . config('app.name') : config('app.name')" />

    @livewireStyles

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{ $head ?? '' }}
</head>
<body class="flex min-h-screen flex-col bg-gray-50 text-gray-900 antialiased dark:bg-gray-900 dark:text-gray-100">

    <x-nav.main />

    <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-6 sm:px-6 lg:px-8">
        {{ $slot }}
    </main>

    <x-footer />
    <x-toast />

    @livewireScripts

</body>
</html>
