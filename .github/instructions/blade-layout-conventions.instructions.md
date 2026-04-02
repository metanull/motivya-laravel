---
description: "Use when creating or modifying the master layout, navigation bars, toast/flash notification UI, footer, shared Blade partials, or the HTML head section. Covers the Blade layout hierarchy, mobile-first responsive patterns, Tailwind CSS usage, locale-aware navigation, and toast dispatch conventions."
applyTo: "resources/views/layouts/**,resources/views/components/**,resources/views/partials/**"
---

# Blade Layout Conventions

## Framework

Tailwind CSS 4 with Blade component syntax. Mobile-first responsive design. All user-facing text via `__()` — never hardcoded.

## Layout Hierarchy

```
resources/views/
├── layouts/
│   └── app.blade.php                # Master layout — every page extends this
├── components/
│   ├── nav/
│   │   ├── main.blade.php           # Top navigation bar
│   │   ├── mobile-menu.blade.php    # Off-canvas mobile navigation
│   │   ├── user-menu.blade.php      # Avatar dropdown (logged in)
│   │   └── locale-switcher.blade.php # FR / EN / NL toggle
│   ├── toast.blade.php              # Toast notification container
│   ├── footer.blade.php             # Site footer
│   └── seo/
│       └── meta.blade.php           # SEO meta tags, OG tags
├── partials/
│   ├── flash.blade.php              # Server-side flash messages (non-Livewire pages)
│   └── loading.blade.php            # Full-page loading spinner
└── livewire/
    └── ...                          # Livewire component views (see livewire-conventions)
```

## Master Layout — `layouts/app.blade.php`

Every page renders inside this layout. It provides the HTML shell, navigation, toast system, and footer.

### Required Slots

| Slot | Purpose | Required |
|------|---------|----------|
| `$slot` | Main page content | Yes |
| `$title` | `<title>` tag content | Yes |
| `$head` | Extra `<head>` elements (meta, scripts) | No |

### Structure

```blade
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
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 antialiased dark:bg-gray-900 dark:text-gray-100">

    <x-nav.main />

    <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        {{ $slot }}
    </main>

    <x-footer />
    <x-toast />

    @livewireScripts
</body>
</html>
```

### Rules

- `<html lang>` derived from `app()->getLocale()` — never hardcoded
- CSRF meta tag always present — needed for Livewire and AJAX
- Vite assets via `@vite()` — never raw `<link>` or `<script>` tags for app CSS/JS
- `@livewireStyles` in `<head>`, `@livewireScripts` before `</body>`
- Dark mode support via Tailwind `dark:` variants — respect `prefers-color-scheme`
- No inline `<style>` or `<script>` blocks — use Vite-bundled assets

## Navigation — `components/nav/main.blade.php`

### Desktop + Mobile

```blade
<nav class="border-b border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
        {{-- Logo --}}
        <a href="{{ route('home') }}" class="text-xl font-bold">
            {{ config('app.name') }}
        </a>

        {{-- Desktop links --}}
        <div class="hidden items-center gap-6 sm:flex">
            <a href="{{ route('sessions.index') }}">{{ __('common.nav.sessions') }}</a>

            @auth
                <x-nav.user-menu />
            @else
                <a href="{{ route('login') }}">{{ __('common.nav.login') }}</a>
                <a href="{{ route('register') }}">{{ __('common.nav.register') }}</a>
            @endauth

            <x-nav.locale-switcher />
        </div>

        {{-- Mobile hamburger --}}
        <button class="sm:hidden" x-data x-on:click="$dispatch('toggle-mobile-menu')">
            {{-- Heroicon: bars-3 --}}
        </button>
    </div>

    <x-nav.mobile-menu />
</nav>
```

### Navigation Rules

- **Role-based links**: Show different nav items per role — use `@can` / `Gate::check()`, never `@if(auth()->user()->role === ...)`
- **Active state**: Highlight current route with `request()->routeIs('pattern*')` and a Tailwind class
- **Mobile-first**: Primary links visible on mobile via hamburger menu, not hidden behind desktop-only nav
- **Alpine.js**: Use Alpine for client-side toggle state (mobile menu open/close, dropdowns)
- **No JS frameworks**: No Vue, React, or jQuery — only Alpine.js for small interactivity

### Role-Based Navigation

| Role | Visible Links |
|------|---------------|
| Guest | Sessions, Login, Register |
| Athlete | Sessions, My Bookings, Profile |
| Coach | Sessions, My Sessions, Dashboard, Profile |
| Accountant | Transactions, Exports, Reports |
| Admin | All coach/athlete links + Admin Panel |

Use policies or `@can` directives — never check role strings in Blade:

```blade
{{-- CORRECT --}}
@can('viewDashboard', App\Models\Coach::class)
    <a href="{{ route('coach.dashboard') }}">{{ __('common.nav.dashboard') }}</a>
@endcan

{{-- WRONG --}}
@if(auth()->user()->role->value === 'coach')
```

## Locale Switcher — `components/nav/locale-switcher.blade.php`

```blade
<div class="flex gap-1 text-sm">
    @foreach (['fr', 'en', 'nl'] as $locale)
        <a href="{{ route('locale.switch', $locale) }}"
           @class([
               'px-2 py-1 rounded',
               'font-bold text-blue-600' => app()->getLocale() === $locale,
               'text-gray-500 hover:text-gray-700' => app()->getLocale() !== $locale,
           ])>
            {{ strtoupper($locale) }}
        </a>
    @endforeach
</div>
```

- Supported locales: `fr`, `en`, `nl` — must match `i18n-localization` instruction
- Current locale visually distinct (bold + color)
- Switches via a GET route that sets session/cookie — see `i18n-localization` instructions

## Toast System — `components/toast.blade.php`

Toasts handle both Livewire event-driven notifications and server-side flash messages.

### Implementation

```blade
<div x-data="{ toasts: [] }"
     x-on:notify.window="toasts.push({ type: $event.detail.type, message: $event.detail.message, id: Date.now() }); setTimeout(() => toasts.shift(), 5000)"
     class="pointer-events-none fixed right-4 top-4 z-50 flex flex-col gap-2">
    <template x-for="toast in toasts" :key="toast.id">
        <div x-show="true"
             x-transition
             class="pointer-events-auto rounded-lg px-4 py-3 text-sm font-medium shadow-lg"
             :class="{
                 'bg-green-500 text-white': toast.type === 'success',
                 'bg-red-500 text-white': toast.type === 'error',
                 'bg-yellow-400 text-gray-900': toast.type === 'warning',
                 'bg-blue-500 text-white': toast.type === 'info',
             }">
            <span x-text="toast.message"></span>
        </div>
    </template>
</div>
```

### Dispatching Toasts

**From Livewire components** (preferred):

```php
$this->dispatch('notify', type: 'success', message: __('sessions.created'));
```

**From controllers** (redirect with flash):

```php
return redirect()->route('home')->with('flash', [
    'type' => 'success',
    'message' => __('sessions.created'),
]);
```

**Flash integration** in `toast.blade.php` — also read from session:

```blade
@if (session('flash'))
    <div x-init="$dispatch('notify', @js(session('flash')))"></div>
@endif
```

### Toast Rules

- **Types**: `success`, `error`, `warning`, `info` — no other types
- **Auto-dismiss**: 5 seconds for success/info, persistent for error (user must dismiss)
- **Messages**: Always via `__()` — never hardcoded text
- **Positioning**: Fixed top-right, `z-50`, non-blocking (pointer-events-none on container)
- **Stacking**: Multiple toasts stack vertically, newest on top
- **Accessibility**: Use `role="alert"` and `aria-live="polite"` for screen readers

## Footer — `components/footer.blade.php`

```blade
<footer class="mt-auto border-t border-gray-200 bg-white py-6 dark:border-gray-700 dark:bg-gray-800">
    <div class="mx-auto max-w-7xl px-4 text-center text-sm text-gray-500 sm:px-6 lg:px-8">
        &copy; {{ date('Y') }} {{ config('app.name') }}.
        {{ __('common.footer.rights') }}
    </div>
</footer>
```

- Year is dynamic via `date('Y')`
- App name from config — never hardcoded
- All text via `__()` translations

## Shared Patterns

### Mobile-First Responsive Breakpoints

Follow Tailwind's default breakpoints. Design mobile-first, add complexity at larger screens:

| Prefix | Min-width | Usage |
|--------|-----------|-------|
| (none) | 0px | Mobile default — always start here |
| `sm:` | 640px | Small tablets, landscape phones |
| `md:` | 768px | Tablets |
| `lg:` | 1024px | Desktops |
| `xl:` | 1280px | Wide desktops — use sparingly |

### Active Link Helper

Use a Blade component or `@class` directive:

```blade
<a href="{{ route('sessions.index') }}"
   @class([
       'text-blue-600 font-semibold' => request()->routeIs('sessions.*'),
       'text-gray-600 hover:text-gray-900' => ! request()->routeIs('sessions.*'),
   ])>
    {{ __('common.nav.sessions') }}
</a>
```

### Loading States

For Livewire actions, use `wire:loading`:

```blade
<button wire:click="save" wire:loading.attr="disabled" wire:loading.class="opacity-50">
    <span wire:loading.remove>{{ __('common.save') }}</span>
    <span wire:loading>{{ __('common.saving') }}</span>
</button>
```

## Forbidden

- No inline CSS or `style` attributes — use Tailwind utility classes exclusively
- No `<script>` tags in Blade views — use Vite-bundled JS or Alpine.js `x-data`
- No hardcoded strings — every user-facing word goes through `__()`
- No role string comparisons in Blade — use `@can`, `@cannot`, or `Gate::check()`
- No `@include` for reusable UI — use Blade components (`<x-component />`)
- No `@extends` / `@section` — use the component-based `<x-layouts.app>` pattern
- No jQuery or external JS libraries — Alpine.js for client interactivity, Livewire for server state
