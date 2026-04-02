---
description: "Generate a Laravel database migration from a plain-English table or column description. Follows Motivya migration conventions: integer cents for money, string enums, reversible down(), SQLite compatibility, and proper foreign key cascades."
argument-hint: "Describe the table or columns, e.g. 'sessions table with coach, date, price, capacity'"
agent: "agent"
tools: [read, edit, search, execute]
---

# Migration Scaffold

You are generating a Laravel migration for the Motivya project.

## Input

The user provides a plain-English description of a table or schema change.
Examples:
- "sessions table with coach, date, price, min/max participants"
- "add stripe_account_id to users"
- "pivot table linking sessions to activity types"

## Before Writing

1. Read [database-migrations.instructions.md](../instructions/database-migrations.instructions.md) for all column type rules, foreign key conventions, and naming patterns.
2. Read [php.instructions.md](../instructions/php.instructions.md) for PHP coding standards (`declare(strict_types=1)`, return types).
3. Search `database/migrations/` for existing migrations to avoid conflicts or duplication.
4. Check the **Core Schema** table in the database-migrations instruction — if the table is listed there, use its documented columns as the baseline.

## Clarify Only When Ambiguous

Ask at most 2 questions if the description is unclear. Do NOT ask if the answer is already defined in the instructions or schema reference. Common answers that need no question:

| Ambiguity | Default answer |
|-----------|---------------|
| Money column type? | `unsignedInteger` (cents) |
| Enum column type? | `string` with default value |
| Timestamp vs datetime? | `timestamp` always |
| Cascade or restrict? | Follow the cascade rules table in database-migrations instruction |
| Index needed? | Yes for FK columns (auto), status+date combos, unique constraints |

## Generation Rules

### 1. Migration File

Generate via the artisan command pattern, then fill in the body:

```bash
php artisan make:migration create_{table}_table
# or
php artisan make:migration add_{columns}_to_{table}_table
```

Apply these rules strictly:

- `declare(strict_types=1)` at top of file
- `up()` and `down()` both fully implemented — never empty `down()`
- Money columns: `unsignedInteger` or `unsignedBigInteger` only — add a comment with the human-readable meaning:

```php
$table->unsignedInteger('price_per_person'); // EUR cents: €12.50 = 1250
```

- Enum-like columns: `string` with `->default()` matching the PHP enum's default case:

```php
$table->string('status')->default('draft'); // SessionStatus enum
```

- Foreign keys: `foreignId()->constrained()` with explicit on-delete behavior:

```php
$table->foreignId('coach_id')->constrained('users')->cascadeOnDelete();
```

- Nullable self-references:

```php
$table->foreignId('parent_session_id')->nullable()->constrained('sessions')->nullOnDelete();
```

- Composite unique constraints where documented:

```php
$table->unique(['session_id', 'athlete_id']);
```

- Composite indexes for common query patterns:

```php
$table->index(['status', 'date']); // Session listing filter+sort
```

- `$table->timestamps()` on every table unless it's a pure pivot with no extra columns.
- No `$table->enum()` — ever.
- No `$table->mediumText()`, `$table->dateTime()`, or MySQL-specific syntax.

### 2. down() Method

Must perfectly reverse the `up()`:

| `up()` action | `down()` action |
|---------------|-----------------|
| `Schema::create('x', ...)` | `Schema::dropIfExists('x')` |
| `$table->addColumn(...)` | `$table->dropColumn(...)` |
| `$table->index([...])` | `$table->dropIndex([...])` |
| `$table->foreign(...)` | `$table->dropForeign(...)` |
| `$table->unique([...])` | `$table->dropUnique([...])` |

### 3. Factory (if creating a new table)

Also generate a model factory in `database/factories/`:

```php
public function definition(): array
{
    return [
        'status' => SessionStatus::Draft->value,
        'price_per_person' => fake()->numberBetween(500, 5000), // €5–€50
        // ... all columns with realistic fake data
    ];
}
```

Rules:
- Use backed enum `->value` for string enum columns
- Use `fake()->numberBetween()` for cent amounts — not `randomFloat`
- Foreign keys use the related factory: `'coach_id' => User::factory()`
- Include state methods for common variants:

```php
public function confirmed(): static
{
    return $this->state(['status' => SessionStatus::Confirmed->value]);
}
```

### 4. Model Update (if adding columns to existing table)

If adding columns to an existing table, update the model's `$fillable` array and add any necessary casts:

```php
protected $casts = [
    'status' => SessionStatus::class,
    'price_per_person' => 'integer',
    'confirmed_at' => 'datetime',
    'is_vat_subject' => 'boolean',
];
```

## Output Order

1. **Migration file** — full `up()` and `down()`
2. **Factory** — if new table (with state methods for enum values)
3. **Model updates** — if altering an existing table (`$fillable`, `$casts`, relationships)
4. **Run command** — the exact `php artisan migrate` to apply

## Validation

After generating, verify:
- Every `up()` action has a matching `down()` reversal
- No `enum()`, `mediumText()`, `dateTime()`, or MySQL-only syntax
- All money columns are `unsignedInteger` or `unsignedBigInteger`
- All FK columns have explicit cascade/restrict/null on-delete
- Column defaults match the corresponding PHP backed enum default case
- The migration runs cleanly: `php artisan migrate:fresh --database=sqlite`
