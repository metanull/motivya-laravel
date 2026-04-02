---
description: "Generate a Laravel Policy class with role-based method implementations, admin before() bypass, ownership checks, and Pest authorization tests from a model name."
argument-hint: "Model name, e.g. 'Session' or 'Booking'"
agent: "agent"
tools: [read, edit, search, execute]
---

# Policy Scaffold

Generate a complete Policy class for a Motivya model with role-based authorization for all four roles.

## Before Writing

1. Read [auth-roles.instructions.md](../instructions/auth-roles.instructions.md) for the permission matrix, policy patterns, and `before()` rules.
2. Read [php.instructions.md](../instructions/php.instructions.md) for strict types and enum usage.
3. Search `app/Models/` for the target model — identify its relationships (especially `coach_id`, `athlete_id`, or other user FK columns) to determine ownership rules.
4. Search `app/Enums/` for `UserRole` — use the enum, never raw strings.
5. Search `app/Policies/` to check if a policy already exists. If it does, update rather than overwrite.

## Input

The user provides a model name (e.g., `Session`, `Booking`, `Invoice`, `User`).

If the model is in the permission matrix below, use those permissions. Otherwise, infer from the model's relationships and ask at most 1 clarifying question.

## Permission Matrix Reference

| Action | Coach | Athlete | Accountant | Admin |
|--------|-------|---------|------------|-------|
| **Session** | | | | |
| viewAny | own | all published | — | all |
| view | own + published | published/booked | — | any |
| create | yes | — | — | yes |
| update | own, draft/published only | — | — | any |
| delete | own, draft only | — | — | any |
| **Booking** | | | | |
| viewAny | own sessions' bookings | own | — | all |
| view | own session's booking | own | — | any |
| create | — | yes | — | — |
| delete (cancel) | — | own | — | any |
| **Invoice** | | | | |
| viewAny | own | own | all | all |
| view | own | own | any | any |
| create | — | — | — | — |
| **User** | | | | |
| viewAny | — | — | — | yes |
| view | self | self | — | any |
| update | self | self | — | any |
| delete | — | — | — | yes |

"own" = resource belongs to the user (via `coach_id`, `athlete_id`, or `user_id`).

## Generation Rules

### 1. Policy Class (`app/Policies/{Model}Policy.php`)

```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\{Model};

final class {Model}Policy
{
    /**
     * Admin bypass — grants all abilities.
     * Accountant is NEVER included here — accountant access is explicit per method.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->role === UserRole::Admin) {
            return true;
        }

        return null;
    }

    // ... policy methods
}
```

Rules:

**Class structure:**
- `declare(strict_types=1)` always
- `final class` — policies are not extended
- `before()` method grants admin bypass, returns `null` for all other roles
- Accountant NEVER gets blanket bypass — always explicit per-method read-only access

**Method signatures:**
- First argument: `User $user`
- Second argument: the model instance (for instance methods) or omitted (for `viewAny`, `create`)
- Return type: `bool` for simple allow/deny
- Use `Illuminate\Auth\Access\Response` only when a specific denial message is needed

**Role checks:**
- Always use `$user->role === UserRole::Coach` — never raw strings
- Use `match` or `if/return` — never nested ternaries
- Ownership checks compare `$user->id` against the model's FK column

**Method pattern:**

```php
public function viewAny(User $user): bool
{
    return match ($user->role) {
        UserRole::Coach,
        UserRole::Athlete => true,
        UserRole::Accountant => false, // explicit deny unless this resource has financial data
        default => false,
    };
}

public function view(User $user, Session $session): bool
{
    return match ($user->role) {
        UserRole::Coach => $user->id === $session->coach_id,
        UserRole::Athlete => $session->status === SessionStatus::Published
            || $session->bookings()->where('athlete_id', $user->id)->exists(),
        UserRole::Accountant => false,
        default => false,
    };
}

public function update(User $user, Session $session): bool
{
    if ($user->role !== UserRole::Coach) {
        return false;
    }

    return $user->id === $session->coach_id
        && in_array($session->status, [SessionStatus::Draft, SessionStatus::Published], true);
}
```

**Ownership detection:**
- If model has `coach_id` → coach owns via `$user->id === $model->coach_id`
- If model has `athlete_id` → athlete owns via `$user->id === $model->athlete_id`
- If model has `user_id` → generic ownership via `$user->id === $model->user_id`
- If model is `User` → self-access via `$user->id === $model->id`
- For indirect ownership (e.g., coach viewing a booking on their session), traverse the relationship

**Accountant access:**
- Read-only on financial resources (invoices, payouts): `viewAny` and `view` return `true`
- All write methods (`create`, `update`, `delete`) return `false`
- Non-financial resources: all methods return `false`

### 2. Policy Registration

If not using auto-discovery, register in `AuthServiceProvider`:

```php
protected $policies = [
    Session::class => SessionPolicy::class,
];
```

Laravel 12 with auto-discovery typically doesn't need this — but mention it in a comment.

### 3. Pest Tests (`tests/Feature/Policies/{Model}PolicyTest.php`)

Generate 5 test categories for every policy method:

```php
<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Session;

describe('SessionPolicy', function () {

    describe('viewAny', function () {
        it('allows coach to view sessions', function () {
            $coach = User::factory()->coach()->create();
            expect($coach->can('viewAny', Session::class))->toBeTrue();
        });

        it('allows athlete to view sessions', function () {
            $athlete = User::factory()->athlete()->create();
            expect($athlete->can('viewAny', Session::class))->toBeTrue();
        });

        it('denies accountant from viewing sessions', function () {
            $accountant = User::factory()->accountant()->create();
            expect($accountant->can('viewAny', Session::class))->toBeFalse();
        });

        it('allows admin to view sessions', function () {
            $admin = User::factory()->admin()->create();
            expect($admin->can('viewAny', Session::class))->toBeTrue();
        });
    });

    describe('update', function () {
        it('allows coach to update own draft session', function () {
            $coach = User::factory()->coach()->create();
            $session = Session::factory()->for($coach, 'coach')->draft()->create();
            expect($coach->can('update', $session))->toBeTrue();
        });

        it('denies coach from updating another coach session', function () {
            $coach = User::factory()->coach()->create();
            $otherSession = Session::factory()->create();
            expect($coach->can('update', $otherSession))->toBeFalse();
        });

        it('denies coach from updating confirmed session', function () {
            $coach = User::factory()->coach()->create();
            $session = Session::factory()->for($coach, 'coach')->confirmed()->create();
            expect($coach->can('update', $session))->toBeFalse();
        });

        it('denies athlete from updating any session', function () {
            $athlete = User::factory()->athlete()->create();
            $session = Session::factory()->create();
            expect($athlete->can('update', $session))->toBeFalse();
        });
    });
});
```

**Test categories per method:**

| Category | What to test |
|----------|-------------|
| Authorized | Correct role succeeds |
| Forbidden role | Wrong role gets denied |
| Ownership | Owner succeeds, non-owner denied |
| State guards | Status-dependent methods respect current state |
| Admin bypass | Admin succeeds on everything via `before()` |

**Test conventions:**
- Use `$user->can('ability', $model)` for policy assertions — cleaner than HTTP tests for unit policy testing
- Use factory state methods (`.coach()`, `.athlete()`, `.draft()`, `.confirmed()`) — never raw `create(['role' => 'coach'])`
- Group tests with `describe()` per policy method
- One assertion per test

## Output Order

1. **Policy class** — `app/Policies/{Model}Policy.php`
2. **Pest tests** — `tests/Feature/Policies/{Model}PolicyTest.php`
3. **Registration note** — whether auto-discovery handles it or manual registration is needed

## Validation

After generating, verify:
- `declare(strict_types=1)` on all files
- `final class` on policy
- `before()` grants admin bypass only — never accountant
- Every method uses `UserRole` enum — no raw strings
- Ownership checks use the correct FK column for the model
- Accountant has explicit read-only or deny on every method — never falls through
- Every `match` has a `default => false` arm
- Tests cover all 4 roles for every policy method
- Tests use factory state methods, not raw attribute arrays
