---
description: "Generate a comprehensive Pest test file for an existing service class, covering happy paths, edge cases, error scenarios, role-based access, money rounding, and state transitions"
agent: "agent"
argument-hint: "Service class name or feature, e.g. 'BookingService' or 'session cancellation logic'"
tools: [search, read, editFile, createFile, runInTerminal]
---

# Generate Pest Test Suite

Create a thorough Pest test file for the service class or feature described by the user. The test file must cover every public method with happy paths, edge cases, and failure scenarios.

## Project Context

- **Pest** (not PHPUnit syntax), **Laravel 12**, PHP 8.2+, SQLite `:memory:` for tests.
- Follow [php.instructions.md](../instructions/php.instructions.md) for strict types, enums, and naming.
- Four roles: `coach`, `athlete`, `accountant`, `admin` — via `UserRole` backed enum in `app/Enums/`.
- Money is always **integer cents** (EUR). Test with 0, 1, boundary, and large values.
- Business logic lives in `app/Services/` — tests target those methods directly.

## Before Generating

1. **Read the source file** — identify every public method, its parameters, return types, exceptions, and side effects.
2. **Read the model(s)** involved — identify relationships, casts, states, and factory definitions.
3. **Read existing tests** in the same domain — match naming patterns and helper usage.
4. **Identify the instruction files** that apply (session-booking, stripe-connect, vat-calculations, peppol-invoicing) and follow their testing rules.

If critical information is missing (e.g. the service doesn't exist yet), ask the user before generating.

## Test Categories

Every test suite must include these categories. Skip a category only if it genuinely doesn't apply to the service under test.

### 1. Happy Path

- Each public method with valid inputs and expected output.
- Assert return values, database state changes, and dispatched events.

```php
it('books an athlete into a session', function () {
    $coach = User::factory()->create(['role' => UserRole::Coach]);
    $session = Session::factory()->for($coach, 'coach')->published()->create();
    $athlete = User::factory()->create(['role' => UserRole::Athlete]);

    $booking = app(BookingService::class)->book($athlete, $session);

    expect($booking)
        ->toBeInstanceOf(Booking::class)
        ->athlete_id->toBe($athlete->id)
        ->status->toBe(BookingStatus::Pending);

    $this->assertDatabaseHas('bookings', [
        'session_id' => $session->id,
        'athlete_id' => $athlete->id,
    ]);
});
```

### 2. Authorization & Roles

- Test each role that should succeed and each that should be denied.
- Use `actingAs()` with the `UserRole` enum — never skip auth.

```php
it('denies booking for a coach role', function () {
    $coach = User::factory()->create(['role' => UserRole::Coach]);
    $session = Session::factory()->published()->create();

    app(BookingService::class)->book($coach, $session);
})->throws(UnauthorizedException::class);
```

### 3. Validation & Invalid Input

- Missing required fields, wrong types, out-of-range values.
- For money: negative cents, zero, amounts exceeding reasonable bounds.

### 4. Edge Cases & Boundaries

- **Money rounding**: amounts that trigger half-cent rounding.
- **Capacity limits**: exactly at `min_participants`, exactly at `max_participants`, one over.
- **Time boundaries**: exactly 48h before start (cancellation cutoff), 1 second before/after.
- **Duplicate operations**: booking the same session twice, double cancellation.
- **Empty collections**: no bookings, no sessions, zero revenue.

```php
it('rejects booking when session is at capacity', function () {
    $session = Session::factory()->create(['max_participants' => 1]);
    Booking::factory()->for($session)->confirmed()->create();
    $athlete = User::factory()->create(['role' => UserRole::Athlete]);

    app(BookingService::class)->book($athlete, $session);
})->throws(SessionFullException::class);
```

### 5. State Transitions

- For stateful entities (sessions, bookings), test every valid transition and assert invalid ones throw.
- Use the enum values from `SessionStatus` and `BookingStatus`.

```php
it('transitions session from published to confirmed when threshold met', function () {
    $session = Session::factory()->published()->create(['min_participants' => 2]);
    Booking::factory()->for($session)->count(2)->create();

    app(SessionService::class)->checkThreshold($session);

    expect($session->fresh()->status)->toBe(SessionStatus::Confirmed);
});

it('rejects confirming an already cancelled session', function () {
    $session = Session::factory()->cancelled()->create();

    app(SessionService::class)->confirm($session);
})->throws(InvalidStateTransitionException::class);
```

### 6. Database Integrity

- Atomic operations: assert no partial writes on failure (booking + payment both committed or neither).
- Unique constraints: duplicate `(session_id, athlete_id)` pair.
- Cascade behavior: what happens when a parent is deleted.

```php
it('rolls back booking if payment fails', function () {
    $session = Session::factory()->published()->create();
    $athlete = User::factory()->create(['role' => UserRole::Athlete]);

    // Mock payment to fail
    $this->mock(StripePaymentService::class)
        ->shouldReceive('charge')
        ->andThrow(new PaymentFailedException());

    try {
        app(BookingService::class)->book($athlete, $session);
    } catch (PaymentFailedException) {
        // expected
    }

    $this->assertDatabaseMissing('bookings', [
        'session_id' => $session->id,
        'athlete_id' => $athlete->id,
    ]);
});
```

### 7. Events & Side Effects

- Assert events are dispatched with correct payload.
- Assert notifications are sent to the correct recipients.
- Use `Event::fake()`, `Notification::fake()`, `Queue::fake()`.

```php
it('dispatches BookingCreated event', function () {
    Event::fake([BookingCreated::class]);

    $booking = app(BookingService::class)->book($athlete, $session);

    Event::assertDispatched(BookingCreated::class, fn ($e) =>
        $e->booking->id === $booking->id
    );
});
```

### 8. Money & VAT Calculations (when applicable)

- VAT-subject vs non-subject coach — same margin assertion.
- All three commission tiers with worked examples matching [vat-calculations.instructions.md](../instructions/vat-calculations.instructions.md).
- Rounding: verify cents-only arithmetic, no float drift.
- Auto-best-plan: assert the cheapest tier is always selected.

```php
it('preserves platform margin for non-subject coach on Freemium', function () {
    $coach = User::factory()->create([
        'role' => UserRole::Coach,
        'is_vat_subject' => false,
        'subscription_plan' => 'freemium',
    ]);

    $result = app(VatCalculationService::class)->calculatePayout(
        coach: $coach,
        revenueInCents: 30000, // €300
    );

    expect($result->marginInCents)->toBe(7438); // same as subject coach margin
});
```

## File Conventions

- **Location**: `tests/Feature/<Domain>/<Name>Test.php` for integration tests, `tests/Unit/<Domain>/<Name>Test.php` for pure logic.
- **File header**: `declare(strict_types=1)` on every file.
- **Describe blocks**: group by method name → `describe('book()', function () { ... })`.
- **Naming**: `it('does something specific')` — start with a verb in present tense.
- **Setup**: use `beforeEach()` for common fixtures. Use factories, never manual `new Model()`.
- **Traits**: include `use RefreshDatabase;` (via Pest `uses()`) for all database tests.
- **No mocks by default**: prefer real service calls and real database. Mock only external services (Stripe, mail).

```php
<?php

declare(strict_types=1);

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('book()', function () {
    // tests grouped here
});
```

## Output

1. Create the test file at the correct path.
2. Run `php artisan test --filter=<TestFileName>` to verify all tests are syntactically valid.
3. List every test name with a one-line description of what it covers.
4. Flag any **untestable paths** (e.g. private methods, third-party API calls that need mocking) and suggest how to address them.
