---
description: "Scaffold a Livewire component with Blade view, mobile-first layout, localized strings, and Pest tests"
agent: "agent"
argument-hint: "Component description, e.g. 'Session booking form for athletes'"
tools: [search, createFile, editFile, runInTerminal]
---

# Scaffold a Livewire Component

Generate a Livewire component with its Blade view, localization files, and Pest tests from the user's description.

## Project Context

- **Laravel 12**, PHP 8.2+, **Livewire 3** with Blade.
- Follow [copilot-instructions.md](../copilot-instructions.md) and [php.instructions.md](../instructions/php.instructions.md).
- Four roles: `coach`, `athlete`, `accountant`, `admin`.
- All monetary amounts stored as **integers in cents** (EUR).
- Business logic in **Service** classes — the component only orchestrates UI interaction + calls services.
- Three locales: `fr` (default), `en`, `nl`. All user-facing strings via `__('key')`.
- Mobile-first: design for small screens first, progressive enhancement for larger viewports.

## Clarify Before Generating

If the user's description is ambiguous, ask (at most 2 questions):

1. Which **role(s)** will use this component?
2. Does it display a **list/table**, a **form**, or a **detail view**?

If the description is clear enough, proceed without asking.

## What to Generate

### 1. Livewire Component Class

Use the generator, then edit the result:

```bash
php artisan make:livewire <Feature>/<Action>
```

The component class must:

- Live in `app/Livewire/<Feature>/` using PascalCase subdirectories (e.g. `app/Livewire/Session/Book.php`).
- Use **Livewire 3** syntax: `#[Validate]` attributes for property validation, `mount()` for initialization, public methods for actions.
- Never contain business logic — inject and call the appropriate **Service** class.
- Use `$this->authorize()` or `Gate::authorize()` for permission checks when the action mutates data.
- Dispatch browser events for toast notifications: `$this->dispatch('notify', ...)`.
- Catch service exceptions and set `$this->addError('key', __('message'))` — never let exceptions bubble to the view.
- Use computed properties (`#[Computed]`) for derived data instead of recalculating in the template.
- Paginate lists with Livewire's `WithPagination` trait.

### 2. Blade View

Create the corresponding view in `resources/views/livewire/<feature>/<action>.blade.php`.

#### Mobile-First Layout Rules

- Use a **single-column layout** as the base — expand to multi-column via `md:` breakpoint utilities only.
- Touch targets: minimum `h-12 w-12` (48px) for all interactive elements.
- Font sizes: base `text-base` (16px), never below `text-sm` (14px) for body text.
- Spacing: prefer `p-4` / `gap-4` as minimum padding on mobile.
- Forms: stack all fields vertically on mobile, use `md:grid md:grid-cols-2` for wider screens only.
- Tables: on mobile, use a **card-based list** layout instead of `<table>`. Switch to `<table>` at `md:` breakpoint using `hidden md:table` / `md:hidden` pattern.
- Images: always include `loading="lazy"` and set explicit `width`/`height` or use `aspect-ratio`.

#### Blade Conventions

- All user-facing strings via `{{ __('feature.key') }}` — no hardcoded text.
- Use `@error('field')` for inline validation messages.
- Use `wire:model.blur` for text inputs (debounce), `wire:model.live` only when instant feedback is required.
- Use `wire:loading` states on submit buttons: disable + show spinner.
- Include `wire:key` on looped elements to prevent DOM diffing issues.
- Money display: format cents to EUR using a shared Blade component or helper (e.g. `<x-money :amount="$price" />`).
- Dates: use Carbon with locale-aware formatting — `$date->translatedFormat('d F Y')`.
- Accessibility: every `<input>` must have a `<label>`, use `aria-live="polite"` on dynamic content regions.

### 3. Localization Files

Create or update translation files for all three locales:

- `lang/fr/feature.php` — French (default, complete this first)
- `lang/en/feature.php` — English translation
- `lang/nl/feature.php` — Dutch translation

Each file returns a flat PHP array:

```php
return [
    'title' => '...',
    'button_submit' => '...',
    'label_field' => '...',
    'error_required' => '...',
    // validation messages as 'validation.field_rule' => '...'
];
```

Key naming convention: `feature.context_element` (e.g. `session.booking_confirm_button`).

### 4. Pest Component Test

Create a test in `tests/Feature/Livewire/<Feature>/<Action>Test.php`:

```php
use Livewire\Livewire;

it('renders the component', function () {
    Livewire::test(ComponentClass::class)
        ->assertStatus(200);
});
```

The test must cover:

- **Rendering**: component mounts and renders without errors.
- **Validation**: submitting with invalid data shows localized error messages.
- **Happy path**: submitting valid data calls the service and produces the correct state.
- **Authorization**: users without the correct role see a 403.
- **Loading states**: assert `wire:loading` attributes exist on submit buttons.
- Use factories for test data. Mock service classes when testing component behavior in isolation.

### 5. Route & Navigation (if new page)

If this component is a full-page component (not embedded in another view):

- Register the route in `routes/web.php` with `auth` and role middleware.
- Use `kebab-case` for URL segments: `/sessions/book`, not `/sessions/Book`.
- Component route syntax: `Route::get('/path', ComponentClass::class)->name('name');`

## Output Format

- Generate each file with its full path.
- Use `php artisan make:livewire` to scaffold, then edit the generated files.
- Follow PSR-12 and the project's naming conventions from [php.instructions.md](../instructions/php.instructions.md).
- After generating, list all created/modified files with a brief summary.
