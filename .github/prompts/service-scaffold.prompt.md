---
description: "Generate a Laravel Service class with constructor injection, DB transactions, event dispatching, domain exceptions, and Pest unit tests from a feature description or domain name."
argument-hint: "Service name or feature, e.g. 'BookingService' or 'session cancellation'"
agent: "agent"
tools: [read, edit, search, execute]
---

# Service Scaffold

Generate a complete Service class for the Motivya project with proper dependency injection, DB transactions, event dispatching, and unit tests.

## Before Writing

1. Read [php.instructions.md](../instructions/php.instructions.md) for strict types, `final class`, constructor promotion, and money-in-cents rules.
2. Read [notification-system.instructions.md](../instructions/notification-system.instructions.md) for the event catalog — services dispatch events, never send notifications directly.
3. Read [session-booking.instructions.md](../instructions/session-booking.instructions.md) for atomic transaction patterns and the `lockForUpdate()` convention.
4. Search `app/Models/` for related models — identify relationships and enum columns.
5. Search `app/Enums/` for status enums used by the domain.
6. Search `app/Services/` to check if the service already exists. If it does, update rather than overwrite.
7. Search `app/Events/` for existing events this service should dispatch.

## Input

The user provides one of:
- A service name: `BookingService`, `SessionService`, `PayoutService`
- A feature description: "session cancellation with refund logic"
- A domain: "booking" or "invoicing"

If ambiguous, ask at most 1 question about which methods to include.

## Generation Rules

### 1. Service Class (`app/Services/{Name}Service.php`)

```php
<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;

final class BookingService
{
    public function __construct(
        private readonly StripePaymentService $stripeService,
    ) {}

    public function book(User $athlete, Session $session): Booking
    {
        return DB::transaction(function () use ($athlete, $session): Booking {
            // ...
        });
    }
}
```

**Class structure rules:**
- `declare(strict_types=1)` always
- `final class` — services are not extended
- No base class — services don't extend anything
- Constructor promotion with `private readonly` for all dependencies
- Inject other services and models — never inject the container, `app()`, or facades in constructor

**Method rules:**
- One public method per action: `book()`, `cancel()`, `confirm()`, `complete()`
- Accept typed model parameters (`User $athlete`, `Session $session`) — never raw IDs
- Return the created/updated model or `void` — never return booleans for success/failure
- Throw domain exceptions on failure — never return `null` or `false`
- PHPDoc on every public method explaining the business rule

**DB transaction rules:**
- Wrap any method that performs multiple writes in `DB::transaction()`
- Use `lockForUpdate()` when concurrent access is possible (booking, participant counts)
- Keep the transaction scope minimal — only DB operations, not external API calls
- Stripe API calls should happen BEFORE the transaction when possible (create PaymentIntent → then record booking in transaction)
- If Stripe is called inside a transaction, handle rollback explicitly

**Event dispatching rules:**
- Dispatch events AFTER the DB transaction commits — use `afterCommit()` or dispatch outside the transaction closure
- Match events to the event catalog in `notification-system.instructions.md`
- Events carry IDs and scalars, not full models:

```php
// Correct — dispatch after transaction
$booking = DB::transaction(function () use ($athlete, $session): Booking {
    // ... create booking, increment participants
    return $booking;
});

event(new BookingCreated(bookingId: $booking->id));

// Wrong — dispatching inside transaction risks events firing on rollback
```

**Money rules:**
- All amounts in integer cents — no floats anywhere
- Rounding: `(int) round($value)` once at the end of a calculation chain
- Store Stripe amounts directly (Stripe also uses cents)
- Never divide by 100 in service logic — only at the presentation layer

**Status transition rules:**
- Use backed enums for all status checks and assignments
- Validate the current status before transitioning — throw if invalid:

```php
public function cancel(User $user, Session $session): Session
{
    if (! in_array($session->status, [SessionStatus::Draft, SessionStatus::Published], true)) {
        throw new InvalidSessionStateException(
            "Cannot cancel session in {$session->status->value} state"
        );
    }

    return DB::transaction(function () use ($session): Session {
        $session->update([
            'status' => SessionStatus::Cancelled,
            'cancelled_at' => now(),
        ]);

        // Refund all confirmed bookings
        $session->bookings()
            ->where('status', BookingStatus::Confirmed)
            ->each(function (Booking $booking): void {
                $this->cancelBooking($booking);
            });

        return $session->fresh();
    });
}
```

### 2. Domain Exceptions (`app/Exceptions/{Domain}/`)

Create specific exception classes for each failure mode:

```php
<?php

declare(strict_types=1);

namespace App\Exceptions\Booking;

use RuntimeException;

final class SessionFullException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Session has reached maximum capacity.');
    }
}
```

Rules:
- One exception per failure type — `SessionFullException`, `AlreadyBookedException`, `InvalidSessionStateException`
- Extend `RuntimeException` — not generic `Exception`
- Group under `app/Exceptions/{Domain}/` subdirectory
- Constructor sets a default message — caller can override
- No HTTP-specific logic (status codes) in exceptions — let the exception handler map them

### 3. Pest Unit Tests (`tests/Unit/Services/{Name}ServiceTest.php`)

```php
<?php

declare(strict_types=1);

use App\Enums\BookingStatus;
use App\Enums\SessionStatus;
use App\Events\BookingCreated;
use App\Exceptions\Booking\SessionFullException;
use App\Models\Booking;
use App\Models\Session;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->service = app(BookingService::class);
});

describe('book', function () {
    it('creates a booking and increments participant count', function () {
        $session = Session::factory()->published()->create([
            'max_participants' => 10,
            'current_participants' => 0,
        ]);
        $athlete = User::factory()->athlete()->create();

        $booking = $this->service->book($athlete, $session);

        expect($booking)
            ->toBeInstanceOf(Booking::class)
            ->status->toBe(BookingStatus::Confirmed);

        expect($session->fresh()->current_participants)->toBe(1);
    });

    it('throws SessionFullException when session is at capacity', function () {
        $session = Session::factory()->published()->create([
            'max_participants' => 1,
            'current_participants' => 1,
        ]);
        $athlete = User::factory()->athlete()->create();

        $this->service->book($athlete, $session);
    })->throws(SessionFullException::class);

    it('dispatches BookingCreated event', function () {
        Event::fake([BookingCreated::class]);

        $session = Session::factory()->published()->create();
        $athlete = User::factory()->athlete()->create();

        $this->service->book($athlete, $session);

        Event::assertDispatched(BookingCreated::class);
    });

    it('rolls back on failure', function () {
        // ... test that DB state is unchanged when exception occurs
    });
});
```

**Test categories per method:**

| Category | What to test |
|----------|-------------|
| Happy path | Method succeeds, correct model returned, DB state updated |
| Domain exceptions | Each exception type thrown for the right condition |
| Atomicity | DB rolls back entirely on failure — no partial writes |
| Events | Correct event dispatched with expected payload |
| Status transitions | Valid transitions succeed, invalid transitions throw |
| Money calculations | Amounts in cents, rounding correct (if service computes money) |
| Concurrency | `lockForUpdate` prevents overbooking (where applicable) |

**Test conventions:**
- Use `beforeEach` to resolve the service from the container
- Use `Event::fake()` to assert event dispatching without triggering listeners
- Use factory state methods (`.published()`, `.confirmed()`, `.coach()`)
- `describe()` per public method
- One assertion per test where practical
- Test exceptions with `->throws(ExceptionClass::class)`

## Output Order

1. **Domain exceptions** — `app/Exceptions/{Domain}/` (one file per exception type)
2. **Service class** — `app/Services/{Name}Service.php`
3. **Pest tests** — `tests/Unit/Services/{Name}ServiceTest.php`

## Validation

After generating, verify:
- `declare(strict_types=1)` on all files
- `final class` on service, exceptions, and test file conventions
- Constructor uses `private readonly` promotion — no property declarations
- Service does not extend a base class
- DB transactions wrap multi-write operations
- `lockForUpdate()` used where concurrent access is possible
- Events dispatched AFTER transaction, not inside
- Domain exceptions extend `RuntimeException`, not `Exception`
- No notification sending — only event dispatching
- Money handled as integer cents throughout
- All status checks use backed enums, not strings
- Tests cover happy path, exceptions, atomicity, events, and state transitions
