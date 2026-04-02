---
description: "Use when writing, reviewing, or structuring Pest tests, configuring phpunit.xml, setting up test fixtures, creating factories/seeders for tests, mocking external services, or deciding what to test and where to put it. Covers directory layout, naming conventions, factory patterns, CI expectations, and Motivya-specific test rules."
applyTo: "tests/**,database/factories/**,phpunit.xml*"
---

# Testing Conventions

## Framework

- **Pest** (Laravel's default in v12) for all tests — never PHPUnit class syntax.
- `declare(strict_types=1)` on every test file.
- Tests run on **SQLite `:memory:`** — never MySQL. The `phpunit.xml` sets `DB_CONNECTION=sqlite` and `DB_DATABASE=:memory:`.

## Directory Structure

```
tests/
├── Feature/
│   ├── Api/                    # API endpoint tests (JSON requests via Sanctum)
│   │   ├── SessionControllerTest.php
│   │   └── BookingControllerTest.php
│   ├── Livewire/               # Livewire component tests
│   │   ├── Session/
│   │   │   └── CreateTest.php
│   │   └── Booking/
│   │       └── CancelTest.php
│   ├── Policies/               # Policy authorization tests
│   │   ├── SessionPolicyTest.php
│   │   └── BookingPolicyTest.php
│   ├── Requests/               # Form request validation tests
│   │   ├── StoreSessionRequestTest.php
│   │   └── UpdateBookingRequestTest.php
│   ├── Components/             # Blade component render tests
│   │   └── MoneyTest.php
│   ├── Webhooks/               # Stripe webhook handling tests
│   │   └── StripeWebhookTest.php
│   ├── Admin/                  # Admin-only feature tests
│   │   └── CoachApprovalTest.php
│   └── Auth/                   # Authentication flow tests
│       ├── LoginTest.php
│       └── GoogleOAuthTest.php
├── Unit/
│   ├── Services/               # Service class unit tests
│   │   ├── BookingServiceTest.php
│   │   ├── VatCalculationServiceTest.php
│   │   └── PayoutServiceTest.php
│   ├── Enums/                  # Enum behavior tests (labels, casting)
│   │   └── SessionStatusTest.php
│   └── Rules/                  # Custom validation rule tests
│       └── BelgianPostalCodeTest.php
├── Pest.php                    # Global Pest config (uses, helpers)
└── TestCase.php                # Base test case (Laravel default)
```

### Placement Rules

| Test type | Directory | Database | What to test |
|-----------|-----------|----------|-------------|
| HTTP endpoints (web + API) | `tests/Feature/Api/`, `tests/Feature/` | Yes | Full request lifecycle: routing, middleware, validation, response |
| Livewire components | `tests/Feature/Livewire/` | Yes | Rendering, wire interactions, validation, auth |
| Policies | `tests/Feature/Policies/` | Yes | All 4 roles × every policy method |
| Form requests | `tests/Feature/Requests/` | Yes | Authorization, required fields, formats, cross-field rules |
| Webhooks | `tests/Feature/Webhooks/` | Yes | Signature verification, payload processing, idempotency |
| Service classes | `tests/Unit/Services/` | Yes | Business logic, state transitions, money calculations |
| Pure calculations | `tests/Unit/` | No | VAT math, payout formulas — no DB needed |
| Enums | `tests/Unit/Enums/` | No | Label methods, casting behavior |

Use `Feature/` when the test touches HTTP, middleware, or database. Use `Unit/` when testing pure logic or service methods that need the DB but not the HTTP layer.

## File Template

```php
<?php

declare(strict_types=1);

use App\Enums\SessionStatus;
use App\Enums\UserRole;
use App\Models\Session;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('SessionService::book', function () {

    beforeEach(function () {
        $this->coach = User::factory()->coach()->create();
        $this->athlete = User::factory()->athlete()->create();
        $this->session = Session::factory()
            ->for($this->coach, 'coach')
            ->published()
            ->create();
    });

    it('creates a booking and increments participants', function () {
        // Arrange — done in beforeEach

        // Act
        $booking = app(BookingService::class)->book($this->athlete, $this->session);

        // Assert
        expect($booking)->toBeInstanceOf(Booking::class);
        expect($this->session->fresh()->current_participants)->toBe(1);
    });
});
```

## Naming Conventions

### Test Files

- Mirror the source file path: `app/Services/BookingService.php` → `tests/Unit/Services/BookingServiceTest.php`
- Suffix with `Test.php` — Pest requires this for auto-discovery.
- Feature tests for controllers: `{Controller}Test.php` (e.g., `SessionControllerTest.php`)
- Feature tests for Livewire: match the component path (e.g., `Session/CreateTest.php`)

### Describe Blocks

- Group by public method: `describe('book', function () { ... })`
- For HTTP tests, group by route: `describe('POST /api/v1/bookings', function () { ... })`
- Nest sub-describes for categories: `describe('authorization', function () { ... })`

### Test Names

- Start with a present-tense verb: `it('creates a booking...')`, `it('rejects negative price...')`
- Be specific about the scenario: `it('denies coach from updating another coach session')`
- Never start with "should" — use direct verbs.
- Include the expected outcome: `it('throws SessionFullException when at capacity')`

## Factory Conventions

### Role Factories

The `UserFactory` must define state methods for every role:

```php
public function coach(): static
{
    return $this->state(['role' => UserRole::Coach->value]);
}

public function athlete(): static
{
    return $this->state(['role' => UserRole::Athlete->value]);
}

public function accountant(): static
{
    return $this->state(['role' => UserRole::Accountant->value]);
}

public function admin(): static
{
    return $this->state(['role' => UserRole::Admin->value]);
}
```

### Status Factories

Every model with a status enum must have factory states for each status value:

```php
// SessionFactory
public function draft(): static
{
    return $this->state(['status' => SessionStatus::Draft->value]);
}

public function published(): static
{
    return $this->state(['status' => SessionStatus::Published->value]);
}

public function confirmed(): static
{
    return $this->state([
        'status' => SessionStatus::Confirmed->value,
        'confirmed_at' => now(),
    ]);
}

public function cancelled(): static
{
    return $this->state([
        'status' => SessionStatus::Cancelled->value,
        'cancelled_at' => now(),
    ]);
}
```

### Factory Rules

- Use `->value` on backed enums — factory `definition()` stores the string value, not the enum object.
- Money columns: `fake()->numberBetween(500, 5000)` — never `randomFloat`.
- FK columns: use the related factory (`'coach_id' => User::factory()`) — never hardcode IDs.
- Use `->for($model, 'relationship')` to associate models in tests — clearer than passing the ID.
- Use `->count(n)` for bulk creation — never loop `create()` in a for-loop.
- Never use `new Model()` in tests — always factory or `Model::create()` inside a transaction.

## Authentication in Tests

- **Never skip authentication** — use `actingAs()` on every request test.
- Create users with factory role states: `User::factory()->coach()->create()`
- For API tests, specify the guard: `$this->actingAs($user, 'sanctum')`
- For web tests, omit the guard: `$this->actingAs($user)`
- Test all 4 roles on authorization-sensitive endpoints — never assume "if coach works, athlete will fail"

```php
// Correct — factory state method
$coach = User::factory()->coach()->create();

// Also acceptable — explicit attribute
$coach = User::factory()->create(['role' => UserRole::Coach->value]);

// Wrong — raw string
$coach = User::factory()->create(['role' => 'coach']);
```

## Mocking Rules

### Mock Only External Services

- **Stripe**: Mock `StripePaymentService` or the Stripe SDK — never make real API calls in tests.
- **Mail**: Use `Mail::fake()` or `Notification::fake()`.
- **External HTTP**: Use `Http::fake()` for any third-party API.
- **Queue**: Use `Queue::fake()` to assert jobs are dispatched without processing.
- **Events**: Use `Event::fake([SpecificEvent::class])` — fake specific events, not all.

### Never Mock

- **Eloquent models** — use factories with real database writes.
- **Service classes** (unless they call external APIs) — test with real implementations.
- **Form requests** — test through HTTP requests, not by calling `rules()` directly.
- **Policies** — test via `$user->can()` or HTTP requests, not by instantiating the policy.

```php
// Correct — mock only Stripe
$this->mock(StripePaymentService::class)
    ->shouldReceive('createPaymentIntent')
    ->andReturn(new FakePaymentIntent('pi_test_123'));

// Wrong — don't mock the service under test
$this->mock(BookingService::class)->shouldReceive('book'); // defeats the purpose
```

## Assertion Patterns

### Database Assertions

```php
$this->assertDatabaseHas('bookings', [
    'session_id' => $session->id,
    'athlete_id' => $athlete->id,
    'status' => BookingStatus::Confirmed->value,
]);

$this->assertDatabaseMissing('bookings', [
    'session_id' => $session->id,
]);

$this->assertDatabaseCount('bookings', 3);
```

### Expectation API (Pest)

Prefer Pest's `expect()` over PHPUnit assertions:

```php
expect($booking)
    ->toBeInstanceOf(Booking::class)
    ->status->toBe(BookingStatus::Confirmed)
    ->athlete_id->toBe($athlete->id);

expect($session->fresh()->current_participants)->toBe(5);
```

### HTTP Response Assertions

```php
// Status codes
->assertOk()              // 200
->assertCreated()         // 201
->assertNoContent()       // 204
->assertForbidden()       // 403
->assertNotFound()        // 404
->assertUnprocessable()   // 422

// JSON structure (API tests)
->assertJsonStructure(['data' => ['id', 'type', 'attributes']])
->assertJsonCount(3, 'data')
->assertJsonPath('data.attributes.status', 'published')
->assertJsonValidationErrors(['postal_code', 'date'])
```

### Exception Assertions

```php
// Chain on the test closure
it('throws when session is full', function () {
    // ...
})->throws(SessionFullException::class);

// With message check
it('throws with a specific message', function () {
    // ...
})->throws(SessionFullException::class, 'Session has reached maximum capacity.');
```

### Event/Notification Assertions

```php
Event::fake([BookingCreated::class]);
// ... perform action
Event::assertDispatched(BookingCreated::class, fn ($e) => $e->bookingId === $booking->id);
Event::assertNotDispatched(SessionCancelled::class);

Notification::fake();
// ... perform action
Notification::assertSentTo($athlete, BookingConfirmedNotification::class);
Notification::assertNotSentTo($coach, BookingConfirmedNotification::class);
```

## Test Categories Checklist

Every test file should cover applicable categories:

| Category | Description | Required for |
|----------|-------------|-------------|
| Happy path | Valid inputs → expected output | All tests |
| Authorization | All 4 roles tested | Policies, controllers, Livewire |
| Validation | Required fields, types, formats, cross-field | Form requests |
| Edge cases | Boundary values, empty collections, duplicates | Services |
| State transitions | Valid transitions succeed, invalid throw | Stateful models |
| Atomicity | Partial failure rolls back completely | Transactional services |
| Events/notifications | Correct events dispatched post-commit | Event-producing services |
| Money calculations | Cent-based arithmetic, no float drift | VAT, payout, pricing |
| Concurrency | `lockForUpdate` prevents race conditions | Booking service |

## Helper Functions

Define shared test helpers in `tests/Pest.php`:

```php
// tests/Pest.php
uses(RefreshDatabase::class)->in('Feature', 'Unit');

function validSessionData(array $overrides = []): array
{
    return array_merge([
        'activity_type'    => 'running',
        'level'            => 'beginner',
        'location'         => 'Parc du Cinquantenaire',
        'postal_code'      => '1000',
        'date'             => now()->addWeek()->format('Y-m-d'),
        'start_time'       => '10:00',
        'end_time'         => '11:00',
        'price_per_person' => 1250,
        'min_participants' => 3,
        'max_participants' => 15,
    ], $overrides);
}
```

- Define `validData()` helpers for each resource — one test overrides one field.
- Define them in `Pest.php` or at the bottom of the test file (if domain-specific).
- Use `RefreshDatabase` globally via `uses()->in()` — not per-file.

## CI Expectations

Tests must pass in CI with these constraints (see [ci-pipeline.instructions.md](ci-pipeline.instructions.md)):

| Constraint | Value |
|-----------|-------|
| Database | SQLite `:memory:` |
| Cache/Queue/Session | `array` driver |
| Mail | `array` driver |
| PHP versions | 8.2, 8.3, 8.4 |
| Parallel | `--parallel` flag |
| Coverage | ≥ 80% via PCOV |
| External services | None — all mocked |

### Parallel Safety

- Never rely on auto-increment IDs across tests — use `$model->id` from the factory.
- Never share state between `describe()` blocks — `beforeEach` resets per block.
- Never write to the filesystem without `Storage::fake()`.
- Never use `sleep()` or time-dependent logic without `Carbon::setTestNow()`.

## Translation Parity Test

Include a test that asserts all three locales have the same keys:

```php
it('has all translation keys in every locale', function () {
    $frKeys = collectTranslationKeys('fr');
    $enKeys = collectTranslationKeys('en');
    $nlKeys = collectTranslationKeys('nl');

    $missingEn = $frKeys->diff($enKeys);
    $missingNl = $frKeys->diff($nlKeys);

    expect($missingEn)->toBeEmpty("Missing EN keys: {$missingEn->implode(', ')}");
    expect($missingNl)->toBeEmpty("Missing NL keys: {$missingNl->implode(', ')}");
});
```

Place this in `tests/Feature/TranslationParityTest.php`. It runs in CI as a separate lint check.

## Forbidden

- Do NOT use PHPUnit class syntax (`class FooTest extends TestCase`) — use Pest closures.
- Do NOT skip authentication in request tests — always `actingAs()`.
- Do NOT use `new Model()` — always factory or `Model::create()`.
- Do NOT hardcode role strings — use `UserRole` enum.
- Do NOT mock the class under test.
- Do NOT use `assertTrue(true)` or empty test bodies — every test must assert something meaningful.
- Do NOT commit tests with `->skip()` or `->todo()` unless paired with a tracking issue.
- Do NOT seed the database in tests — use factories. Seeders are for production reference data only.
- Do NOT use `withoutExceptionHandling()` by default — let the framework handle exceptions normally unless debugging a specific test.
- Do NOT test private methods — test the public interface.
