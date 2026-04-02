---
description: "Scaffold reusable anonymous or class-based Blade components for cross-app UI patterns: money formatting, date display, status badges, role badges, and custom variants"
agent: "agent"
argument-hint: "Component name or description, e.g. 'money' or 'session status badge with color coding'"
tools: [search, createFile, editFile, runInTerminal]
---

# Scaffold a Shared Blade Component

Generate a reusable Blade component from the user's description. The component must be usable project-wide via `<x-name>` syntax.

## Project Context

- **Laravel 12**, PHP 8.2+, **Livewire 3**, Blade, Tailwind CSS.
- Follow [copilot-instructions.md](../copilot-instructions.md) and [php.instructions.md](../instructions/php.instructions.md).
- All monetary amounts stored as **integers in cents** (EUR).
- Three locales: `fr` (default), `en`, `nl`. All user-facing strings via `__('key')`.
- Mobile-first: minimum touch target `h-12 w-12`, base text `text-base`, never below `text-sm`.
- Session states: `draft`, `published`, `confirmed`, `completed`, `cancelled`.
- Booking states: `pending_payment`, `confirmed`, `cancelled`, `refunded`.
- User roles: `coach`, `athlete`, `accountant`, `admin`.

## Clarify Before Generating

If the user provides only a name like "money" or "badge", proceed using the built-in catalog below. Only ask a question if the request describes something **not** in the catalog and the expected props or behavior are genuinely ambiguous.

## Built-In Component Catalog

When the user requests one of these, use the specification directly — no questions needed:

### `<x-money :amount="$cents" />`
- **Type**: Anonymous Blade component
- **Props**: `amount` (int, cents), `currency` (string, default `'EUR'`), `showSign` (bool, default `false`)
- **Rendering**: Format cents → EUR with locale-aware decimal/thousands separators. Use `number_format()` with `,` decimal and `.` thousands for fr-BE, or `NumberFormatter` if available.
- **Examples**: `1250` → `12,50 €` | `0` → `0,00 €` | `-1250` with `showSign` → `−12,50 €`
- **Rules**: Never use floats for calculation. Division by 100 happens **only** at display time. Include `&nbsp;` between amount and `€` symbol.

### `<x-date :value="$carbon" />`
- **Type**: Anonymous Blade component
- **Props**: `value` (Carbon instance), `format` (string, default `'d F Y'`), `relative` (bool, default `false`)
- **Rendering**: Use `$value->translatedFormat($format)` for locale-aware output. When `relative` is true, show `diffForHumans()` with a `title` attribute containing the full date.
- **Examples**: `<x-date :value="$session->date" />` → `15 mars 2026` | with `relative` → `dans 3 jours`
- **Rules**: Always respect the app locale. Wrap in `<time datetime="...">` HTML element for semantics.

### `<x-badge :label="$text" color="green" />`
- **Type**: Anonymous Blade component
- **Props**: `label` (string), `color` (string: `green`, `yellow`, `red`, `blue`, `gray`, `indigo`), `size` (string: `sm`, `md`, default `md`)
- **Rendering**: Rounded pill with bg + text color. Use Tailwind classes keyed by color. Size `sm` = `text-xs px-2 py-0.5`, `md` = `text-sm px-2.5 py-1`.
- **Rules**: Use a `@php` map or `match` to resolve color → Tailwind classes. Never use dynamic class strings that Tailwind can't purge — use full class names in the map.

### `<x-session-status :status="$session->status" />`
- **Type**: Anonymous Blade component (wraps `<x-badge>`)
- **Props**: `status` (string: `draft`, `published`, `confirmed`, `completed`, `cancelled`)
- **Color map**: `draft` → `gray`, `published` → `blue`, `confirmed` → `green`, `completed` → `indigo`, `cancelled` → `red`
- **Label**: Localized via `__('session.status_' . $status)` — never hardcode text.
- **Rules**: Must accept only valid session states. On unknown status, render `gray` badge with the raw value.

### `<x-booking-status :status="$booking->status" />`
- **Type**: Anonymous Blade component (wraps `<x-badge>`)
- **Props**: `status` (string: `pending_payment`, `confirmed`, `cancelled`, `refunded`)
- **Color map**: `pending_payment` → `yellow`, `confirmed` → `green`, `cancelled` → `red`, `refunded` → `gray`
- **Label**: Localized via `__('booking.status_' . $status)`.

### `<x-role-badge :role="$user->role" />`
- **Type**: Anonymous Blade component (wraps `<x-badge>`)
- **Props**: `role` (string or `UserRole` enum: `coach`, `athlete`, `accountant`, `admin`)
- **Color map**: `coach` → `indigo`, `athlete` → `blue`, `accountant` → `green`, `admin` → `red`
- **Label**: Localized via `__('user.role_' . $role)`. If `$role` is a `UserRole` enum, use `$role->value`.

## What to Generate

### 1. Component File

**Anonymous components** (no PHP logic beyond props):
- Create in `resources/views/components/<name>.blade.php`
- Use `@props([...])` directive at the top of the file with defaults.

**Class-based components** (when computed logic is needed):
- Run `php artisan make:component <Name>` and edit both files.
- Keep the `render()` method minimal — compute derived values in the constructor or as public methods.

### 2. Localization Strings

If the component renders user-facing text (labels, statuses), add entries to:
- `lang/fr/component-name.php` (or the feature-level file if one exists, e.g. `lang/fr/session.php`)
- `lang/en/component-name.php`
- `lang/nl/component-name.php`

Do not create a new lang file if the keys logically belong in an existing feature file. For status components, merge keys into the feature file (e.g. `session.status_draft`).

### 3. Pest Component Test

Create `tests/Feature/Components/<Name>Test.php`:

```php
use Illuminate\Support\Facades\View;

it('renders the <x-name> component', function () {
    $view = $this->blade('<x-name :prop="$value" />', ['value' => ...]);
    $view->assertSee('expected output');
});
```

Test coverage must include:
- **Default rendering**: component renders with required props.
- **Variants**: each enumerated value (status, color, role) produces correct classes/text.
- **Edge cases**: zero amount for money, null/unknown values for badges, past dates.
- **Localization**: assert rendered text matches the `fr` locale translation.
- **Accessibility**: assert semantic HTML elements exist where specified (e.g. `<time>` for dates).

## Output Format

Present each file as a separate fenced code block with the file path as a comment header. Create all files using the tools — do not just display the code.

After generating, list the component's usage signature and 2-3 Blade usage examples the user can copy-paste.
