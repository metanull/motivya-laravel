---
description: "Use when implementing notifications, email templates, push notifications, event-listener wiring, reminder scheduling, session confirmation alerts, booking notifications, coach KYC status updates, or localized notification content. Covers the event-driven notification architecture, channel selection, and tri-lingual content (fr/en/nl)."
applyTo: "app/Notifications/*,app/Listeners/*,app/Events/*,app/Mail/*"
---
# Notification System Rules

## Architecture

All notifications are **event-driven**. User-facing side effects (emails, push, in-app) are triggered by dispatching Events, never called directly from controllers, services, or Livewire components.

```
Service → dispatch Event → Listener → send Notification
```

### Rules

- **Never** send a notification directly from a controller or service class — always dispatch an event
- **Never** hardcode user-facing text — all strings come from `lang/{locale}/notifications.php`
- One Notification class per distinct message — no multipurpose notifications with conditionals
- One Listener per Event-Notification pairing — keep listeners single-responsibility
- Events carry only IDs and scalar data — listeners load models as needed
- Events MUST implement `ShouldBroadcast` only when real-time push is needed; default to queued

## Event Catalog

Every event dispatched in the system must map to this catalog. Add new events here when extending functionality.

### Session Events

| Event | Dispatched When | Triggered By |
|-------|----------------|--------------|
| `SessionConfirmed` | `current_participants` reaches `min_participants` | BookingService |
| `SessionCancelled` | Deadline passed without threshold, or coach/admin cancels | ScheduledCommand / SessionService |
| `SessionCompleted` | Session `end_time` passed | ScheduledCommand / SessionService |
| `SessionReminder` | Scheduled time before session start | ScheduledCommand |

### Booking Events

| Event | Dispatched When | Triggered By |
|-------|----------------|--------------|
| `BookingCreated` | Athlete books and payment succeeds | BookingService (via `payment_intent.succeeded` webhook) |
| `BookingCancelled` | Athlete cancels within allowed window | BookingService |
| `BookingRefunded` | Stripe refund processed | Stripe webhook listener |

### Coach Events

| Event | Dispatched When | Triggered By |
|-------|----------------|--------------|
| `CoachApproved` | Admin approves KYC application | AdminService |
| `CoachRejected` | Admin rejects KYC application | AdminService |
| `CoachPayoutProcessed` | Stripe transfer to coach completed | Stripe webhook listener |
| `CoachStripeOnboardingComplete` | Coach finishes Stripe Express onboarding | Stripe webhook listener |

### Admin Events

| Event | Dispatched When | Triggered By |
|-------|----------------|--------------|
| `NewCoachApplication` | Coach submits KYC application | CoachApplicationService |
| `PaymentAnomaly` | Stripe webhook indicates dispute or failure | Stripe webhook listener |

## Event Class Structure

```php
class SessionConfirmed implements ShouldQueue
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $sessionId,
    ) {}
}
```

### Rules

- Events MUST use `ShouldQueue` (default) — only use synchronous dispatch for critical user-blocking flows
- Events carry the minimal data needed (IDs, not full models) — except when `SerializesModels` is appropriate
- Event property names use snake_case for IDs (`$sessionId`, `$athleteId`)
- Events live in `app/Events/` — one file per event

## Listener → Notification Mapping

Each listener handles exactly one event and sends one or more notifications:

| Listener | Event | Notifications Sent |
|----------|-------|--------------------|
| `SendSessionConfirmedNotifications` | `SessionConfirmed` | `SessionConfirmedNotification` → all booked athletes + coach |
| `SendSessionCancelledNotifications` | `SessionCancelled` | `SessionCancelledNotification` → all booked athletes + coach |
| `SendSessionReminderNotifications` | `SessionReminder` | `SessionReminderNotification` → all confirmed athletes |
| `SendBookingConfirmation` | `BookingCreated` | `BookingConfirmedNotification` → athlete, `NewBookingNotification` → coach |
| `SendBookingCancellation` | `BookingCancelled` | `BookingCancelledNotification` → athlete |
| `SendRefundNotification` | `BookingRefunded` | `BookingRefundedNotification` → athlete |
| `SendCoachApprovalNotification` | `CoachApproved` | `CoachApprovedNotification` → coach |
| `SendCoachRejectionNotification` | `CoachRejected` | `CoachRejectedNotification` → coach |
| `SendPayoutNotification` | `CoachPayoutProcessed` | `PayoutProcessedNotification` → coach |
| `NotifyAdminNewApplication` | `NewCoachApplication` | `NewCoachApplicationNotification` → all admins |

### Listener Registration

Register in `EventServiceProvider` using the `$listen` array — never use event discovery for production:

```php
protected $listen = [
    SessionConfirmed::class => [
        SendSessionConfirmedNotifications::class,
    ],
    BookingCreated::class => [
        SendBookingConfirmation::class,
    ],
    // ...
];
```

## Notification Channels

### Channel Selection by Role

| Recipient Role | Default Channels | Notes |
|----------------|-----------------|-------|
| Athlete | `mail`, `database` | `database` for in-app notification center |
| Coach | `mail`, `database` | Financial notifications always include email |
| Admin | `mail`, `database` | Payment anomalies also via `mail` immediately |
| Accountant | `mail` | No in-app — accountant accesses via export |

### Channel Rules

- **Email** (`mail`): Always available. Use Laravel's `MailMessage` builder with Blade markdown templates
- **Database** (`database`): For in-app notification feed. Store via Laravel's `notifications` table
- **Broadcast** (`broadcast`): Only for real-time UI updates. Never use for critical notifications — always pair with `database` or `mail`
- All notifications implement `ShouldQueue` — never send synchronously unless blocking the user is required

### Per-User Preferences (future)

Design notifications so the channel list comes from `$notifiable->preferredChannels($notification)` pattern. For MVP, hardcode the defaults above. Never remove `mail` from financial notifications regardless of user preference.

## Notification Class Pattern

```php
class BookingConfirmedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly int $bookingId,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $booking = Booking::with('session')->findOrFail($this->bookingId);

        return (new MailMessage)
            ->subject(__('notifications.booking_confirmed_subject'))
            ->greeting(__('notifications.greeting', ['name' => $notifiable->first_name]))
            ->line(__('notifications.booking_confirmed_body', [
                'activity' => $booking->session->activity_type,
                'date'     => $booking->session->start_datetime->translatedFormat('l j F Y'),
                'time'     => $booking->session->start_datetime->format('H:i'),
            ]))
            ->action(__('notifications.view_booking'), route('bookings.show', $booking))
            ->line(__('notifications.thanks'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'booking_id' => $this->bookingId,
            'type'       => 'booking_confirmed',
        ];
    }
}
```

### Rules

- **Always** use `__('notifications.key')` for all user-facing strings — never inline text
- **Always** use `translatedFormat()` for dates — never `format()` with hardcoded locale patterns
- Load models inside `toMail()` / `toArray()` — not in the constructor (keeps serialized payload small)
- Use `MailMessage` builder — no raw HTML in notification classes
- The `toArray()` return must include a `type` key for frontend rendering of the in-app feed

## Localization

All notification strings live in `lang/{locale}/notifications.php`:

```
lang/
├── fr/
│   └── notifications.php
├── en/
│   └── notifications.php
└── nl/
    └── notifications.php
```

### Locale Resolution

Notifications MUST be sent in the **recipient's preferred locale**, not the sender's or the app default:

```php
$notifiable->notify(
    (new BookingConfirmedNotification($booking->id))->locale($notifiable->locale)
);
```

### Rules

- Default locale: `fr` (fr-BE) — if user has no preference, use French
- Supported: `fr`, `en`, `nl`
- Translation keys use dot notation with the `notifications.` prefix
- Every notification string in `fr` MUST have corresponding entries in `en` and `nl`
- Date formatting uses Carbon's `translatedFormat()` which respects the locale
- Monetary amounts use the `<x-money>` component pattern in Blade mail templates — format as `€ XX,XX` in mail strings via a helper

### Translation Key Convention

```php
// lang/fr/notifications.php
return [
    'greeting'                    => 'Bonjour :name,',
    'thanks'                      => 'Merci d\'utiliser Motivya !',

    // Booking
    'booking_confirmed_subject'   => 'Réservation confirmée',
    'booking_confirmed_body'      => 'Votre réservation pour :activity le :date à :time est confirmée.',
    'booking_cancelled_subject'   => 'Réservation annulée',
    'booking_cancelled_body'      => 'Votre réservation pour :activity le :date a été annulée.',
    'booking_refunded_subject'    => 'Remboursement effectué',
    'booking_refunded_body'       => 'Votre remboursement de :amount pour :activity a été traité.',

    // Session
    'session_confirmed_subject'   => 'Séance confirmée',
    'session_confirmed_body'      => 'La séance :activity du :date à :time est confirmée !',
    'session_cancelled_subject'   => 'Séance annulée',
    'session_cancelled_body'      => 'La séance :activity du :date a été annulée.',
    'session_reminder_subject'    => 'Rappel : séance demain',
    'session_reminder_body'       => 'Votre séance :activity a lieu demain à :time.',

    // Coach
    'coach_approved_subject'      => 'Profil approuvé',
    'coach_approved_body'         => 'Votre profil coach a été validé. Vous pouvez maintenant créer des séances.',
    'coach_rejected_subject'      => 'Profil refusé',
    'coach_rejected_body'         => 'Votre demande de profil coach a été refusée. Raison : :reason',
    'payout_processed_subject'    => 'Paiement effectué',
    'payout_processed_body'       => 'Un virement de :amount a été effectué sur votre compte.',

    // Coach — new booking
    'new_booking_subject'         => 'Nouvelle réservation',
    'new_booking_body'            => ':athlete_name a réservé votre séance :activity du :date.',

    // Admin
    'new_application_subject'     => 'Nouvelle candidature coach',
    'new_application_body'        => ':coach_name a soumis une demande de profil coach.',

    'view_booking'                => 'Voir ma réservation',
    'view_session'                => 'Voir la séance',
    'view_dashboard'              => 'Voir le tableau de bord',
];
```

## Session Reminder Scheduling

Use the Laravel scheduler to dispatch `SessionReminder` events. Default: **24 hours before** session start.

```php
// app/Console/Kernel.php — schedule method
$schedule->command('sessions:send-reminders')->hourly();
```

The command queries sessions starting in the next 24–25 hours that haven't had reminders sent yet. Track sent reminders via a `reminder_sent_at` column on the sessions table or a `session_reminders` tracking table — never send duplicates.

### Rules

- Only send reminders for `confirmed` sessions — never for `published` (not yet confirmed)
- Reminder window is configurable via `config('motivya.reminder_hours_before')`, default `24`
- Idempotent: re-running the command must not re-send to already-notified athletes

## Email Templates

Use Blade markdown mail templates in `resources/views/mail/`:

```
resources/views/mail/
├── booking/
│   ├── confirmed.blade.php
│   ├── cancelled.blade.php
│   └── refunded.blade.php
├── session/
│   ├── confirmed.blade.php
│   ├── cancelled.blade.php
│   └── reminder.blade.php
└── coach/
    ├── approved.blade.php
    ├── rejected.blade.php
    └── payout.blade.php
```

### Rules

- Templates extend `mail::message` (Laravel's markdown mail layout)
- Never inline CSS — rely on Laravel's built-in CSS inlining for markdown mails
- Include the Motivya logo via the mail theme customization, not per-template
- The `MailMessage` builder is preferred for simple notifications; use custom Blade templates only when the layout requires more than what the builder offers (e.g., tables, multiple CTAs)

## Testing

### Required Tests per Notification

For every notification class, write these Pest tests:

```php
// Verify event dispatch triggers the correct listener
test('SessionConfirmed event dispatches SendSessionConfirmedNotifications listener', function () {
    Event::fake();
    // ... trigger action
    Event::assertDispatched(SessionConfirmed::class);
});

// Verify notification is sent to correct recipients
test('session confirmed notification sent to all booked athletes and coach', function () {
    Notification::fake();
    // ... trigger event
    Notification::assertSentTo($athletes, SessionConfirmedNotification::class);
    Notification::assertSentTo($coach, SessionConfirmedNotification::class);
});

// Verify notification content in each locale
test('booking confirmed notification uses correct locale strings', function () {
    $notification = new BookingConfirmedNotification($booking->id);
    $mail = $notification->toMail($athlete);
    expect($mail->subject)->toBe(__('notifications.booking_confirmed_subject', [], 'fr'));
});

// Verify database payload structure
test('booking confirmed notification stores correct database payload', function () {
    $notification = new BookingConfirmedNotification($booking->id);
    $data = $notification->toArray($athlete);
    expect($data)->toHaveKeys(['booking_id', 'type']);
});
```

### Test Coverage Requirements

- Event dispatch for every event in the catalog
- Notification sent to correct recipients (not to wrong roles)
- Notification NOT sent when conditions aren't met (e.g., cancelled session doesn't send reminder)
- Locale switching: verify French, English, and Dutch output
- Idempotency: reminder command run twice sends only once
- Queue: verify notifications implement `ShouldQueue`

## Cross-References

- **Session state transitions** that dispatch events: see `session-booking` instruction
- **Payment webhook events** that trigger notifications: see `stripe-connect` instruction
- **Coach KYC approval/rejection flow**: see `auth-roles` instruction
- **Payout calculation** included in coach payout notification: see `vat-calculations` and `peppol-invoicing` instructions
