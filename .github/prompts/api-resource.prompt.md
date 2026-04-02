---
description: "Scaffold an API resource endpoint: controller, JSON resource, form requests, policy, Sanctum token scopes, and Pest feature tests"
agent: "agent"
argument-hint: "Resource description, e.g. 'Sessions CRUD for coaches with public list for athletes'"
tools: [search, createFile, editFile, runInTerminal]
---

# Scaffold an API Resource

Generate all the files needed for a JSON API resource endpoint. This prompt creates API-only files — no Livewire or Blade. Use `/laravel-scaffold` for full-stack features or `/livewire-component` for UI-only additions.

## Project Context

- **Laravel 12**, PHP 8.2+, **Laravel Sanctum** for API authentication.
- Follow [copilot-instructions.md](../copilot-instructions.md) and [php.instructions.md](../instructions/php.instructions.md).
- Follow [auth-roles.instructions.md](../instructions/auth-roles.instructions.md) for Sanctum token scopes and policy patterns.
- Four roles: `coach`, `athlete`, `accountant`, `admin` — see `UserRole` enum.
- All monetary amounts stored as **integers in cents** (EUR). API responses expose cents — never format to decimals.
- Business logic in **Service** classes (`app/Services/`), not controllers.
- Localization: validation error messages via `lang/` files, not hardcoded.

## Clarify Before Generating

If the user's description is ambiguous, ask (at most 2 questions):

1. Which **role(s)** can access this resource (and at what level — read, write, or both)?
2. Does the resource **already have a model and migration**, or should we scaffold those too?

If the description is clear enough, proceed without asking.

## What to Generate

### 1. JSON Resource

Create `app/Http/Resources/<Name>Resource.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'type'       => 'sessions',
            // Map all public attributes — keep cents for money fields
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
```

Rules:
- `final class`, `declare(strict_types=1)`.
- Return `id` and `type` (plural snake_case table name) as top-level keys.
- Money fields: return raw integer cents. Add a comment `// cents` next to each.
- Dates: always `toIso8601String()`.
- Relationships: use `$this->whenLoaded('relation')` — never eager-load inside the resource.
- Sensitive fields (email, phone, payout amounts): include only `$this->when()` with role-based conditions.
- If the resource has a collection variant with aggregation (e.g. pagination meta), also create `<Name>Collection` extending `ResourceCollection`.

### 2. Form Requests

Create one per mutating action — `Store<Name>Request`, `Update<Name>Request`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

final class StoreSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === UserRole::Coach;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'title'    => ['required', 'string', 'max:255'],
            'price'    => ['required', 'integer', 'min:0'], // cents
            // ...
        ];
    }
}
```

Rules:
- `final class`, `declare(strict_types=1)`.
- `authorize()` uses the `UserRole` enum — never hardcoded strings.
- Money rules: `['required', 'integer', 'min:0']` — never `numeric` or `decimal`.
- Use `Rule::in()` with enum `->value` for enum-backed fields.
- Custom validation messages via localization keys in `messages()` method.

### 3. API Controller

Create `app/Http/Controllers/Api/<Name>Controller.php`:

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

        return SessionResource::collection(
            Session::with(['coach'])->paginate()
        );
    }

    public function store(StoreSessionRequest $request): JsonResponse
    {
        $session = $this->service->create($request->validated());

        return SessionResource::make($session)
            ->response()
            ->setStatusCode(201);
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

Rules:
- `final class`, `declare(strict_types=1)`.
- Thin controller: validate via Form Request, authorize via Policy, delegate to Service, return Resource.
- `index` returns paginated `ResourceCollection`. Default 15 per page — allow `?per_page` up to 100.
- `store` returns `201`. `destroy` returns `204` with null body.
- `show` and `update` use route model binding.
- Never `return response()->json($model)` — always wrap in a Resource class.
- Filter/sort via query parameters — implement in the controller using Eloquent scopes, not in the service.

### 4. Routes

Register in `routes/api.php`:

```php
Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('sessions', SessionController::class);
});
```

Rules:
- Use `Route::apiResource()` for standard REST — it excludes `create` and `edit` (form routes).
- URL segments: plural `kebab-case` (e.g. `coaching-sessions`, not `coachingSessions`).
- Apply `auth:sanctum` middleware to all mutating routes.
- Public read endpoints (e.g. session listing for discovery): place outside the `auth:sanctum` group.
- Nest related resources when the parent is required: `sessions/{session}/bookings`.
- Version prefix: `api/v1/` — use `Route::prefix('v1')` in the group.

### 5. Sanctum Token Scopes

Ensure token abilities match the resource and role per [auth-roles.instructions.md](../instructions/auth-roles.instructions.md):

| Role | Abilities for this resource |
|------|----------------------------|
| Coach | `<resource>:read`, `<resource>:write` (own only) |
| Athlete | `<resource>:read`, `<resource>:book` (if applicable) |
| Accountant | `<resource>:read` |
| Admin | `<resource>:*` |

Add ability checks in the controller or form request if the endpoint serves multiple roles:

```php
if (! $request->user()->tokenCan('<resource>:write')) {
    abort(403);
}
```

### 6. Policy

If a Policy does not already exist for this resource, create one following [auth-roles.instructions.md](../instructions/auth-roles.instructions.md):

- `viewAny`, `view`, `create`, `update`, `delete` — map each to the role permission matrix.
- `before()` grants admin bypass.
- Ownership checks for coach/athlete resources.

### 7. Pest Feature Tests

Create `tests/Feature/Api/<Name>ControllerTest.php`:

```php
<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Session;
use App\Models\User;

beforeEach(function () {
    $this->coach   = User::factory()->create(['role' => UserRole::Coach]);
    $this->athlete = User::factory()->create(['role' => UserRole::Athlete]);
    $this->admin   = User::factory()->create(['role' => UserRole::Admin]);
});

describe('GET /api/v1/sessions', function () {
    it('returns paginated sessions', function () {
        Session::factory()->count(3)->create();

        $this->actingAs($this->athlete, 'sanctum')
            ->getJson('/api/v1/sessions')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data' => [['id', 'type']], 'links', 'meta']);
    });

    it('rejects unauthenticated requests', function () {
        $this->getJson('/api/v1/sessions')
            ->assertUnauthorized();
    });
});

// ...store, show, update, destroy
```

Every test file must cover:

1. **Happy path** — authorized role, valid data, correct status code and JSON structure.
2. **Authorization** — each of the four roles: who gets 200/201/204, who gets 403.
3. **Unauthenticated** — returns 401, not a redirect.
4. **Validation** — missing required fields return 422 with `errors` object keyed by field.
5. **Not found** — non-existent ID returns 404.
6. **Ownership** — coach A cannot update/delete coach B's resource.
7. **Pagination** — `index` returns `data`, `links`, and `meta` keys.
8. **Filtering** — if query params are supported, test that they actually filter results.

Use `actingAs($user, 'sanctum')` — always specify the `sanctum` guard.

## Output Order

Generate files in this order:

1. JSON Resource (`app/Http/Resources/`)
2. Form Requests (`app/Http/Requests/`)
3. Controller (`app/Http/Controllers/Api/`)
4. Routes (`routes/api.php` — append to existing file)
5. Policy (if new — `app/Policies/`)
6. Pest tests (`tests/Feature/Api/`)

After generating, list all created files and show a sample `curl` command for the primary endpoint.
