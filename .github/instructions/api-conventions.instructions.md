---
description: "Use when building API endpoints, configuring routes/api.php, implementing pagination, rate limiting, error responses, CORS, versioning, or JSON response formatting. Covers Laravel Sanctum token auth, standard envelope, and Belgian locale defaults."
applyTo: "routes/api.php,routes/api/**,app/Http/Controllers/Api/**,app/Http/Resources/**"
---
# API Conventions

## Route Structure

### File Layout

Separate API routes by version and domain:

```
routes/
├── api.php              # Registers version groups only
├── api/
│   └── v1.php           # All v1 route definitions
```

In `api.php`:

```php
Route::prefix('v1')->group(base_path('routes/api/v1.php'));
```

In `v1.php`, group by domain:

```php
// Public (no auth)
Route::get('sessions', [SessionController::class, 'index']);
Route::get('sessions/{session}', [SessionController::class, 'show']);

// Authenticated
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('bookings', BookingController::class)->except(['index']);
    Route::get('me/bookings', [BookingController::class, 'index']);

    // Coach-only
    Route::middleware('role:coach')->group(function () {
        Route::apiResource('sessions', SessionController::class)->except(['index', 'show']);
    });

    // Admin-only
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('users', UserController::class);
    });
});
```

### URL Conventions

| Rule | Example |
|------|---------|
| Version prefix | `/api/v1/` |
| Plural nouns for resources | `/sessions`, `/bookings` |
| Kebab-case for multi-word | `/coach-profiles` |
| Nested only for true parent-child | `/sessions/{session}/bookings` |
| Actions as verbs on resource | `POST /sessions/{session}/cancel` |
| No verbs in CRUD routes | `/sessions`, not `/get-sessions` |
| Current user's resources | `/me/bookings`, `/me/sessions` — never `/users/{id}/bookings` in the API |

### Route Model Binding

Use implicit binding with `{model}` parameters. For soft-deleted models, add `->withTrashed()` explicitly — never silently include trashed records.

## Response Format

### Standard Envelope

All API responses follow a consistent structure:

**Single resource:**

```json
{
  "data": {
    "id": 1,
    "type": "sessions",
    "title": "Yoga matinal",
    "price": 1200,
    "created_at": "2026-03-15T08:00:00+01:00"
  }
}
```

**Collection (paginated):**

```json
{
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 20,
    "total": 93
  },
  "links": {
    "first": "/api/v1/sessions?page=1",
    "last": "/api/v1/sessions?page=5",
    "prev": null,
    "next": "/api/v1/sessions?page=2"
  }
}
```

**Error:**

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "price": ["The price field must be an integer."]
  }
}
```

### Response Rules

| Rule | Detail |
|------|--------|
| Money fields | Raw integer cents — never format to decimals. Add `// cents` comment in Resource |
| Dates | ISO 8601 with timezone: `toIso8601String()` |
| Nulls | Include nullable fields with `null` — don't omit them |
| Enums | Return the string `->value`, not the enum instance |
| Relationships | `whenLoaded()` — never eager-load inside the Resource class |
| Empty collections | Return `"data": []`, not `null` or `404` |
| Created | `201` with `Location` header pointing to the new resource |
| Deleted | `204 No Content` — empty body |

### HTTP Status Codes

| Status | When |
|--------|------|
| `200` | Successful GET, PUT, PATCH |
| `201` | Successful POST that creates a resource |
| `204` | Successful DELETE |
| `401` | Missing or invalid Sanctum token |
| `403` | Valid token but insufficient role/permission |
| `404` | Resource not found or model binding failure |
| `409` | Conflict (e.g., session already fully booked) |
| `422` | Validation failure — return `errors` object |
| `429` | Rate limit exceeded |
| `500` | Unhandled server error — never leak stack traces in production |

## Pagination

### Defaults

```php
// In a base controller or trait:
protected int $defaultPerPage = 20;
protected int $maxPerPage = 100;
```

Controllers must respect the `per_page` query parameter but cap it:

```php
$perPage = min(
    (int) $request->input('per_page', $this->defaultPerPage),
    $this->maxPerPage,
);

return SessionResource::collection(
    Session::query()->paginate($perPage)
);
```

### Cursor Pagination

For high-volume feeds (e.g., notification history, activity logs), use `cursorPaginate()` instead of offset-based pagination. Standard resource listings use offset-based.

### Sorting

Accept `sort` query parameter with field names, prefix `-` for descending:

```
GET /api/v1/sessions?sort=-start_time,price
```

Only allow sorting on indexed columns. Reject unknown sort fields with `422`.

## Rate Limiting

### Configuration

Define rate limiters in `App\Providers\AppServiceProvider::boot()`:

```php
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});

RateLimiter::for('api-auth', function (Request $request) {
    return Limit::perMinute(120)->by($request->user()->id);
});

RateLimiter::for('api-public', function (Request $request) {
    return Limit::perMinute(30)->by($request->ip());
});

RateLimiter::for('webhooks', function (Request $request) {
    return Limit::perMinute(300)->by($request->ip());
});
```

### Assignment

| Route Group | Limiter | Rationale |
|-------------|---------|-----------|
| Public endpoints (no auth) | `api-public` (30/min) | Prevent scraping |
| Authenticated endpoints | `api-auth` (120/min) | Higher trust |
| Stripe webhooks | `webhooks` (300/min) | Stripe can burst events |
| Login / register | `api` (60/min) + `throttle:5,1` | Brute-force protection |

### Headers

Laravel includes `X-RateLimit-Limit`, `X-RateLimit-Remaining`, and `Retry-After` automatically. Do not suppress these headers.

## CORS

### Configuration

In `config/cors.php`:

```php
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000')),
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['Content-Type', 'Authorization', 'Accept', 'Accept-Language', 'X-Requested-With'],
    'exposed_headers' => ['X-RateLimit-Limit', 'X-RateLimit-Remaining', 'Retry-After'],
    'max_age' => 86400,
    'supports_credentials' => true,
];
```

### Rules

- **Never** use `'allowed_origins' => ['*']` in production — always whitelist domains.
- `CORS_ALLOWED_ORIGINS` is a comma-separated env variable — never commit literal domains.
- Expose rate limit headers so clients can implement backoff.
- `supports_credentials: true` is required for Sanctum cookie-based SPA auth.

## Content Negotiation

### Request Headers

All API requests must send:

```
Accept: application/json
Content-Type: application/json
```

If `Accept` header is missing or not JSON, return `406 Not Acceptable` for API routes. Configure this in middleware or a base controller `__construct()`.

### Locale

Accept `Accept-Language` header for localized validation messages and enum labels:

```php
// In API middleware:
$locale = $request->getPreferredLanguage(['fr', 'en', 'nl']);
app()->setLocale($locale);
```

Default to `fr` (fr-BE). Supported values: `fr`, `en`, `nl`.

## Filtering

Accept filter parameters as flat query strings — not nested objects:

```
GET /api/v1/sessions?activity=yoga&level=beginner&date_from=2026-04-01&date_to=2026-04-30
```

### Filter Implementation

Use a dedicated query scope or filter class — never inline `where()` chains in controllers:

```php
// In SessionController:
$sessions = $service->search($request->validated());

// In SessionService:
public function search(array $filters): LengthAwarePaginator
{
    return Session::query()
        ->when($filters['activity'] ?? null, fn ($q, $v) => $q->where('activity', $v))
        ->when($filters['level'] ?? null, fn ($q, $v) => $q->where('level', $v))
        ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('start_time', '>=', $v))
        ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('start_time', '<=', $v))
        ->paginate($filters['per_page'] ?? 20);
}
```

Validate all filter inputs in the Form Request — never pass raw query strings to Eloquent.

## Authentication

### Sanctum Token Scopes

API tokens are scoped by role. See `auth-roles.instructions.md` for the full scope table.

Token creation must set abilities matching the user's role:

```php
$token = $user->createToken('api', match ($user->role) {
    UserRole::Coach      => ['sessions:write', 'bookings:read', 'profile:write'],
    UserRole::Athlete    => ['sessions:read', 'bookings:write', 'profile:write'],
    UserRole::Accountant => ['transactions:read', 'invoices:read', 'exports:read'],
    UserRole::Admin      => ['*'],
});
```

### Token-Based vs Cookie-Based

| Client | Auth Method |
|--------|-------------|
| SPA (same domain) | Sanctum cookie (stateful) |
| Mobile app / third-party | Sanctum bearer token (stateless) |

Both methods go through `auth:sanctum` middleware — controllers don't care which is used.

## Testing

### Required Tests Per Endpoint

Every API controller must have Pest feature tests covering:

1. **Happy path** — correct status code, response shape, and data values
2. **Unauthenticated** — `401` without token
3. **Forbidden** — `403` with wrong role
4. **Validation** — `422` with specific error keys for each invalid field
5. **Not found** — `404` for nonexistent resource
6. **Rate limited** — `429` when exceeding rate limit (test with `RateLimiter::for()` override)

### Test Pattern

```php
it('lists published sessions for an unauthenticated user', function () {
    $session = Session::factory()->published()->create();

    getJson('/api/v1/sessions')
        ->assertOk()
        ->assertJsonPath('data.0.id', $session->id)
        ->assertJsonPath('data.0.type', 'sessions')
        ->assertJsonStructure(['data', 'meta', 'links']);
});

it('returns 401 for unauthenticated booking attempt', function () {
    postJson('/api/v1/bookings', ['session_id' => 1])
        ->assertUnauthorized();
});

it('returns 403 when athlete tries to create a session', function () {
    $athlete = User::factory()->athlete()->create();

    actingAs($athlete, 'sanctum')
        ->postJson('/api/v1/sessions', Session::factory()->raw())
        ->assertForbidden();
});

it('returns 422 with validation errors for missing fields', function () {
    $coach = User::factory()->coach()->create();

    actingAs($coach, 'sanctum')
        ->postJson('/api/v1/sessions', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['title', 'price', 'start_time']);
});
```

### Assert Cents

Money fields in responses must be asserted as integers:

```php
->assertJsonPath('data.price', 1200) // 12.00 EUR = 1200 cents
```

Never assert formatted strings like `"12,00 €"` in API test responses.
