---
description: "Generate Laravel Form Request classes with validation rules, authorization delegation to policies, localized error messages, and Pest validation tests from a model or action description."
argument-hint: "Action + model, e.g. 'StoreSession' or 'UpdateBooking' or 'session creation'"
agent: "agent"
tools: [read, edit, search, execute]
---

# Form Request Scaffold

Generate Form Request classes for the Motivya project with validation rules derived from the model schema, authorization via policies, and localized error messages.

## Before Writing

1. Read [php.instructions.md](../instructions/php.instructions.md) for strict types, `final class`, and form request conventions.
2. Read [database-migrations.instructions.md](../instructions/database-migrations.instructions.md) for the core schema — column types drive validation rules.
3. Read [auth-roles.instructions.md](../instructions/auth-roles.instructions.md) for the role enum and policy delegation.
4. Read [i18n-localization.instructions.md](../instructions/i18n-localization.instructions.md) for localized error message conventions.
5. Read [testing-conventions.instructions.md](../instructions/testing-conventions.instructions.md) for Pest test structure and factory patterns.
6. Search `app/Models/` for the target model — use `$fillable` and `$casts` to derive rules.
7. Search `database/migrations/` for the table definition — column constraints map to validation rules.
8. Search `app/Enums/` for backed enums referenced by the model.
9. Search `app/Http/Requests/` to check if the form request exists. Update rather than overwrite.

## Input

The user provides one of:
- An action + model: `StoreSession`, `UpdateBooking`, `CancelSession`
- A plain description: "session creation form request" or "booking cancellation"
- A model name only: `Session` — generate both `Store` and `Update` requests

If the user provides just a model name, generate **both** `Store{Model}Request` and `Update{Model}Request`.

## Column → Validation Rule Mapping

Derive rules from the migration column definitions:

| Column type/pattern | Validation rules |
|---------------------|-----------------|
| `string('title')` | `['required', 'string', 'max:255']` |
| `text('description')` | `['nullable', 'string', 'max:5000']` |
| `unsignedInteger('price_*')` | `['required', 'integer', 'min:0']` |
| `string('status')` with enum | `['required', Rule::enum(SessionStatus::class)]` |
| `string('role')` with enum | `['required', Rule::enum(UserRole::class)]` |
| `date('date')` | `['required', 'date', 'after:today']` (Store) or `['required', 'date']` (Update) |
| `time('start_time')` | `['required', 'date_format:H:i']` |
| `time('end_time')` | `['required', 'date_format:H:i', 'after:start_time']` |
| `boolean('is_*')` | `['required', 'boolean']` |
| `foreignId('*_id')` | `['required', 'exists:table,id']` |
| `string('postal_code')` | `['required', 'string', 'regex:/^[1-9]\d{3}$/']` (Belgian 4-digit) |
| `string('email')` | `['required', 'email:rfc,dns', 'max:255']` |
| `string('enterprise_number')` | `['required', 'string', 'regex:/^(BE)?0\d{9}$/']` |
| `integer('min_participants')` | `['required', 'integer', 'min:1']` |
| `integer('max_participants')` | `['required', 'integer', 'min:1', 'gte:min_participants']` |
| nullable column | Replace `'required'` with `'nullable'` |

### Store vs Update Differences

| Aspect | Store | Update |
|--------|-------|--------|
| Required fields | Everything that has no DB default | Only submitted fields (use `'sometimes'`) |
| Unique rules | `Rule::unique('table')` | `Rule::unique('table')->ignore($this->route('model'))` |
| Date fields | `'after:today'` for future-only | `'date'` (may already be past for edits) |
| Foreign keys | `'required', 'exists:...'` | `'sometimes', 'exists:...'` |

## Generation Rules

### 1. Form Request Class (`app/Http/Requests/{Action}{Model}Request.php`)

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Delegate to policy — preferred pattern
        return $this->user()->can('create', Session::class);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'activity_type'    => ['required', 'string', 'exists:activity_types,slug'],
            'level'            => ['required', Rule::enum(SessionLevel::class)],
            'location'         => ['required', 'string', 'max:255'],
            'postal_code'      => ['required', 'string', 'regex:/^[1-9]\d{3}$/'],
            'date'             => ['required', 'date', 'after:today'],
            'start_time'       => ['required', 'date_format:H:i'],
            'end_time'         => ['required', 'date_format:H:i', 'after:start_time'],
            'price_per_person' => ['required', 'integer', 'min:0'],
            'min_participants' => ['required', 'integer', 'min:1'],
            'max_participants' => ['required', 'integer', 'min:1', 'gte:min_participants'],
            'description'      => ['nullable', 'string', 'max:5000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'postal_code.regex'       => __('validation.custom.postal_code.regex'),
            'end_time.after'          => __('validation.custom.end_time.after_start'),
            'max_participants.gte'    => __('validation.custom.max_participants.gte_min'),
            'date.after'              => __('validation.custom.date.future_only'),
            'price_per_person.min'    => __('validation.custom.price.non_negative'),
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'activity_type'    => __('validation.attributes.activity_type'),
            'postal_code'      => __('validation.attributes.postal_code'),
            'price_per_person' => __('validation.attributes.price_per_person'),
            'min_participants' => __('validation.attributes.min_participants'),
            'max_participants' => __('validation.attributes.max_participants'),
            'start_time'       => __('validation.attributes.start_time'),
            'end_time'         => __('validation.attributes.end_time'),
        ];
    }
}
```

**Class rules:**
- `declare(strict_types=1)` always
- `final class` — form requests are not extended
- `authorize()` delegates to the Policy via `$this->user()->can()` — never inline role checks
- `rules()` returns fully typed array — every rule as an array, never pipe-delimited strings
- `messages()` returns localized overrides for custom rules — keys follow `field.rule` pattern
- `attributes()` returns localized attribute names for cleaner error messages

**Authorization patterns:**

```php
// For model creation — check class-level ability
public function authorize(): bool
{
    return $this->user()->can('create', Session::class);
}

// For model update — check instance-level ability
public function authorize(): bool
{
    return $this->user()->can('update', $this->route('session'));
}

// When authorization is handled at route middleware level
public function authorize(): bool
{
    return true; // Middleware already enforced role
}
```

**Validation patterns for Motivya-specific fields:**

```php
// Belgian enterprise number (10 digits, optional BE prefix)
'enterprise_number' => ['required', 'string', 'regex:/^(BE)?0\d{9}$/'],

// Belgian postal code (4 digits, 1000-9999)
'postal_code' => ['required', 'string', 'regex:/^[1-9]\d{3}$/'],

// Money in cents — always integer, never float
'price_per_person' => ['required', 'integer', 'min:0'],

// Cross-field: max ≥ min
'max_participants' => ['required', 'integer', 'min:1', 'gte:min_participants'],

// Backed enum validation
'status' => ['required', Rule::enum(SessionStatus::class)],
'role'   => ['required', Rule::enum(UserRole::class)],

// Time ordering: end must be after start
'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
```

### 2. Translation File Updates

Add custom validation messages to `lang/{locale}/validation.php`:

```php
// lang/fr/validation.php — add to 'custom' key
'custom' => [
    'postal_code' => [
        'regex' => 'Le code postal doit être un code belge valide (4 chiffres).',
    ],
    'end_time' => [
        'after_start' => "L'heure de fin doit être postérieure à l'heure de début.",
    ],
    'max_participants' => [
        'gte_min' => 'Le nombre maximum doit être supérieur ou égal au minimum.',
    ],
    'date' => [
        'future_only' => 'La date doit être dans le futur.',
    ],
    'price' => [
        'non_negative' => 'Le prix ne peut pas être négatif.',
    ],
],
```

Provide translations for all 3 locales (`fr`, `en`, `nl`).

### 3. Pest Tests (`tests/Feature/Requests/{Action}{Model}RequestTest.php`)

```php
<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Session;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('StoreSessionRequest', function () {

    describe('authorization', function () {
        it('allows coach to create a session', function () {
            $coach = User::factory()->coach()->create();

            $this->actingAs($coach)
                ->postJson(route('coach.sessions.store'), validSessionData())
                ->assertStatus(201);
        });

        it('forbids athlete from creating a session', function () {
            $athlete = User::factory()->athlete()->create();

            $this->actingAs($athlete)
                ->postJson(route('coach.sessions.store'), validSessionData())
                ->assertForbidden();
        });
    });

    describe('validation', function () {
        beforeEach(function () {
            $this->coach = User::factory()->coach()->create();
        });

        it('requires all mandatory fields', function () {
            $this->actingAs($this->coach)
                ->postJson(route('coach.sessions.store'), [])
                ->assertUnprocessable()
                ->assertJsonValidationErrors([
                    'activity_type',
                    'date',
                    'start_time',
                    'end_time',
                    'price_per_person',
                    'min_participants',
                    'max_participants',
                ]);
        });

        it('rejects invalid Belgian postal code', function () {
            $this->actingAs($this->coach)
                ->postJson(route('coach.sessions.store'), [
                    ...validSessionData(),
                    'postal_code' => '999',
                ])
                ->assertJsonValidationErrors(['postal_code']);
        });

        it('rejects end_time before start_time', function () {
            $this->actingAs($this->coach)
                ->postJson(route('coach.sessions.store'), [
                    ...validSessionData(),
                    'start_time' => '14:00',
                    'end_time' => '13:00',
                ])
                ->assertJsonValidationErrors(['end_time']);
        });

        it('rejects max_participants less than min_participants', function () {
            $this->actingAs($this->coach)
                ->postJson(route('coach.sessions.store'), [
                    ...validSessionData(),
                    'min_participants' => 5,
                    'max_participants' => 3,
                ])
                ->assertJsonValidationErrors(['max_participants']);
        });

        it('rejects negative price', function () {
            $this->actingAs($this->coach)
                ->postJson(route('coach.sessions.store'), [
                    ...validSessionData(),
                    'price_per_person' => -100,
                ])
                ->assertJsonValidationErrors(['price_per_person']);
        });

        it('rejects past date', function () {
            $this->actingAs($this->coach)
                ->postJson(route('coach.sessions.store'), [
                    ...validSessionData(),
                    'date' => '2020-01-01',
                ])
                ->assertJsonValidationErrors(['date']);
        });
    });
});

function validSessionData(): array
{
    return [
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
    ];
}
```

**Test categories:**

| Category | What to test |
|----------|-------------|
| Authorization | Correct role accepted, wrong role rejected (via `authorize()`) |
| Required fields | Submit empty payload — all required fields flagged |
| Type validation | Wrong types (string for integer, etc.) rejected |
| Format validation | Regex fields (postal code, enterprise number) with invalid format |
| Cross-field rules | `end_time > start_time`, `max >= min` |
| Boundary values | Zero price, min=1, past dates, future dates |
| Enum validation | Invalid enum values rejected, valid values accepted |

**Test conventions:**
- Use `assertJsonValidationErrors()` for field-specific assertions
- Use a `validData()` helper function returning a complete valid payload — override individual fields per test
- Use `postJson` / `putJson` — never `post` with form data for API-style testing
- `beforeEach` with `actingAs(coach)` for validation-focused tests

## Output Order

1. **Store Form Request** — `app/Http/Requests/Store{Model}Request.php`
2. **Update Form Request** — `app/Http/Requests/Update{Model}Request.php` (if generating both)
3. **Translation updates** — custom messages for `lang/fr/`, `lang/en/`, `lang/nl/`
4. **Pest tests** — `tests/Feature/Requests/{Action}{Model}RequestTest.php`

## Validation

After generating, verify:
- `declare(strict_types=1)` on all files
- `final class` on form request classes
- `authorize()` delegates to Policy — no inline role string checks
- `rules()` uses array syntax, not pipe-delimited strings
- Money fields validate as `integer`, not `numeric` or `decimal`
- Enum fields use `Rule::enum()` — not `Rule::in()`
- Cross-field rules present: `gte:min_participants`, `after:start_time`
- Belgian-specific regex patterns: postal code `^[1-9]\d{3}$`, enterprise number `^(BE)?0\d{9}$`
- `messages()` uses `__()` localization — no hardcoded strings
- `attributes()` maps field names to localized labels
- Update request uses `'sometimes'` instead of `'required'` for optional fields
- Unique rules include `->ignore()` in update requests
- Tests use a `validData()` helper overriding one field per test
