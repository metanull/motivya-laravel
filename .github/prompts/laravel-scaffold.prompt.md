---
description: "Scaffold a full Laravel feature slice from a short description: migration, model, factory, service, form request, policy, controller, Livewire component, Blade views, and Pest tests"
agent: "agent"
argument-hint: "Feature description, e.g. 'Coach can create and manage sport sessions'"
tools: [search, createFile, editFile, runInTerminal]
---

# Scaffold a Full Laravel Feature Slice

Generate all the files needed for the feature described by the user. Each feature must produce the complete vertical slice listed below.

## Project Context

- **Laravel 12**, PHP 8.2+, **Livewire** + Blade (mobile-first), **Pest** tests.
- Follow [copilot-instructions.md](../copilot-instructions.md) and [php.instructions.md](../instructions/php.instructions.md).
- Four roles: `coach`, `athlete`, `accountant`, `admin`. The user will specify which role(s) own this feature.
- All monetary amounts stored as **integers in cents** (EUR).
- Business logic in **Service** classes (`app/Services/`), not controllers or models.
- User-facing strings via `lang/` localization files — never hardcode text.
- Database: MySQL in prod, SQLite in dev/test.

## Clarify Before Generating

If the user's description is ambiguous, ask (at most 3 questions):

1. Which **role(s)** own this feature?
2. Does it involve **monetary amounts** or **scheduling**?
3. Any **relationships** to existing models?

If the description is clear enough, proceed without asking.

## What to Generate

### 1. Migration

- `php artisan make:migration create_<table>_table`
- Use `snake_case` table/column names.
- Money columns: `unsignedBigInteger` (cents). Never `decimal` or `float`.
- Include proper indexes and foreign key constraints.
- Add `$table->timestamps()` and `$table->softDeletes()` where appropriate.

### 2. Model

- `php artisan make:model <Name>`
- Define `$fillable`, `$casts`, and relationships.
- Cast money fields to `int`. Cast dates to `datetime`. Cast booleans to `boolean`.
- Add relationship methods (belongsTo, hasMany, etc.) with return types.
- No business logic in the model — only accessors, mutators, scopes, and relationships.

### 3. Factory

- `php artisan make:factory <Name>Factory`
- Realistic fake data using Faker. Money in cents (`$this->faker->numberBetween(500, 50000)`).
- Define useful states for common test scenarios (e.g. `cancelled()`, `published()`).

### 4. Service Class

- Create `app/Services/<Feature>Service.php` manually (no artisan command).
- All business logic lives here: creation, updates, state transitions, calculations.
- Use DB transactions for multi-step mutations.
- Inject dependencies via constructor.
- Throw domain-specific exceptions on failure.

### 5. Form Request(s)

- `php artisan make:request <Action><Name>Request` (e.g. `StoreSessionRequest`).
- The `authorize()` method should delegate to the Policy or return `true` if auth is route-level.
- Validation rules in `rules()`. Use `'amount' => ['required', 'integer', 'min:0']` for money.
- Custom error messages in `messages()` using localization keys.

### 6. Policy

- `php artisan make:policy <Name>Policy --model=<Name>`
- Map methods to the four roles. Use `Gate::before()` for admin override only if needed.
- Every method must have an explicit return — no fall-through.

### 7. Controller

- `php artisan make:controller <Name>Controller`
- Thin controller: validate via Form Request, delegate to Service, return response.
- RESTful resource routes where possible.
- Use `$this->authorize()` or Form Request authorization — never manual role checks.

### 8. Livewire Component + Blade View

- `php artisan make:livewire <Feature>/<Action>` (e.g. `Session/Create`).
- Component handles user interaction, calls the Service class.
- Blade template in `resources/views/livewire/` — mobile-first, minimal logic.
- Use `wire:model`, `wire:click`, validation via `#[Validate]` attributes.
- All user-facing strings via `{{ __('key') }}` or `@lang('key')`.

### 9. Routes

- Register routes in the appropriate file (`routes/web.php`).
- Apply `auth` and role middleware.
- Use `Route::resource()` for standard CRUD, explicit routes for custom actions.
- Follow `kebab-case` for URL segments.

### 10. Pest Tests

Create both feature and unit tests:

**Feature test** (`tests/Feature/<Name>Test.php`):
- Test each CRUD endpoint with authorized and unauthorized roles.
- Test validation by submitting invalid data.
- Test Livewire components using `Livewire::test()`.
- Use factories and `actingAs()` for authentication.

**Unit test** (`tests/Unit/<Name>ServiceTest.php`):
- Test each Service method in isolation.
- Test edge cases: boundary values, duplicate handling, state transitions.
- For money: test with 0, 1 cent, large amounts, and rounding scenarios.

### 11. Localization

- Add French (`lang/fr/`) keys for all user-facing strings (primary locale).
- Add placeholder English (`lang/en/`) and Dutch (`lang/nl/`) files with the same keys.

## Output Format

- Use `php artisan make:*` generators first, then edit the generated files.
- List every created/modified file at the end with a brief description.
- Follow PSR-12 and the naming conventions in [php.instructions.md](../instructions/php.instructions.md).
- Add PHPDoc annotations to all public methods.
- Ensure the migration is runnable and tests pass with `php artisan test`.
