---
description: "Generate an Event class, Listener class, Notification class with localized mail/database channels, translation keys for all 3 locales, EventServiceProvider registration, and Pest tests from an event name or trigger description."
argument-hint: "Event name or trigger, e.g. 'SessionConfirmed' or 'when athlete cancels a booking'"
agent: "agent"
tools: [read, edit, search, execute]
---

# Event-Listener Scaffold

Generate the full event-driven notification chain for a Motivya domain event: Event → Listener → Notification → translations → tests.

## Before Writing

1. Read [notification-system.instructions.md](../instructions/notification-system.instructions.md) for the event catalog, listener mapping, channel rules, notification class pattern, and localization conventions.
2. Read [php.instructions.md](../instructions/php.instructions.md) for strict types and `final class`.
3. Read [i18n-localization.instructions.md](../instructions/i18n-localization.instructions.md) for translation key format and locale rules.
4. Read [testing-conventions.instructions.md](../instructions/testing-conventions.instructions.md) for Pest patterns and factory usage.
5. Search `app/Events/` to check if the event already exists — update rather than duplicate.
6. Search `app/Listeners/` and `app/Notifications/` for existing classes in the same domain.
7. Search `lang/fr/notifications.php` for existing translation keys to avoid conflicts.

## Input

The user provides one of:
- An event name: `SessionConfirmed`, `BookingCancelled`, `CoachApproved`
- A trigger description: "when an athlete cancels a booking", "when session reaches min participants"
- A domain: "booking notifications" or "coach payout"

If the event is in the event catalog (notification-system instruction), use the documented mapping. Otherwise, infer the listener → notification chain and ask at most 1 question about which roles receive the notification.

## Event Catalog Quick Reference

| Event | Recipients | Channels |
|-------|-----------|----------|
| `SessionConfirmed` | All booked athletes + coach | mail, database |
| `SessionCancelled` | All booked athletes + coach | mail, database |
| `SessionReminder` | All confirmed athletes | mail, database |
| `BookingCreated` | Athlete (confirmed) + Coach (new booking) | mail, database |
| `BookingCancelled` | Athlete | mail, database |
| `BookingRefunded` | Athlete | mail, database |
| `CoachApproved` | Coach | mail, database |
| `CoachRejected` | Coach | mail, database |
| `CoachPayoutProcessed` | Coach | mail, database |
| `NewCoachApplication` | All admins | mail, database |
| `PaymentAnomaly` | All admins | mail, database |

## Generation Rules

### 1. Event Class (`app/Events/{Name}.php`)

```php
<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

final class SessionConfirmed implements ShouldQueue
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly int $sessionId,
    ) {}
}
```

Rules:
- `declare(strict_types=1)` and `final class`
- Implement `ShouldQueue` by default — only remove if the flow is user-blocking
- Constructor accepts **IDs and scalars only** — not full model instances
- Property naming: `$sessionId`, `$bookingId`, `$coachId` (camelCase with `Id` suffix)
- Use `Dispatchable` and `SerializesModels` traits
- Do NOT implement `ShouldBroadcast` unless real-time push is explicitly needed

### 2. Listener Class (`app/Listeners/{ListenerName}.php`)

```php
<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\SessionConfirmed;
use App\Models\Session;
use App\Notifications\SessionConfirmedNotification;

final class SendSessionConfirmedNotifications
{
    public function handle(SessionConfirmed $event): void
    {
        $session = Session::with(['coach', 'bookings.athlete'])->findOrFail($event->sessionId);

        // Notify all booked athletes
        $session->bookings->each(function ($booking) use ($session): void {
            $booking->athlete->notify(
                (new SessionConfirmedNotification($session->id))
                    ->locale($booking->athlete->locale ?? 'fr')
            );
        });

        // Notify the coach
        $session->coach->notify(
            (new SessionConfirmedNotification($session->id))
                ->locale($session->coach->locale ?? 'fr')
        );
    }
}
```

Rules:
- `declare(strict_types=1)` and `final class`
- Naming: `Send{Domain}{Action}Notifications` or `Send{Action}Notification`
- `handle()` accepts the typed Event — loads models via eager loading
- Sends notifications using `$notifiable->notify()` — never `Notification::send()` in bulk unless sending to a collection
- **Always** set locale via `->locale($notifiable->locale ?? 'fr')` — French fallback
- One listener per event — if an event triggers notifications to multiple distinct groups (athletes vs coach), the single listener handles both
- Do NOT put business logic in listeners — dispatching events was the service's job, the listener only sends notifications

### 3. Notification Class (`app/Notifications/{Name}Notification.php`)

```php
<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Session;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class SessionConfirmedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly int $sessionId,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $session = Session::findOrFail($this->sessionId);

        return (new MailMessage())
            ->subject(__('notifications.session_confirmed_subject'))
            ->greeting(__('notifications.greeting', ['name' => $notifiable->first_name]))
            ->line(__('notifications.session_confirmed_body', [
                'activity' => $session->activity_type,
                'date' => $session->date->translatedFormat('l j F Y'),
                'time' => $session->start_time,
            ]))
            ->action(__('notifications.view_session'), route('sessions.show', $session))
            ->line(__('notifications.thanks'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'session_id' => $this->sessionId,
            'type' => 'session_confirmed',
        ];
    }
}
```

Rules:
- `declare(strict_types=1)` and `final class`
- Implement `ShouldQueue` with `Queueable` trait
- Constructor accepts IDs — load models inside `toMail()` / `toArray()` (keeps serialized payload small)
- `via()` returns channels per the channel selection table — athletes and coaches get `['mail', 'database']`, accountants get `['mail']`
- `toMail()` uses `MailMessage` builder with `__()` localization for every string
- Dates via `translatedFormat()` — never `format()`
- `toArray()` must include a `type` key — used by the in-app notification feed
- One notification per distinct message — no conditionals switching content

### 4. Translation Keys (`lang/{locale}/notifications.php`)

Add new keys to all 3 locale files. Follow the existing key naming pattern:

```php
// lang/fr/notifications.php — add these keys
'session_confirmed_subject' => 'Séance confirmée',
'session_confirmed_body'    => 'La séance :activity du :date à :time est confirmée !',

// lang/en/notifications.php
'session_confirmed_subject' => 'Session confirmed',
'session_confirmed_body'    => 'The :activity session on :date at :time is confirmed!',

// lang/nl/notifications.php
'session_confirmed_subject' => 'Sessie bevestigd',
'session_confirmed_body'    => 'De sessie :activity op :date om :time is bevestigd!',
```

Rules:
- All 3 locales updated in the same pass — never leave a locale incomplete
- Keys use `snake_case` with `_subject` and `_body` suffixes
- Use `:parameter` placeholders — never concatenate
- Provide natural, localized text — not machine-translated
- Reuse shared keys (`greeting`, `thanks`, `view_session`) — do not duplicate

### 5. EventServiceProvider Registration

Add the event → listener mapping:

```php
// app/Providers/EventServiceProvider.php — $listen array
SessionConfirmed::class => [
    SendSessionConfirmedNotifications::class,
],
```

If the file uses event discovery, note this in a comment but still register explicitly for production reliability.

### 6. Pest Tests (`tests/Feature/Notifications/{Name}Test.php`)

```php
<?php

declare(strict_types=1);

use App\Events\SessionConfirmed;
use App\Listeners\SendSessionConfirmedNotifications;
use App\Models\Booking;
use App\Models\Session;
use App\Models\User;
use App\Notifications\SessionConfirmedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

describe('SessionConfirmed notification chain', function () {

    describe('event dispatch', function () {
        it('dispatches SessionConfirmed event when threshold met', function () {
            Event::fake([SessionConfirmed::class]);

            // ... trigger action that dispatches the event

            Event::assertDispatched(SessionConfirmed::class, fn ($e) =>
                $e->sessionId === $session->id
            );
        });
    });

    describe('listener', function () {
        it('sends notification to all booked athletes and coach', function () {
            Notification::fake();

            $coach = User::factory()->coach()->create();
            $session = Session::factory()
                ->for($coach, 'coach')
                ->confirmed()
                ->create();
            $athletes = User::factory()->athlete()->count(3)->create();
            $athletes->each(fn ($a) => Booking::factory()
                ->for($session)
                ->for($a, 'athlete')
                ->confirmed()
                ->create()
            );

            $listener = new SendSessionConfirmedNotifications();
            $listener->handle(new SessionConfirmed($session->id));

            Notification::assertSentTo($athletes, SessionConfirmedNotification::class);
            Notification::assertSentTo($coach, SessionConfirmedNotification::class);
        });

        it('does not notify athletes of other sessions', function () {
            Notification::fake();

            $session = Session::factory()->confirmed()->create();
            $otherAthlete = User::factory()->athlete()->create();

            $listener = new SendSessionConfirmedNotifications();
            $listener->handle(new SessionConfirmed($session->id));

            Notification::assertNotSentTo($otherAthlete, SessionConfirmedNotification::class);
        });
    });

    describe('notification content', function () {
        it('uses correct French subject', function () {
            $session = Session::factory()->confirmed()->create();
            $athlete = User::factory()->athlete()->create(['locale' => 'fr']);

            $notification = new SessionConfirmedNotification($session->id);
            $mail = $notification->toMail($athlete);

            expect($mail->subject)->toBe('Séance confirmée');
        });

        it('uses correct English subject when locale is en', function () {
            $session = Session::factory()->confirmed()->create();
            $athlete = User::factory()->athlete()->create(['locale' => 'en']);

            app()->setLocale('en');
            $notification = new SessionConfirmedNotification($session->id);
            $mail = $notification->toMail($athlete);

            expect($mail->subject)->toBe('Session confirmed');
        });

        it('stores correct database payload', function () {
            $session = Session::factory()->confirmed()->create();
            $athlete = User::factory()->athlete()->create();

            $notification = new SessionConfirmedNotification($session->id);
            $data = $notification->toArray($athlete);

            expect($data)
                ->toHaveKey('session_id', $session->id)
                ->toHaveKey('type', 'session_confirmed');
        });
    });

    describe('queue', function () {
        it('implements ShouldQueue', function () {
            expect(SessionConfirmedNotification::class)
                ->toImplement(\Illuminate\Contracts\Queue\ShouldQueue::class);
        });
    });
});
```

**Test categories:**

| Category | What to test |
|----------|-------------|
| Event dispatch | Correct event fired by the service with expected payload |
| Correct recipients | Notification sent to athletes + coach, NOT to unrelated users |
| No false positives | Notification NOT sent when conditions aren't met |
| Locale content | French, English, and Dutch subject/body assertions |
| Database payload | `toArray()` returns expected keys and `type` |
| Queue contract | Notification implements `ShouldQueue` |

## Output Order

1. **Event class** — `app/Events/{Name}.php`
2. **Listener class** — `app/Listeners/Send{Name}Notifications.php`
3. **Notification class** — `app/Notifications/{Name}Notification.php`
4. **Translation keys** — `lang/fr/`, `lang/en/`, `lang/nl/` updates to `notifications.php`
5. **EventServiceProvider** — registration in `$listen` array
6. **Pest tests** — `tests/Feature/Notifications/{Name}Test.php`

## Validation

After generating, verify:
- `declare(strict_types=1)` on all files
- `final class` on event, listener, and notification
- Event carries only IDs — no model instances in constructor
- Listener loads models via eager loading — not N+1 queries
- Listener sets `->locale($notifiable->locale ?? 'fr')` on every notification
- Notification uses `__()` for all user-facing strings — no hardcoded text
- Notification uses `translatedFormat()` for dates — never `format()`
- `toArray()` includes a `type` key
- All 3 locale files updated with matching keys
- Translation keys use `:parameter` placeholders — no concatenation
- EventServiceProvider maps event → listener
- Tests cover all 6 categories: dispatch, recipients, false positives, locale, payload, queue
