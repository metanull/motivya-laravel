---
description: "Use when implementing role-based access control, authorization policies, gates, middleware guards, role checks, user authentication, Google OAuth, MFA, API token management, or permission logic. Covers the four-role model (coach, athlete, accountant, admin), policy patterns, and middleware conventions."
applyTo: "app/Policies/*,app/Http/Middleware/*,app/Models/User*,app/Http/Controllers/Auth/*,app/Livewire/Auth/*"
---
# Authorization & Role-Based Access Control

## Four-Role Model

Motivya uses exactly four roles. Never add roles without a documented decision in `doc/Decisions.md`.

| Role | Slug | Description |
|------|------|-------------|
| Coach | `coach` | Creates sessions, receives payouts, manages profile |
| Athlete | `athlete` | Discovers/books sessions, makes payments |
| Accountant | `accountant` | Read-only financial oversight, export data |
| Admin | `admin` | Platform management, KYC validation, disputes |

### Storage

- Store the role as a string enum column on the `users` table: `role` with values `coach`, `athlete`, `accountant`, `admin`
- Default role for new registrations: `athlete`
- Coach accounts start as `pending_coach` until admin KYC approval promotes them to `coach`
- A user has **one role** — no many-to-many role tables, no Spatie packages, no role hierarchies

### Role Enum

Define roles as a backed PHP enum:

```php
enum UserRole: string
{
    case Coach      = 'coach';
    case Athlete    = 'athlete';
    case Accountant = 'accountant';
    case Admin      = 'admin';
}
```

Cast the `role` column to this enum in the User model. Reference roles via the enum — never hardcode strings in policies, middleware, or controllers.

## Authentication

### Methods

| Method | Implementation |
|--------|---------------|
| Email/password | Laravel Breeze or Fortify — standard flow |
| Google OAuth | Laravel Socialite with `google` driver |
| MFA | TOTP-based, optional per user, enforced for `admin` and `accountant` roles |
| API tokens | Laravel Sanctum — scoped tokens per role |

### Rules

- All auth routes must be rate-limited (`throttle:login` for authentication endpoints)
- Password reset tokens expire after 60 minutes
- OAuth users who also have a password can use either method
- On first OAuth login, create user with `athlete` role if no account exists for that email
- Never store OAuth tokens long-term — use them only during the auth flow

## Policies

Use Laravel Policies for **all** authorization. Never use inline `if ($user->role === ...)` checks in controllers, Livewire components, or Blade views.

### Naming Convention

| Resource | Policy Class | File |
|----------|-------------|------|
| Session | `SessionPolicy` | `app/Policies/SessionPolicy.php` |
| Booking | `BookingPolicy` | `app/Policies/BookingPolicy.php` |
| Invoice | `InvoicePolicy` | `app/Policies/InvoicePolicy.php` |
| User | `UserPolicy` | `app/Policies/UserPolicy.php` |

### Policy Method Pattern

Every policy method must:
1. Accept the `User` model as the first argument
2. Use the `UserRole` enum for role checks
3. Return `bool` — never `Response` for simple allow/deny
4. Use `Response::deny('message')` only when a specific error message is needed

```php
public function update(User $user, Session $session): bool
{
    // Coach can only edit their own sessions
    if ($user->role === UserRole::Coach) {
        return $user->id === $session->coach_id;
    }

    // Admin can edit any session
    return $user->role === UserRole::Admin;
}
```

### Permission Matrix

This is the baseline. Extend per-resource as needed, but never grant broader access than shown.

| Action | Coach | Athlete | Accountant | Admin |
|--------|-------|---------|------------|-------|
| Create session | own | — | — | any |
| Edit session | own | — | — | any |
| Cancel session | own | — | — | any |
| Book session | — | yes | — | — |
| Cancel booking | — | own | — | any |
| View invoices | own | own | all (read) | all |
| Export financials | — | — | yes | yes |
| Manage users | — | — | — | yes |
| Approve coaches | — | — | — | yes |
| View dashboard | own stats | own bookings | financial | platform |

### before() Method

Register a `before()` in policies to give `admin` bypass where appropriate:

```php
public function before(User $user, string $ability): ?bool
{
    if ($user->role === UserRole::Admin) {
        return true; // Admin can do anything
    }

    return null; // Fall through to specific checks
}
```

Use this judiciously — the accountant role should **never** get blanket bypass. Accountant access is read-only and must be explicitly granted per method.

## Middleware

### Role Middleware

Create a single `EnsureUserHasRole` middleware — not separate middleware per role:

```php
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $allowedRoles = array_map(
            fn (string $role) => UserRole::from($role),
            $roles
        );

        if (! in_array($request->user()->role, $allowedRoles, true)) {
            abort(403);
        }

        return $next($request);
    }
}
```

Register as `role` in the HTTP kernel or bootstrap:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'role' => EnsureUserHasRole::class,
    ]);
})
```

### Usage in Routes

```php
// Single role
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    // Admin-only routes
});

// Multiple roles
Route::middleware(['auth', 'role:admin,accountant'])->group(function () {
    // Admin or accountant
});

// Coach-only session management
Route::middleware(['auth', 'role:coach'])->group(function () {
    Route::resource('sessions', CoachSessionController::class);
});
```

### MFA Middleware

Create `EnsureMfaVerified` middleware. Apply to:
- All `admin` routes (mandatory)
- All `accountant` routes (mandatory)
- Optional for `coach` and `athlete` (user can enable in settings)

```php
Route::middleware(['auth', 'role:admin', 'mfa'])->prefix('admin')->group(/* ... */);
```

## Gates

Use Gates sparingly — only for cross-cutting abilities not tied to a specific model:

```php
Gate::define('export-financials', function (User $user): bool {
    return in_array($user->role, [UserRole::Accountant, UserRole::Admin], true);
});

Gate::define('access-admin-panel', function (User $user): bool {
    return $user->role === UserRole::Admin;
});
```

Prefer Policies over Gates for model-specific authorization.

## Route Organization

| File | Middleware | Roles |
|------|-----------|-------|
| `routes/web.php` | `auth` | Public + athlete routes |
| `routes/coach.php` | `auth`, `role:coach` | Coach dashboard, session CRUD |
| `routes/admin.php` | `auth`, `role:admin`, `mfa` | Admin panel |
| `routes/api.php` | `auth:sanctum` | API endpoints with token scoping |

Register additional route files in `bootstrap/app.php`:

```php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    api: __DIR__.'/../routes/api.php',
    commands: __DIR__.'/../routes/console.php',
    then: function () {
        Route::middleware(['web', 'auth', 'role:coach'])
            ->prefix('coach')
            ->name('coach.')
            ->group(base_path('routes/coach.php'));

        Route::middleware(['web', 'auth', 'role:admin', 'mfa'])
            ->prefix('admin')
            ->name('admin.')
            ->group(base_path('routes/admin.php'));
    },
)
```

## Sanctum API Tokens

Scope tokens by role. Never issue a token with abilities beyond the user's role:

```php
$token = $user->createToken('api', abilities: match ($user->role) {
    UserRole::Coach      => ['sessions:read', 'sessions:write', 'bookings:read', 'payouts:read'],
    UserRole::Athlete    => ['sessions:read', 'bookings:read', 'bookings:write'],
    UserRole::Accountant => ['invoices:read', 'financials:export'],
    UserRole::Admin      => ['*'],
});
```

## Testing Authorization

Every policy and middleware must have Pest tests covering:

1. **Authorized access** — correct role can perform the action
2. **Forbidden access** — wrong role gets 403
3. **Unauthenticated access** — no user gets 302 redirect to login
4. **Ownership checks** — coach can only access own resources, not another coach's
5. **MFA enforcement** — admin/accountant without MFA verified gets redirected

```php
// Example: test that athlete cannot create a session
it('forbids athlete from creating a session', function () {
    $athlete = User::factory()->athlete()->create();

    $this->actingAs($athlete)
        ->post(route('coach.sessions.store'), [...])
        ->assertForbidden();
});

// Example: test that coach can only edit own session
it('forbids coach from editing another coach session', function () {
    $coach = User::factory()->coach()->create();
    $otherSession = Session::factory()->create(); // different coach

    $this->actingAs($coach)
        ->put(route('coach.sessions.update', $otherSession), [...])
        ->assertForbidden();
});
```

### Factory States

Define role-specific factory states on `UserFactory`:

```php
public function coach(): static
{
    return $this->state(fn () => ['role' => UserRole::Coach]);
}

public function athlete(): static
{
    return $this->state(fn () => ['role' => UserRole::Athlete]);
}

public function accountant(): static
{
    return $this->state(fn () => ['role' => UserRole::Accountant]);
}

public function admin(): static
{
    return $this->state(fn () => ['role' => UserRole::Admin]);
}
```
