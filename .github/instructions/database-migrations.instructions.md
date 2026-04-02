---
description: "Use when creating or modifying database migrations, adding columns, creating tables, defining indexes, foreign keys, or altering schema. Covers naming conventions, reversibility, column type rules, SQLite/MySQL compatibility, and Motivya-specific schema patterns."
applyTo: "database/migrations/**"
---
# Database Migration Rules

## Naming Conventions

Follow Laravel's timestamp-prefixed naming strictly:

| Action | Pattern | Example |
|--------|---------|---------|
| Create table | `create_{table}_table` | `2026_03_31_000001_create_sessions_table` |
| Add columns | `add_{columns}_to_{table}_table` | `2026_03_31_000002_add_location_to_sessions_table` |
| Remove columns | `remove_{columns}_from_{table}_table` | `2026_03_31_000003_remove_legacy_from_users_table` |
| Rename column | `rename_{old}_to_{new}_in_{table}_table` | `2026_03_31_000004_rename_name_to_title_in_sessions_table` |
| Create pivot | `create_{table1}_{table2}_table` | `2026_03_31_000005_create_activity_session_table` |

Rules:
- Table names are **plural**, snake_case: `sessions`, `bookings`, `processed_webhooks`
- Use `php artisan make:migration` — never create migration files manually
- One logical change per migration — never combine unrelated table changes

## Reversibility

Every migration MUST have a working `down()` method. No exceptions.

```php
public function up(): void
{
    Schema::create('sessions', function (Blueprint $table) {
        // ...
    });
}

public function down(): void
{
    Schema::dropIfExists('sessions');
}
```

Rules:
- `Schema::create()` → `down()` uses `Schema::dropIfExists()`
- `$table->addColumn()` → `down()` uses `$table->dropColumn()`
- `$table->index()` → `down()` uses `$table->dropIndex()`
- `$table->foreign()` → `down()` uses `$table->dropForeign()`
- Never leave `down()` empty — if a migration truly cannot be reversed, throw an exception:

```php
public function down(): void
{
    throw new \RuntimeException('This migration cannot be reversed. Restore from backup.');
}
```

## Column Type Rules

### Money Columns

All monetary amounts use `unsignedInteger` or `unsignedBigInteger` (cents in EUR). Never `decimal`, `float`, or `double`:

```php
$table->unsignedInteger('price_per_person');        // €12.50 = 1250
$table->unsignedInteger('platform_fee');             // In cents
$table->unsignedBigInteger('total_revenue');         // Large aggregates
```

### String Enum Columns

Use `string` columns for enum-backed values — not MySQL `enum()`. This keeps SQLite compatibility and matches the PHP backed enum pattern:

```php
$table->string('role')->default('athlete');           // UserRole enum
$table->string('status')->default('draft');           // SessionStatus enum
$table->string('booking_status')->default('pending_payment'); // BookingStatus enum
```

Never use `$table->enum()` — it generates a MySQL `ENUM` type that:
- Breaks SQLite in dev/test
- Cannot be altered without recreating the column
- Couples the database to application-level values

### Timestamps and Dates

```php
$table->timestamps();                                // created_at, updated_at
$table->timestamp('confirmed_at')->nullable();       // State transition timestamps
$table->timestamp('cancelled_at')->nullable();
$table->date('date');                                // Session date
$table->time('start_time');                          // Session times
$table->time('end_time');
```

Use nullable timestamps for optional state-tracking fields. Never use `datetime` — use `timestamp` for consistency.

### Boolean Columns

```php
$table->boolean('is_vat_subject')->default(false);
$table->boolean('is_mfa_enabled')->default(false);
$table->boolean('is_approved')->default(false);
```

Always provide a `default()` for boolean columns.

## Foreign Keys

Use `foreignId()` with `constrained()` for all foreign keys:

```php
$table->foreignId('coach_id')->constrained('users')->cascadeOnDelete();
$table->foreignId('session_id')->constrained()->cascadeOnDelete();
$table->foreignId('athlete_id')->constrained('users')->restrictOnDelete();
```

### Cascade Rules

| Relationship | On Delete | Rationale |
|--------------|-----------|-----------|
| Session → Coach (user) | `cascadeOnDelete` | Coach deleted → sessions removed |
| Booking → Session | `cascadeOnDelete` | Session deleted → bookings removed |
| Booking → Athlete (user) | `restrictOnDelete` | Cannot delete user with active bookings |
| Invoice → Booking | `restrictOnDelete` | Cannot delete booking with invoices |
| Recurring → Parent session | `nullOnDelete` | Parent deleted → children become standalone |

Rules:
- Always specify the on-delete behavior explicitly — never rely on database defaults
- Use `restrictOnDelete()` when child records have financial or legal significance (invoices, payments)
- Use `cascadeOnDelete()` for dependent data that has no standalone meaning
- Use `nullOnDelete()` for soft references (parent session templates)
- Self-referencing FKs (e.g., `parent_session_id`) must be nullable:

```php
$table->foreignId('parent_session_id')->nullable()->constrained('sessions')->nullOnDelete();
```

## Indexes

### When to Index

- All foreign key columns (Laravel adds these automatically with `foreignId()->constrained()`)
- Columns used in `WHERE` clauses for common queries
- Columns used in `ORDER BY` for paginated listings
- Columns used in unique constraints

### Composite Indexes

Place the higher-cardinality column first:

```php
// Booking lookup: one athlete per session
$table->unique(['session_id', 'athlete_id']);

// Session listing: filter by status, sort by date
$table->index(['status', 'date']);

// Geo-filtering by postal code and status
$table->index(['postal_code', 'status']);
```

### Naming

Let Laravel auto-generate index names. Only name explicitly when the auto-generated name exceeds 64 characters:

```php
// Auto-named (preferred)
$table->index(['status', 'date']);

// Explicit name (only when necessary)
$table->index(['status', 'date', 'postal_code'], 'sessions_status_date_postal_idx');
```

### Webhook Idempotency

The `processed_webhooks` table needs a unique index on the Stripe event ID:

```php
$table->string('stripe_event_id')->unique();
```

## SQLite / MySQL Compatibility

Dev and test use SQLite; production uses MySQL. Migrations must work on both:

| Do | Don't |
|----|-------|
| `$table->string('role')` | `$table->enum('role', [...])` |
| `$table->text('description')` | `$table->mediumText()` (SQLite ignores length) |
| `$table->unsignedInteger('amount')` | MySQL-specific column modifiers |
| `$table->timestamp()` | `$table->dateTime()` with MySQL-specific defaults |
| `$table->json('metadata')` | Rely on MySQL JSON functions in migrations |

Rules:
- Never use MySQL-specific SQL in migrations (e.g., `DB::statement('ALTER TABLE ...')`)
- Never use `$table->enum()` — use `$table->string()` instead
- Test migrations against SQLite locally: `php artisan migrate:fresh --database=sqlite`
- If a feature genuinely requires MySQL-only syntax, gate it behind a config check and document the decision in `doc/Decisions.md`

## Table Reference — Core Schema

These are the core tables the Motivya migration set must define. Use as a reference when creating migrations:

| Table | Key columns | Notes |
|-------|-------------|-------|
| `users` | `role`, `enterprise_number`, `is_vat_subject`, `is_approved`, `stripe_account_id`, `locale` | Single table, 4 roles |
| `sessions` | `coach_id`, `status`, `date`, `start_time`, `end_time`, `price_per_person`, `min_participants`, `max_participants`, `current_participants`, `postal_code`, `parent_session_id` | Session lifecycle |
| `bookings` | `session_id`, `athlete_id`, `status`, `payment_intent_id`, `cancelled_at`, `refund_id` | Unique on (`session_id`, `athlete_id`) |
| `invoices` | `booking_id`, `type` (`invoice`/`credit_note`), `number`, `xml_path`, `total_excl_vat`, `vat_amount`, `total_incl_vat` | PEPPOL BIS 3.0 |
| `processed_webhooks` | `stripe_event_id`, `event_type`, `processed_at` | Idempotency guard |
| `activity_types` | `name`, `slug`, `cover_image_path` | Admin-managed |
| `favourites` | `user_id`, `session_id` | Athlete favourites |

## Seeder Conventions

- Keep production seeders minimal — only admin user and activity types
- Use factories for all test data — never insert test data in seeders
- Factory definitions live in `database/factories/` and must use correct enum values and cent amounts
- Seeders must be idempotent — use `firstOrCreate()` for reference data
