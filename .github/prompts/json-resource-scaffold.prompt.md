---
description: "Scaffold a Laravel JSON API Resource with conditional relationship loading, role-based field visibility, and money/date formatting. Generates the Resource class and a Pest test."
argument-hint: "Model name, e.g. 'Session', 'Booking', 'User', 'CoachProfile'"
agent: "agent"
tools: [read, edit, search, execute]
---

# JSON Resource Scaffold

Generate a Laravel JSON API Resource class for the Motivya project with conditional relationships and role-based field visibility.

## Before Writing

1. Read [api-conventions.instructions.md](../instructions/api-conventions.instructions.md) — the **authoritative source** for response format, money fields, date formatting, and `whenLoaded()` rules.
2. Read [auth-roles.instructions.md](../instructions/auth-roles.instructions.md) — for the four-role model and how to check roles in Resources.
3. Read [php.instructions.md](../instructions/php.instructions.md) — for strict types, code style.
4. Read [testing-conventions.instructions.md](../instructions/testing-conventions.instructions.md) — for Pest test structure.
5. Search `app/Http/Resources/` to check if the Resource already exists — update rather than duplicate.
6. Search the corresponding model in `app/Models/` to understand its fields, relationships, casts, and enums.
7. Search existing Resources for project patterns (e.g., how money or dates are already handled).

## Input

The user provides a model name. Infer fields from the model's migration and `$fillable`/`$casts`. If the model doesn't exist yet, ask the user for the field list.

## Output Structure

Generate exactly **2 files**:

### 1. Resource Class — `app/Http/Resources/{Model}Resource.php`

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\UserRole;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class {Model}Resource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            // --- Always visible ---
            'id'   => $this->id,
            'type' => '{plural-kebab}',

            // --- Public fields ---
            // List all fields safe for any authenticated user

            // --- Money fields (integer cents, never formatted) ---
            // 'price' => $this->price, // cents

            // --- Dates (ISO 8601 with timezone) ---
            // 'created_at' => $this->created_at?->toIso8601String(),

            // --- Enums (string value, not instance) ---
            // 'status' => $this->status?->value,

            // --- Conditional relationships (only when eager-loaded) ---
            // 'coach'    => new CoachResource($this->whenLoaded('coach')),
            // 'bookings' => BookingResource::collection($this->whenLoaded('bookings')),

            // --- Role-based fields ---
            // Fields visible only to specific roles
            ...$this->roleBasedFields($request),
        ];
    }

    private function roleBasedFields(Request $request): array
    {
        $user = $request->user();
        $fields = [];

        // Coach or Admin: see participant details, revenue
        if ($user?->role === UserRole::Coach || $user?->role === UserRole::Admin) {
            // $fields['current_participants'] = $this->current_participants;
        }

        // Admin only: see internal/sensitive data
        if ($user?->role === UserRole::Admin) {
            // $fields['internal_notes'] = $this->internal_notes;
        }

        // Accountant: see financial fields
        if ($user?->role === UserRole::Accountant || $user?->role === UserRole::Admin) {
            // $fields['platform_fee'] = $this->platform_fee; // cents
            // $fields['vat_amount']   = $this->vat_amount;   // cents
        }

        // Owner: see own private data (e.g., athlete sees their own booking details)
        if ($user?->id === $this->user_id) {
            // $fields['payment_status'] = $this->payment_status?->value;
        }

        return $fields;
    }
}
```

### 2. Pest Test — `tests/Feature/Resources/{Model}ResourceTest.php`

```php
<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Http\Resources\{Model}Resource;
use App\Models\{Model};
use App\Models\User;

it('includes public fields for any authenticated user', function () {
    $user = User::factory()->create(['role' => UserRole::Athlete]);
    $model = {Model}::factory()->create();

    $resource = (new {Model}Resource($model))
        ->toResponse(request()->setUserResolver(fn () => $user))
        ->getData(true);

    expect($resource['data'])
        ->toHaveKeys(['id', 'type', /* ...public fields */]);
});

it('includes role-specific fields for coaches', function () {
    $coach = User::factory()->create(['role' => UserRole::Coach]);
    $model = {Model}::factory()->create();

    $resource = (new {Model}Resource($model))
        ->toResponse(request()->setUserResolver(fn () => $coach))
        ->getData(true);

    expect($resource['data'])
        ->toHaveKeys([/* coach-visible fields */]);
});

it('includes admin-only fields for admins', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $model = {Model}::factory()->create();

    $resource = (new {Model}Resource($model))
        ->toResponse(request()->setUserResolver(fn () => $admin))
        ->getData(true);

    expect($resource['data'])
        ->toHaveKeys([/* admin-only fields */]);
});

it('hides role-specific fields from athletes', function () {
    $athlete = User::factory()->create(['role' => UserRole::Athlete]);
    $model = {Model}::factory()->create();

    $resource = (new {Model}Resource($model))
        ->toResponse(request()->setUserResolver(fn () => $athlete))
        ->getData(true);

    expect($resource['data'])
        ->not->toHaveKeys([/* coach/admin-only fields */]);
});

it('includes conditional relationships only when loaded', function () {
    $user = User::factory()->create();
    $model = {Model}::factory()->create();

    // Without loading
    $resource = (new {Model}Resource($model))
        ->toResponse(request()->setUserResolver(fn () => $user))
        ->getData(true);

    expect($resource['data'])->not->toHaveKey('relationship_name');

    // With loading
    $model->load('relationship');
    $resource = (new {Model}Resource($model))
        ->toResponse(request()->setUserResolver(fn () => $user))
        ->getData(true);

    expect($resource['data'])->toHaveKey('relationship_name');
});

it('formats money fields as integer cents', function () {
    $user = User::factory()->create();
    $model = {Model}::factory()->create(['price' => 1250]);

    $resource = (new {Model}Resource($model))
        ->toResponse(request()->setUserResolver(fn () => $user))
        ->getData(true);

    expect($resource['data']['price'])->toBe(1250);
});

it('formats dates as ISO 8601', function () {
    $user = User::factory()->create();
    $model = {Model}::factory()->create();

    $resource = (new {Model}Resource($model))
        ->toResponse(request()->setUserResolver(fn () => $user))
        ->getData(true);

    expect($resource['data']['created_at'])->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/');
});
```

## Rules

- **`type` field**: Always include a `type` field with the plural kebab-case model name (`sessions`, `bookings`, `coach-profiles`)
- **Money**: Raw integer cents — add `// cents` inline comment. Never format to decimals.
- **Dates**: `->toIso8601String()` with null-safe operator (`?->`)
- **Enums**: Return `->value` (the string), not the enum instance
- **Relationships**: Always use `$this->whenLoaded('relation')` — never `$this->relation` (prevents N+1)
- **Nested resources**: Wrap loaded relationships in their Resource class: `new CoachResource($this->whenLoaded('coach'))`
- **Collections**: Use `::collection()` for has-many: `BookingResource::collection($this->whenLoaded('bookings'))`
- **Nullable fields**: Include with `null` value — don't omit them from the response
- **Role checks**: Use `$request->user()?->role === UserRole::X` — reference the enum, never hardcode strings
- **No eager loading inside Resources**: Never call `$this->load()` — the controller or service decides what to eager-load
- **Owner checks**: For "user sees their own data" patterns, compare `$request->user()?->id` with the model's owner FK
- **No business logic**: Resources are presentation-only — no calculations, state transitions, or service calls
- **Private method for role fields**: Extract role-based logic into a `roleBasedFields()` method to keep `toArray()` clean

## Field Visibility Matrix

When generating, classify every model field into one of these tiers:

| Tier | Who sees it | Example fields |
|------|-------------|----------------|
| Public | Any authenticated user | `id`, `title`, `activity_type`, `start_time`, `price` |
| Owner | The resource owner only | `payment_intent_id`, `cancellation_reason` |
| Coach/Admin | Coach (if owner) or Admin | `current_participants`, `revenue`, `min_participants` |
| Accountant/Admin | Financial roles | `platform_fee`, `vat_amount`, `payout_amount` |
| Admin only | Admin | `internal_notes`, `stripe_account_id`, `ip_address` |
| Never exposed | No one via API | `password`, `remember_token`, `stripe_customer_id` |

## Validation

After generating:
- [ ] `declare(strict_types=1)` at top
- [ ] `type` field present with correct plural kebab-case value
- [ ] Money fields are raw integers with `// cents` comment
- [ ] Dates use `?->toIso8601String()`
- [ ] Enums use `?->value`
- [ ] All relationships use `whenLoaded()` — zero N+1 risk
- [ ] Role checks use `UserRole` enum, not hardcoded strings
- [ ] No eager loading inside the Resource
- [ ] Nullable fields return `null`, not omitted
- [ ] Pest test covers: public fields, role-based visibility (at least coach + admin + athlete), conditional relationships, money format, date format
