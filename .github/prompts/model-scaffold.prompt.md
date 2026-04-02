---
description: "Generate an Eloquent model with relationships, casts, scopes, backed-enum integration, and factory from a table description or migration. Follows Motivya conventions: integer cents, string-backed enums, strict types."
argument-hint: "Table or model name, e.g. 'Session' or 'bookings table'"
agent: "agent"
tools: [read, edit, search, execute]
---

# Model Scaffold

Generate a complete Eloquent model for the Motivya project from a table description, model name, or existing migration.

## Before Writing

1. Read [php.instructions.md](../instructions/php.instructions.md) for strict types, backed enums, service class boundaries, and money rules.
2. Read [database-migrations.instructions.md](../instructions/database-migrations.instructions.md) for the core schema reference table and column type conventions.
3. Search `database/migrations/` for an existing migration matching the table — use its columns as the source of truth.
4. Search `app/Models/` to check if the model already exists. If it does, update rather than overwrite.
5. Search `app/Enums/` for existing backed enums that apply to this model's columns.

## Input

The user provides one of:
- A model name: `Session`, `Booking`, `User`
- A table name: `sessions`, `bookings`, `invoices`
- A description: "model for the sessions table with coach relationship"
- Nothing specific — infer from context or ask which model to scaffold

If the model maps to a table in the core schema reference, use those documented columns. If a migration exists, derive columns from it.

## Clarify Only When Necessary

Ask at most 1 question — and only if:
- The table is not in the core schema and no migration exists
- The user's description mentions relationships that conflict with the schema

Do NOT ask about:
- Cast types (derive from column types)
- Enum classes (derive from column names matching known enums)
- Relationship types (derive from foreign key conventions)

## Generation Rules

### 1. Model File (`app/Models/{Name}.php`)

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Session extends Model
{
    use HasFactory;

    protected $fillable = [
        // All non-auto columns except id, timestamps
    ];

    protected $casts = [
        // Derived from column types
    ];
}
```

Rules:

**Class declaration:**
- `declare(strict_types=1)` on every file
- `final` class — models are not extended in Motivya
- `use HasFactory` trait always included
- For `User` model: extend `Authenticatable` instead of `Model`

**$fillable:**
- Include every column except `id`, `created_at`, `updated_at`
- Foreign key columns (`coach_id`, `session_id`, etc.) are fillable
- Status and enum columns are fillable
- Nullable timestamp columns (`confirmed_at`, `cancelled_at`) are fillable

**$casts — derive from column types:**

| Column pattern | Cast |
|----------------|------|
| `*_id` (foreign key) | Omit — integer by default |
| `role` | `UserRole::class` |
| `status` (on sessions) | `SessionStatus::class` |
| `status` (on bookings) | `BookingStatus::class` |
| `type` (on invoices) | `InvoiceType::class` |
| `price_*`, `*_fee`, `*_amount`, `*_vat`, `*_excl_vat`, `*_incl_vat`, `total_*` | `'integer'` |
| `is_*` | `'boolean'` |
| `*_at` (nullable timestamps) | `'datetime'` |
| `date` | `'date'` |
| `metadata`, `*_json` | `'array'` |

- Never cast `start_time` / `end_time` — they are `time` columns, leave as strings
- Never add casts that duplicate Laravel's defaults (`created_at`, `updated_at` are auto-cast)

**Relationships:**
- Derive from `foreignId()` columns in the migration
- Use return type declarations on all relationship methods

```php
// belongsTo — for every foreign key column
public function coach(): BelongsTo
{
    return $this->belongsTo(User::class, 'coach_id');
}

// hasMany — for the inverse side
public function bookings(): HasMany
{
    return $this->hasMany(Booking::class);
}

// belongsTo self-referencing (nullable parent)
public function parentSession(): BelongsTo
{
    return $this->belongsTo(self::class, 'parent_session_id');
}

public function childSessions(): HasMany
{
    return $this->hasMany(self::class, 'parent_session_id');
}
```

Relationship conventions:
- Specify the foreign key explicitly when the column name doesn't follow Laravel convention (e.g., `coach_id` → `User::class`)
- Use `self::class` for self-referencing relationships
- Name inverse relationships as the plural of the related model
- For pivot tables, use `belongsToMany` with explicit table and key names

**Scopes:**
- Add scopes for common query patterns derived from indexed columns:

```php
// Status-based scopes for every status enum
public function scopePublished(Builder $query): Builder
{
    return $query->where('status', SessionStatus::Published);
}

public function scopeConfirmed(Builder $query): Builder
{
    return $query->where('status', SessionStatus::Confirmed);
}

// Date-based scopes
public function scopeUpcoming(Builder $query): Builder
{
    return $query->where('date', '>=', now()->toDateString());
}

// Relationship scopes
public function scopeForCoach(Builder $query, User $coach): Builder
{
    return $query->where('coach_id', $coach->id);
}
```

Scope conventions:
- One scope per enum value for status columns
- Date comparison scopes for `date` columns (`upcoming`, `past`)
- `forCoach`, `forAthlete` owner scopes when FK references users table
- Always type-hint `Builder` parameter and return type
- Use enum references, never raw strings

**Accessors (only when needed):**
- Do NOT add money formatting accessors — that's the Blade layer's job (`<x-money>`)
- Add `fullName` accessor if model has `first_name` + `last_name`
- Add `isConfirmed`, `isCancelled` convenience accessors for status checks:

```php
public function isConfirmed(): bool
{
    return $this->status === SessionStatus::Confirmed;
}
```

### 2. Backed Enum (if not already in `app/Enums/`)

If the model uses a status or type column that doesn't have an existing enum:

```php
<?php

declare(strict_types=1);

namespace App\Enums;

enum SessionStatus: string
{
    case Draft     = 'draft';
    case Published = 'published';
    case Confirmed = 'confirmed';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __('enums.session_status.' . $this->value);
    }
}
```

Rules:
- Always string-backed
- Include a `label()` method that returns the localized display name
- Place in `app/Enums/`

### 3. Factory (`database/factories/{Name}Factory.php`)

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SessionStatus;
use App\Models\Session;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Session> */
final class SessionFactory extends Factory
{
    protected $model = Session::class;

    public function definition(): array
    {
        return [
            'coach_id' => User::factory(),
            'status' => SessionStatus::Draft->value,
            'date' => fake()->dateTimeBetween('+1 day', '+30 days')->format('Y-m-d'),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'price_per_person' => fake()->numberBetween(500, 5000),
            'min_participants' => 3,
            'max_participants' => 15,
            'current_participants' => 0,
            'postal_code' => fake()->numerify('1###'),
        ];
    }
}
```

Rules:
- `declare(strict_types=1)` and `final class`
- Money columns: `fake()->numberBetween()` with realistic cent ranges — never `randomFloat`
- Enum columns: use `EnumClass::Default->value` — never raw strings
- FK columns: use related factory (`User::factory()`)
- Dates: use `fake()->dateTimeBetween()` for realistic ranges
- Include state methods for each enum value:

```php
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

## Output Order

1. **Backed Enum** — if new (skip if already exists in `app/Enums/`)
2. **Model** — full class with fillable, casts, relationships, scopes, accessors
3. **Factory** — with definition and state methods for each enum value

## Validation

After generating, verify:
- `declare(strict_types=1)` on all files
- `final class` on model and factory
- Every FK column has a corresponding `belongsTo` relationship
- Every enum column has a cast to its enum class
- Every money column is cast to `'integer'`
- Every boolean column is cast to `'boolean'`
- No business logic in the model — only relationships, casts, scopes, and simple accessors
- Factory uses `->value` for enum columns and `numberBetween` for money
- Scopes use enum references, not raw strings
