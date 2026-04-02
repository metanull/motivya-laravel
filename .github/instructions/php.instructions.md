---
description: "Use when writing any PHP code in the Motivya project. Covers Laravel 12 conventions, strict types, backed enums, service class patterns, integer-cents money, localization rules, and Pint formatting. Apply to all PHP files."
applyTo: "**/*.php"
---
# PHP Coding Standards — Motivya

## Strict Types

Every PHP file must declare strict types on line 1:

```php
<?php

declare(strict_types=1);
```

No exceptions — migrations, seeders, config files, tests, service providers, all of them.

## General Guidelines

- **Follow Laravel 12 conventions** for directory structure, naming, and patterns
- Keep classes, methods, and functions focused on a single responsibility
- Handle all exceptions gracefully — never swallow without logging
- Use `config()` helper for configuration — never `env()` outside `config/` files
- Use `Storage::disk()` — never raw filesystem functions
- Use Laravel built-in features over vendor-specific or low-level PHP equivalents
- Never implement fallback logic or workarounds without explicit approval
- Never ignore warnings or errors, even pre-existing ones

## Backed Enums

Use backed PHP enums for all finite value sets. Never hardcode string/int constants for domain concepts:

```php
enum UserRole: string
{
    case Coach      = 'coach';
    case Athlete    = 'athlete';
    case Accountant = 'accountant';
    case Admin      = 'admin';
}

enum SessionStatus: string
{
    case Draft     = 'draft';
    case Published = 'published';
    case Confirmed = 'confirmed';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}

enum BookingStatus: string
{
    case Pending   = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
    case Refunded  = 'refunded';
}
```

Rules:
- Enums live in `app/Enums/`
- Always `string`-backed (never `int`-backed) — database columns store the string value
- Cast enum columns in Eloquent models: `protected $casts = ['role' => UserRole::class]`
- Reference enums in policies, middleware, and services — never compare against raw strings
- Use `->value` only at serialization boundaries (API responses, Blade views)

## Money — Integer Cents

All monetary amounts are stored and computed as **integers in EUR cents**. No floats, no `bcmath`, no `Money` library.

```php
// Correct
$priceInCents = 1250; // €12.50

// Wrong
$price = 12.50;
$price = '12.50';
```

Rules:
- Database columns: `unsignedInteger` or `unsignedBigInteger`, never `decimal` or `float`
- Rounding: `(int) round($value)` with PHP default half-up — apply once at the end of a calculation chain
- Display: format in Blade via `number_format($cents / 100, 2, ',', '.')` or the `<x-money>` component
- Never divide cents by 100 in business logic — only at the presentation layer
- Store Stripe amounts directly (Stripe also uses cents)

## Service Classes

Business logic lives in `app/Services/` — not in controllers, models, or Livewire components.

```php
final class BookingService
{
    public function __construct(
        private readonly SessionService $sessionService,
        private readonly StripePaymentService $stripeService,
    ) {}

    public function book(User $athlete, Session $session): Booking
    {
        // ...
    }
}
```

Rules:
- Controllers call services; services call other services or repositories
- Services are `final` by default — extend only with a documented reason
- Inject dependencies via constructor promotion with `private readonly`
- Services should **not** extend any base class
- Use DB transactions in the service layer when atomicity is needed, not in controllers
- One public method per action is preferred — `book()`, `cancel()`, `confirm()` — not a god-class

## Form Requests

Use Form Request classes for **all** input validation. Never validate inline in controllers:

```php
final class StoreSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === UserRole::Coach;
    }

    public function rules(): array
    {
        return [
            'title'            => ['required', 'string', 'max:255'],
            'price_in_cents'   => ['required', 'integer', 'min:0'],
            'max_participants' => ['required', 'integer', 'min:1'],
        ];
    }
}
```

## Localization

All user-facing strings go through Laravel's localization system. Three locales: `fr` (default), `en`, `nl`.

- Never hardcode user-facing text in PHP or Blade
- Use `__('messages.key')` or `trans()` helpers
- Translation files live in `lang/{locale}/` as PHP arrays
- Dates: use `translatedFormat()` from Carbon — never `format()` for user-visible dates

## Naming Conventions

- **PSR-12** coding standard, **PSR-4** autoloading
- When Laravel 12 has a specific convention, follow it strictly over the defaults below

| Context | Convention | Example |
|---------|-----------|---------|
| Classes | PascalCase | `BookingService`, `SessionPolicy` |
| Methods | camelCase | `cancelBooking()`, `isVatSubject()` |
| Variables | camelCase | `$priceInCents`, `$coachPayout` |
| Constants | UPPER_CASE | `MAX_PARTICIPANTS`, `VAT_RATE_STANDARD` |
| DB columns/tables | snake_case | `coach_id`, `price_in_cents`, `sport_sessions` |
| Config files | snake_case | `stripe.php`, `peppol.php` |
| Migration files | snake_case | `create_sport_sessions_table.php` |
| Routes/URLs | kebab-case | `/coach/sessions/{session}/bookings` |

## Documentation

- Use PHPDoc on public methods of services, controllers, and non-trivial model methods
- Use dedoc/Scramble annotations on controller methods for API documentation
- Comment complex logic, non-obvious decisions, and business rules
- Do not over-comment obvious code
- Do not add comments unrelated to the code or business logic

## Code Quality

- **Verify formatting with Laravel Pint** before committing — never ignore Pint violations
- Never ignore lint errors, warnings, or failing tests
- Use PHPStan/Larastan if configured — respect the configured level

## Testing

- Use **Pest** for all tests, not PHPUnit syntax
- Feature tests for HTTP endpoints and Livewire components
- Unit tests for service classes and calculation logic
- Use `$this->actingAs(User::factory()->create(['role' => UserRole::Coach]))` — never skip auth in tests
- Use SQLite `:memory:` database for tests
- Factories over manual model creation — every model must have a factory