---
description: "Generate a resource controller (web or API) wired to Form Requests for validation, a Service class for business logic, a Policy for authorization, and Pest feature tests. Thin controller pattern — no logic in the controller."
argument-hint: "Controller name or feature, e.g. 'CoachSessionController' or 'API sessions CRUD'"
agent: "agent"
tools: [read, edit, search, execute]
---

# Controller Scaffold

Generate a thin resource controller for the Motivya project, wired to existing (or newly created) Form Requests, Service, and Policy classes.

## Before Writing

1. Read [php.instructions.md](../instructions/php.instructions.md) for strict types, `final class`, and naming.
2. Read [auth-roles.instructions.md](../instructions/auth-roles.instructions.md) for route organization, middleware, and policy delegation.
3. Read [api-conventions.instructions.md](../instructions/api-conventions.instructions.md) for JSON response envelopes, status codes, and pagination (API controllers only).
4. Read [testing-conventions.instructions.md](../instructions/testing-conventions.instructions.md) for feature test patterns.
5. Search `app/Services/` for the corresponding service class — if missing, note it in output.
6. Search `app/Http/Requests/` for existing form requests — if missing, generate stubs or note dependencies.
7. Search `app/Policies/` for the policy — if missing, note it in output.
8. Search `app/Http/Resources/` for JSON resources (API controllers only).
9. Search the route files (`routes/web.php`, `routes/coach.php`, `routes/admin.php`, `routes/api.php`) to avoid duplicate registrations.

## Input

The user provides one of:
- A controller name: `CoachSessionController`, `BookingController`, `AdminUserController`
- A feature description: "sessions CRUD for coaches", "athlete bookings", "API sessions endpoint"
- A model name with context: "Session controller for the coach dashboard"

### Infer Controller Type

| Signal | Type | Namespace | Route file |
|--------|------|-----------|-----------|
| "API", "JSON", "endpoint" | API | `App\Http\Controllers\Api` | `routes/api.php` |
| "admin" | Web (admin) | `App\Http\Controllers\Admin` | `routes/admin.php` |
| "coach" | Web (coach) | `App\Http\Controllers\Coach` | `routes/coach.php` |
| "Livewire", "component" | Skip — use `/livewire-component` instead | — | — |
| Default | Web | `App\Http\Controllers` | `routes/web.php` |

Ask at most 1 question — only if the type cannot be inferred.

## Generation Rules

### 1. Web Controller (`app/Http/Controllers/{Namespace}/{Model}Controller.php`)

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSessionRequest;
use App\Http\Requests\UpdateSessionRequest;
use App\Models\Session;
use App\Services\SessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class SessionController extends Controller
{
    public function __construct(
        private readonly SessionService $service,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', Session::class);

        $sessions = Session::query()
            ->forCoach(auth()->user())
            ->latest('date')
            ->paginate(20);

        return view('coach.sessions.index', compact('sessions'));
    }

    public function create(): View
    {
        $this->authorize('create', Session::class);

        return view('coach.sessions.create');
    }

    public function store(StoreSessionRequest $request): RedirectResponse
    {
        $session = $this->service->create(
            auth()->user(),
            $request->validated(),
        );

        return redirect()
            ->route('coach.sessions.show', $session)
            ->with('success', __('sessions.created'));
    }

    public function show(Session $session): View
    {
        $this->authorize('view', $session);

        return view('coach.sessions.show', compact('session'));
    }

    public function edit(Session $session): View
    {
        $this->authorize('update', $session);

        return view('coach.sessions.edit', compact('session'));
    }

    public function update(UpdateSessionRequest $request, Session $session): RedirectResponse
    {
        $this->service->update($session, $request->validated());

        return redirect()
            ->route('coach.sessions.show', $session)
            ->with('success', __('sessions.updated'));
    }

    public function destroy(Session $session): RedirectResponse
    {
        $this->authorize('delete', $session);

        $this->service->delete($session);

        return redirect()
            ->route('coach.sessions.index')
            ->with('success', __('sessions.deleted'));
    }
}
```

### 2. API Controller (`app/Http/Controllers/Api/{Model}Controller.php`)

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSessionRequest;
use App\Http\Requests\UpdateSessionRequest;
use App\Http\Resources\SessionResource;
use App\Models\Session;
use App\Services\SessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class SessionController extends Controller
{
    public function __construct(
        private readonly SessionService $service,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Session::class);

        $sessions = Session::query()
            ->with(['coach'])
            ->paginate(min((int) request('per_page', 20), 100));

        return SessionResource::collection($sessions);
    }

    public function store(StoreSessionRequest $request): JsonResponse
    {
        $session = $this->service->create(
            $request->user(),
            $request->validated(),
        );

        return SessionResource::make($session)
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('api.sessions.show', $session));
    }

    public function show(Session $session): SessionResource
    {
        $this->authorize('view', $session);

        return SessionResource::make($session->load(['coach']));
    }

    public function update(UpdateSessionRequest $request, Session $session): SessionResource
    {
        $session = $this->service->update($session, $request->validated());

        return SessionResource::make($session);
    }

    public function destroy(Session $session): JsonResponse
    {
        $this->authorize('delete', $session);

        $this->service->delete($session);

        return response()->json(null, 204);
    }
}
```

### Controller Rules

**Structure:**
- `declare(strict_types=1)` and `final class`
- Extend `App\Http\Controllers\Controller` — the only allowed base class
- Constructor injection with `private readonly` for the service class
- One service per controller — if the action spans multiple services, the primary service orchestrates

**Thin controller principle — controllers do exactly 4 things:**
1. **Authorize** — via `$this->authorize()` (policy) or Form Request `authorize()`
2. **Validate** — via type-hinted Form Request parameter (automatic)
3. **Delegate** — call the service method with validated data
4. **Respond** — return view/redirect (web) or Resource/JSON (API)

Never put business logic, DB queries beyond simple reads, event dispatching, or notification sending in controllers.

**Authorization:**
- Use `$this->authorize('ability', $model)` for `index`, `show`, `destroy`
- Form Request `authorize()` handles `store` and `update` (validation + auth in one object)
- Never inline role checks (`if ($user->role === ...`) — always delegate to policy

**Query patterns in controllers:**
- `index()` may use Eloquent scopes for filtering/sorting — this is presentation logic, not business logic
- `show()` may eager-load relationships for the view/resource
- Never execute raw DB queries in controllers — use scopes or the service

**Flash messages (web only):**
- Use `->with('success', __('domain.key'))` for successful mutations
- Localization keys, never hardcoded strings
- Only `success` and `error` flash types

**Status codes (API only):**

| Method | Status | Response |
|--------|--------|----------|
| `index` | 200 | `ResourceCollection` (paginated) |
| `store` | 201 | `Resource` + `Location` header |
| `show` | 200 | `Resource` |
| `update` | 200 | `Resource` |
| `destroy` | 204 | `null` |

### 3. Route Registration

**Web routes:**

```php
// routes/coach.php — already has ['web', 'auth', 'role:coach'] middleware
Route::resource('sessions', Coach\SessionController::class)
    ->names('coach.sessions');
```

**API routes:**

```php
// routes/api/v1.php
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('sessions', Api\SessionController::class);
});

// Public read-only (no auth)
Route::get('sessions', [Api\SessionController::class, 'index']);
Route::get('sessions/{session}', [Api\SessionController::class, 'show']);
```

**Custom actions (non-CRUD):**

```php
// Append to the resource routes
Route::post('sessions/{session}/cancel', [Coach\SessionController::class, 'cancel'])
    ->name('coach.sessions.cancel');
```

Route rules:
- `Route::resource()` for web, `Route::apiResource()` for API
- Register in the correct route file per the route organization table
- Named routes: `{prefix}.{plural_model}.{action}` (e.g., `coach.sessions.index`)
- URL segments: plural `kebab-case`
- Do NOT register duplicate routes — check existing route files first
- Custom actions as `POST` verbs on the resource (e.g., `/cancel`, `/confirm`)

### 4. Pest Feature Tests (`tests/Feature/{Namespace}/{Model}ControllerTest.php`)

```php
<?php

declare(strict_types=1);

use App\Enums\SessionStatus;
use App\Enums\UserRole;
use App\Models\Session;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('CoachSessionController', function () {

    beforeEach(function () {
        $this->coach = User::factory()->coach()->create();
        $this->otherCoach = User::factory()->coach()->create();
        $this->athlete = User::factory()->athlete()->create();
        $this->admin = User::factory()->admin()->create();
    });

    describe('GET /coach/sessions', function () {
        it('lists only the coach own sessions', function () {
            Session::factory()->for($this->coach, 'coach')->count(3)->create();
            Session::factory()->for($this->otherCoach, 'coach')->count(2)->create();

            $this->actingAs($this->coach)
                ->get(route('coach.sessions.index'))
                ->assertOk()
                ->assertViewHas('sessions', fn ($s) => $s->count() === 3);
        });

        it('redirects unauthenticated users to login', function () {
            $this->get(route('coach.sessions.index'))
                ->assertRedirect(route('login'));
        });

        it('forbids athlete from accessing coach sessions', function () {
            $this->actingAs($this->athlete)
                ->get(route('coach.sessions.index'))
                ->assertForbidden();
        });
    });

    describe('POST /coach/sessions', function () {
        it('creates a session with valid data', function () {
            $this->actingAs($this->coach)
                ->post(route('coach.sessions.store'), validSessionData())
                ->assertRedirect();

            $this->assertDatabaseHas('sessions', [
                'coach_id' => $this->coach->id,
            ]);
        });

        it('rejects invalid data with validation errors', function () {
            $this->actingAs($this->coach)
                ->post(route('coach.sessions.store'), [])
                ->assertSessionHasErrors();
        });
    });

    describe('PUT /coach/sessions/{session}', function () {
        it('allows coach to update own session', function () {
            $session = Session::factory()
                ->for($this->coach, 'coach')
                ->draft()
                ->create();

            $this->actingAs($this->coach)
                ->put(route('coach.sessions.update', $session), validSessionData([
                    'location' => 'Updated Location',
                ]))
                ->assertRedirect();

            expect($session->fresh()->location)->toBe('Updated Location');
        });

        it('forbids coach from updating another coach session', function () {
            $session = Session::factory()
                ->for($this->otherCoach, 'coach')
                ->create();

            $this->actingAs($this->coach)
                ->put(route('coach.sessions.update', $session), validSessionData())
                ->assertForbidden();
        });
    });

    describe('DELETE /coach/sessions/{session}', function () {
        it('allows coach to delete own draft session', function () {
            $session = Session::factory()
                ->for($this->coach, 'coach')
                ->draft()
                ->create();

            $this->actingAs($this->coach)
                ->delete(route('coach.sessions.destroy', $session))
                ->assertRedirect(route('coach.sessions.index'));

            $this->assertDatabaseMissing('sessions', ['id' => $session->id]);
        });

        it('allows admin to delete any session', function () {
            $session = Session::factory()->create();

            $this->actingAs($this->admin)
                ->delete(route('coach.sessions.destroy', $session))
                ->assertRedirect();
        });
    });
});

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

**Test categories per endpoint:**

| Category | What to test |
|----------|-------------|
| Happy path | Authorized role, valid data, correct status/redirect |
| Authorization | All 4 roles: who succeeds, who gets 403 |
| Unauthenticated | Redirect to login (web) or 401 (API) |
| Ownership | Coach A can't mutate Coach B's resources |
| Validation | Empty payload → validation errors |
| Not found | Non-existent model → 404 |
| State guards | Can't update confirmed session, can't delete with bookings |
| Pagination | `index` paginates correctly (API: `data`/`meta`/`links`) |

**Test conventions:**
- `describe()` per HTTP route: `'GET /coach/sessions'`, `'POST /api/v1/sessions'`
- `beforeEach` creates all 4 role users + an `otherCoach` for ownership tests
- `validData()` helper at file bottom — each test overrides one field
- Web tests: `assertRedirect()`, `assertSessionHasErrors()`, `assertViewHas()`
- API tests: `assertOk()`, `assertCreated()`, `assertJsonStructure()`, `actingAs($user, 'sanctum')`

## Output Order

1. **Controller** — `app/Http/Controllers/{Namespace}/{Model}Controller.php`
2. **Route registration** — append to the correct route file
3. **Pest tests** — `tests/Feature/{Namespace}/{Model}ControllerTest.php`
4. **Dependency check** — list missing Service, Form Request, Policy, or Resource classes with links to the prompts that generate them

## Dependency Check

After generating, list which supporting classes exist and which need scaffolding:

```
✅ app/Services/SessionService.php — exists
✅ app/Policies/SessionPolicy.php — exists
❌ app/Http/Requests/StoreSessionRequest.php — run /form-request-scaffold StoreSession
❌ app/Http/Resources/SessionResource.php — run /api-resource Session (API only)
```

## Validation

After generating, verify:
- `declare(strict_types=1)` on all files
- `final class` on controller
- Controller extends `Controller` — no other base class
- No business logic in controller — only authorize, validate, delegate, respond
- Service injected via `private readonly` constructor promotion
- Authorization via `$this->authorize()` or Form Request — no inline role checks
- Flash messages use `__()` localization — no hardcoded strings
- API responses wrapped in Resource classes — never raw `response()->json($model)`
- Status codes match the method table (201 for store, 204 for destroy)
- Routes registered in the correct file with proper middleware
- Tests cover all 8 categories with all 4 roles
- Tests use `validData()` helper pattern
