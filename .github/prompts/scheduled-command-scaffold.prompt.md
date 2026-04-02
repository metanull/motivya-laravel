---
description: "Scaffold a Laravel Artisan command for scheduled tasks: session reminders, threshold checks, auto-cancellation, session completion, or any recurring job. Generates the command class, registers it in the scheduler, and creates a Pest test."
argument-hint: "Task name, e.g. 'session reminders 24h before', 'cancel sessions past deadline', 'complete finished sessions'"
agent: "agent"
tools: [read, edit, search, execute]
---

# Scheduled Command Scaffold

Generate a Laravel Artisan console command for a scheduled/recurring task in the Motivya project.

## Before Writing

1. Read [domain-concepts.instructions.md](../instructions/domain-concepts.instructions.md) — understand session/booking state machines and business rules.
2. Read [notification-system.instructions.md](../instructions/notification-system.instructions.md) — scheduled commands dispatch Events, never send notifications directly.
3. Read [session-booking.instructions.md](../instructions/session-booking.instructions.md) — for threshold, cancellation, and completion rules.
4. Read [php.instructions.md](../instructions/php.instructions.md) — for strict types, code style, and Laravel conventions.
5. Read [testing-conventions.instructions.md](../instructions/testing-conventions.instructions.md) — for Pest test structure.
6. Search `app/Console/Commands/` to check if a similar command already exists — update rather than duplicate.
7. Search `routes/console.php` to see existing scheduler registrations.

## Input

The user describes the scheduled task in natural language. Common Motivya tasks:

| Task | Schedule | What it does |
|------|----------|-------------|
| Session reminders | Hourly | Find confirmed sessions starting within N hours, dispatch `SessionReminder` event for each |
| Threshold cancellation | Every 15 min | Find published sessions past their deadline with `current_participants < min_participants`, transition to `cancelled`, dispatch `SessionCancelled` |
| Session completion | Hourly | Find confirmed sessions where `end_time` has passed, transition to `completed`, dispatch `SessionCompleted` |
| Coach payout trigger | Daily | Find completed sessions awaiting payout, dispatch `CoachPayoutProcessed` |

If the task doesn't match these patterns, infer the appropriate logic from the domain rules.

## Output Structure

Generate exactly **3 files**:

### 1. Command Class — `app/Console/Commands/{Name}.php`

```php
<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

class {Name} extends Command
{
    protected $signature = 'motivya:{kebab-name} {--dry-run : List affected records without making changes}';
    protected $description = '{One-line description}';

    public function handle(): int
    {
        // 1. Query for eligible records using Eloquent scopes
        // 2. Loop and process each record
        // 3. Dispatch events (never send notifications directly)
        // 4. Log results via $this->info() / $this->warn()
        // 5. Return self::SUCCESS or self::FAILURE

        return self::SUCCESS;
    }
}
```

### 2. Scheduler Registration — `routes/console.php`

Append the schedule entry. Use the appropriate frequency:

```php
Schedule::command('motivya:{kebab-name}')->everyFifteenMinutes()->withoutOverlapping();
```

### 3. Pest Test — `tests/Feature/Commands/{Name}Test.php`

```php
<?php

declare(strict_types=1);

use App\Models\Session;
// ... other imports

it('processes eligible records', function () {
    // Arrange: create records matching the criteria
    // Act: run the command
    $this->artisan('motivya:{kebab-name}')->assertSuccessful();
    // Assert: verify state transitions, events dispatched
});

it('skips ineligible records', function () {
    // Arrange: create records that should NOT be processed
    // Act: run the command
    // Assert: records unchanged
});

it('supports dry-run mode', function () {
    // Arrange
    $this->artisan('motivya:{kebab-name} --dry-run')->assertSuccessful();
    // Assert: no state changes, only output
});
```

## Rules

- **Prefix**: All command signatures start with `motivya:` (e.g., `motivya:send-reminders`, `motivya:cancel-expired-sessions`)
- **Dry-run**: Every command MUST have a `--dry-run` option that logs what would happen without making changes
- **Idempotent**: Running the command twice on the same data must produce the same result (no double-processing)
- **Events, not notifications**: Commands dispatch domain Events. Listeners handle notifications. Never `Notification::send()` in a command.
- **Scopes**: Use Eloquent query scopes on models to encapsulate the filtering logic (e.g., `Session::pastDeadline()->belowThreshold()`)
- **Locking**: Use `->withoutOverlapping()` on the scheduler to prevent concurrent runs
- **Logging**: Use `$this->info()` for success, `$this->warn()` for skipped, `$this->error()` for failures. Include counts.
- **Transactions**: If the command modifies multiple related records, wrap in `DB::transaction()`
- **State transitions**: Call the appropriate Service class method — never update `status` directly in the command
- **Time handling**: Use `Carbon::now()` (or `now()`) and compare against session fields. Respect the `Europe/Brussels` timezone.
- **No hardcoded text**: If the command produces user-facing output beyond CLI logging, use `__()` for translations

## Validation

After generating:
- [ ] Command class has `declare(strict_types=1)`
- [ ] Signature uses `motivya:` prefix
- [ ] `--dry-run` option is present and functional
- [ ] Events are dispatched, not notifications
- [ ] State changes go through Service classes
- [ ] Scheduler entry uses `withoutOverlapping()`
- [ ] Pest test covers: eligible records, ineligible records, dry-run mode
- [ ] No direct `$model->update(['status' => ...])` — uses service method
