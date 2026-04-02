---
description: "Use when creating or modifying Livewire components, their Blade views, wire:model bindings, component validation, form handling, pagination, authorization in components, or mobile-first layout. Covers Livewire 3 syntax, directory conventions, service delegation, and Pest test patterns."
applyTo: "app/Livewire/**,resources/views/livewire/**"
---

# Livewire Conventions

## Framework

**Livewire 3** with Blade templates and Tailwind CSS. All interactive UI in Motivya is built with Livewire — no Inertia, no Vue, no React.

## Directory Structure

### Component Classes

```
app/Livewire/
├── Session/
│   ├── Create.php
│   ├── Edit.php
│   ├── Index.php
│   └── Show.php
├── Booking/
│   ├── Book.php
│   └── Cancel.php
├── Coach/
│   ├── Dashboard.php
│   └── Profile.php
├── Athlete/
│   └── Dashboard.php
├── Admin/                      # Admin-only — edited by @admin-tools agent
│   ├── CoachApproval.php
│   └── UserManagement.php
└── Auth/
    ├── Login.php
    └── Register.php
```

### Blade Views

```
resources/views/livewire/
├── session/
│   ├── create.blade.php
│   ├── edit.blade.php
│   ├── index.blade.php
│   └── show.blade.php
├── booking/
│   ├── book.blade.php
│   └── cancel.blade.php
└── ...
```

### Naming Rules

| Item | Convention | Example |
|------|-----------|---------|
| Component class | `PascalCase` in `Feature/` subdirectory | `app/Livewire/Session/Create.php` |
| Class name | Action verb or noun — short | `Create`, `Edit`, `Index`, `Book`, `Cancel` |
| Blade view | `kebab-case` mirroring the class path | `resources/views/livewire/session/create.blade.php` |
| Blade tag | `<livewire:feature.action />` | `<livewire:session.create />` |
| Feature grouping | One directory per domain model | `Session/`, `Booking/`, `Coach/` |

## Component Class Pattern

```php
<?php

declare(strict_types=1);

namespace App\Livewire\Session;

use App\Enums\SessionLevel;
use App\Models\Session;
use App\Services\SessionService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

final class Create extends Component
{
    #[Validate('required|string|max:255')]
    public string $location = '';

    #[Validate('required|string|regex:/^[1-9]\d{3}$/')]
    public string $postalCode = '';

    #[Validate('required|date|after:today')]
    public string $date = '';

    #[Validate('required|date_format:H:i')]
    public string $startTime = '';

    #[Validate('required|date_format:H:i|after:startTime')]
    public string $endTime = '';

    #[Validate('required|integer|min:0')]
    public int $pricePerPerson = 0;

    #[Validate('required|integer|min:1')]
    public int $minParticipants = 1;

    #[Validate('required|integer|min:1|gte:minParticipants')]
    public int $maxParticipants = 1;

    public function save(SessionService $service): void
    {
        Gate::authorize('create', Session::class);

        $this->validate();

        $service->create(auth()->user(), $this->only([
            'location',
            'postalCode',
            'date',
            'startTime',
            'endTime',
            'pricePerPerson',
            'minParticipants',
            'maxParticipants',
        ]));

        $this->dispatch('notify', type: 'success', message: __('sessions.created'));
        $this->redirect(route('coach.sessions.index'), navigate: true);
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.session.create');
    }
}
```

### Class Rules

- `declare(strict_types=1)` and `final class` on every component
- Extend `Livewire\Component` — no other base class
- One component per file — no nested or anonymous components

### Properties

- Use `#[Validate]` attributes for inline validation — preferred for simple rules
- For complex rules (cross-field, conditional), override `rules()` method instead
- Property names: `camelCase` — Livewire auto-converts to `wire:model` compatible names
- Money properties: `public int` — always cents, never float
- Typed properties with defaults: `public string $location = ''`, `public int $price = 0`
- Do NOT use `public $property` without a type — always type-hint

### Lifecycle Methods

| Method | Use for |
|--------|---------|
| `mount()` | Initialize properties from route parameters or model. Runs once. |
| `render()` | Return the view. Keep it simple — just `return view('...')`. |
| `updated{Property}()` | React to a specific property change (e.g., revalidate on blur). |
| `hydrate()` / `dehydrate()` | Advanced: transform data between requests. Avoid unless needed. |

- `mount()` receives route parameters and injected dependencies
- Never put authorization in `mount()` — use route middleware or `Gate::authorize()` in action methods
- Never call services in `mount()` unless loading initial data for a form

### Action Methods

- Public methods are callable from the view via `wire:click`, `wire:submit`, etc.
- **Always authorize** before any mutation: `Gate::authorize('ability', $model)`
- **Always validate** before processing: `$this->validate()`
- **Delegate to Service** — never write business logic inline
- Catch domain exceptions and set errors:

```php
public function book(BookingService $service): void
{
    Gate::authorize('book', $this->session);

    $this->validate();

    try {
        $service->book(auth()->user(), $this->session);
        $this->dispatch('notify', type: 'success', message: __('bookings.confirmed'));
        $this->redirect(route('athlete.bookings.index'), navigate: true);
    } catch (SessionFullException) {
        $this->addError('booking', __('bookings.session_full'));
    } catch (AlreadyBookedException) {
        $this->addError('booking', __('bookings.already_booked'));
    }
}
```

### Computed Properties

Use `#[Computed]` for derived data:

```php
#[Computed]
public function availableSpots(): int
{
    return $this->session->max_participants - $this->session->current_participants;
}
```

- Cached per request — no duplicate queries
- Access in Blade via `$this->availableSpots` (not a method call)
- Use for data that depends on reactive properties and should update automatically

### Pagination

Use `WithPagination` trait for lists:

```php
use Livewire\WithPagination;

final class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.session.index', [
            'sessions' => Session::query()
                ->forCoach(auth()->user())
                ->when($this->search, fn ($q) => $q->search($this->search))
                ->latest('date')
                ->paginate(20),
        ]);
    }
}
```

- Reset page on filter/search changes via `updatedProperty()` + `$this->resetPage()`
- Pass paginated collection to the view — never all records
- Use Eloquent scopes for filtering — never raw `where()` chains in the component

## Blade View Pattern

```blade
<div>
    <h1 class="text-xl font-semibold">{{ __('sessions.create_title') }}</h1>

    <form wire:submit="save" class="mt-6 space-y-4">
        {{-- Location --}}
        <div>
            <label for="location" class="block text-sm font-medium text-gray-700">
                {{ __('sessions.label_location') }}
            </label>
            <input
                wire:model.blur="location"
                type="text"
                id="location"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            />
            @error('location')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Price (displayed in EUR, stored in cents) --}}
        <div>
            <label for="pricePerPerson" class="block text-sm font-medium text-gray-700">
                {{ __('sessions.label_price') }}
            </label>
            <div class="relative mt-1">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">€</span>
                <input
                    wire:model.blur="pricePerPerson"
                    type="number"
                    id="pricePerPerson"
                    min="0"
                    step="1"
                    class="block w-full rounded-md border-gray-300 pl-8 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
            </div>
            <p class="mt-1 text-xs text-gray-500">{{ __('sessions.hint_price_cents') }}</p>
            @error('pricePerPerson')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Submit button with loading state --}}
        <button
            type="submit"
            wire:loading.attr="disabled"
            wire:loading.class="opacity-50 cursor-not-allowed"
            class="inline-flex items-center justify-center h-12 px-6 rounded-md bg-indigo-600 text-white font-medium hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
        >
            <span wire:loading.remove>{{ __('sessions.button_create') }}</span>
            <span wire:loading class="inline-flex items-center">
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                {{ __('common.saving') }}
            </span>
        </button>
    </form>
</div>
```

### Wire Directives

| Directive | When to use |
|-----------|-------------|
| `wire:model.blur` | Text inputs — validate on blur, not on every keystroke |
| `wire:model.live` | Only when instant feedback is required (search, filters, toggles) |
| `wire:model` | Selects, checkboxes, radio buttons — sync on change |
| `wire:submit="method"` | Form submission — on the `<form>` element |
| `wire:click="method"` | Button actions outside forms |
| `wire:loading` | Show/hide/disable during network requests |
| `wire:loading.attr="disabled"` | Disable buttons during submission |
| `wire:key="unique"` | Required on every element inside `@foreach` loops |
| `wire:navigate` | On `<a>` tags for SPA-like page transitions |
| `wire:confirm` | Destructive actions — shows browser confirm dialog |

### Loading States

Every submit button must have loading states:

```blade
<button wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-not-allowed">
    <span wire:loading.remove>{{ __('label') }}</span>
    <span wire:loading>{{ __('common.saving') }}</span>
</button>
```

For long-running actions, add a `wire:target="method"` to scope the loading state to that specific action.

### Error Display

Use `@error` for inline validation and a generic error block for domain exceptions:

```blade
{{-- Inline field error --}}
@error('location')
    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
@enderror

{{-- General error for domain exceptions --}}
@error('booking')
    <div class="rounded-md bg-red-50 p-4">
        <p class="text-sm text-red-700">{{ $message }}</p>
    </div>
@enderror
```

### Navigation

Use Livewire's `wire:navigate` for internal links (SPA-like transitions):

```blade
<a href="{{ route('coach.sessions.show', $session) }}" wire:navigate>
    {{ $session->title }}
</a>
```

For programmatic redirects in component methods:

```php
$this->redirect(route('coach.sessions.index'), navigate: true);
```

## Mobile-First Layout Rules

Design for mobile viewport first. Add larger-screen rules only via breakpoint prefixes.

### Base (Mobile) Defaults

| Property | Value | Rationale |
|----------|-------|-----------|
| Layout | Single column | `flex flex-col` |
| Padding | `p-4` | Min 16px on all sides |
| Gap | `gap-4` | Min 16px between stacked items |
| Font size | `text-base` (16px) | Never below `text-sm` (14px) for body |
| Touch targets | `h-12 w-12` min (48px) | All buttons, links, inputs |
| Text inputs | `h-12` | Comfortable tappable height |

### Responsive Breakpoints

Only expand layout at `md:` (768px) and above:

```blade
{{-- Single-column on mobile, two-column on desktop --}}
<div class="flex flex-col gap-4 md:grid md:grid-cols-2 md:gap-6">
    ...
</div>
```

- `sm:` (640px) — minor adjustments only (padding, font bump)
- `md:` (768px) — primary breakpoint for multi-column, table display
- `lg:` (1024px) — max-width containers, wide sidebar layouts
- Never design desktop-first and add mobile overrides

### Tables → Cards on Mobile

Tables are unreadable on mobile. Use the card/table swap pattern:

```blade
{{-- Mobile: card layout --}}
<div class="space-y-4 md:hidden">
    @foreach ($sessions as $session)
        <div wire:key="session-card-{{ $session->id }}" class="rounded-lg border p-4">
            <p class="font-semibold">{{ $session->activity_type }}</p>
            <p class="text-sm text-gray-500">
                <x-date :value="$session->date" /> · {{ $session->start_time }}
            </p>
            <p class="mt-2"><x-money :amount="$session->price_per_person" /></p>
        </div>
    @endforeach
</div>

{{-- Desktop: table layout --}}
<table class="hidden md:table w-full">
    <thead>
        <tr>
            <th>{{ __('sessions.activity') }}</th>
            <th>{{ __('sessions.date') }}</th>
            <th>{{ __('sessions.price') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($sessions as $session)
            <tr wire:key="session-row-{{ $session->id }}">
                <td>{{ $session->activity_type }}</td>
                <td><x-date :value="$session->date" /></td>
                <td><x-money :amount="$session->price_per_person" /></td>
            </tr>
        @endforeach
    </tbody>
</table>
```

### Image Handling

```blade
<img
    src="{{ $session->cover_image_url }}"
    alt="{{ $session->activity_type }}"
    loading="lazy"
    width="400"
    height="300"
    class="w-full rounded-lg object-cover aspect-[4/3]"
/>
```

- Always `loading="lazy"` on below-the-fold images
- Always explicit `width` + `height` or `aspect-ratio` to prevent layout shift
- Use `object-cover` + aspect ratio for consistent sizing

## Authorization

- Route middleware handles role-gating (e.g., `role:coach` on coach routes)
- Component action methods call `Gate::authorize()` before mutations
- Use `@can` / `@cannot` in Blade for conditional UI rendering:

```blade
@can('update', $session)
    <a href="{{ route('coach.sessions.edit', $session) }}" wire:navigate>
        {{ __('sessions.edit_button') }}
    </a>
@endcan
```

- Never duplicate policy logic in Blade — always delegate to the policy via `@can`
- Never check roles directly in Blade (`@if($user->role === ...)`) — use `@can`

## Localization

- All user-facing strings via `{{ __('domain.key') }}` — no hardcoded text
- Translation files organized by domain: `lang/fr/sessions.php`, `lang/fr/bookings.php`
- Use `<x-money>` for amounts, `<x-date>` for dates — never format inline
- Shared UI strings in `lang/{locale}/common.php`: `saving`, `cancel`, `confirm`, `delete`

## Toast Notifications

Dispatch browser events for toast/flash messaging:

```php
// In component action method
$this->dispatch('notify', type: 'success', message: __('sessions.created'));
$this->dispatch('notify', type: 'error', message: __('sessions.create_failed'));
```

The main layout listens for the `notify` event and renders the toast. Components never render toast UI directly.

## Forbidden

- Do NOT put business logic in components — delegate to services
- Do NOT dispatch events (domain events) from components — services dispatch events
- Do NOT send notifications from components — services dispatch events, listeners send notifications
- Do NOT use `@livewire` directive — use `<livewire:name />` tag syntax
- Do NOT use `$this->emit()` — Livewire 3 uses `$this->dispatch()`
- Do NOT use `wire:model.defer` — deprecated in Livewire 3 (it's the default behavior)
- Do NOT use untyped public properties — always add type hints
- Do NOT use `wire:model.live` on text inputs unless instant feedback is essential (search fields only)
- Do NOT render money amounts with inline `number_format()` — always use `<x-money>`
- Do NOT check roles directly in Blade — use `@can` with policies
- Do NOT load all records — always paginate lists with `WithPagination`
- Do NOT use `sleep()` or polling (`wire:poll`) without a documented reason
