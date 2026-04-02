---
description: "Use when implementing session creation, session editing, booking logic, capacity tracking, cancellation policies, threshold-based confirmation, recurring sessions, or booking-related service classes. Covers atomic booking, overbooking prevention, min/max participant rules, and refund triggers."
applyTo: "app/Services/*Session*,app/Services/*Booking*,app/Models/Session*,app/Models/Booking*"
---
# Session Booking Rules

## Session Lifecycle

A session follows a strict state machine. Never skip states or allow transitions outside this flow:

```
DRAFT → PUBLISHED → CONFIRMED → COMPLETED
                  ↘ CANCELLED
```

| State | Meaning | Transitions To |
|-------|---------|----------------|
| `draft` | Coach is editing, not visible to athletes | `published`, deleted |
| `published` | Visible, accepting bookings, threshold not yet met | `confirmed`, `cancelled` |
| `confirmed` | Min participants reached, session will happen | `completed`, `cancelled` |
| `completed` | Session took place, triggers payout/invoicing | — (terminal) |
| `cancelled` | Threshold not met by deadline, or coach cancelled | — (terminal) |

### State Transition Rules

- `published → confirmed`: automatic when bookings reach `min_participants`
- `published → cancelled`: automatic when deadline passes and `min_participants` not reached
- `confirmed → cancelled`: only by admin or coach (triggers refunds for all athletes)
- `confirmed → completed`: automatic after session `end_time` passes (or manual by coach/admin)
- `completed` triggers payout calculation — defer to `peppol-invoicing` and `vat-calculations` instructions

## Session Model Requirements

| Field | Type | Rules |
|-------|------|-------|
| `coach_id` | FK | Required, must match authenticated coach |
| `activity_type` | string | From admin-managed list of activities |
| `level` | enum | `beginner`, `intermediate`, `advanced` |
| `location` | string | Address or venue name |
| `postal_code` | string | Belgian postal code for geo-filtering |
| `latitude` / `longitude` | decimal | For map display and proximity search |
| `date` | date | Future dates only on creation |
| `start_time` / `end_time` | time | End must be after start |
| `price_per_person` | integer | In **cents** (EUR), never floats |
| `min_participants` | integer | ≥ 1 |
| `max_participants` | integer | ≥ `min_participants` |
| `current_participants` | integer | Maintained atomically, starts at 0 |
| `status` | enum | One of the lifecycle states above |
| `cover_image_id` | FK nullable | References admin-uploaded activity images (coaches select, not upload) |

## Atomic Booking — Overbooking Prevention

Booking MUST be atomic. Use a DB transaction with a row-level lock to prevent race conditions:

```php
DB::transaction(function () use ($session, $athlete) {
    // Lock the session row for update
    $session = Session::lockForUpdate()->findOrFail($session->id);

    if ($session->current_participants >= $session->max_participants) {
        throw new SessionFullException();
    }

    if ($session->status !== 'published' && $session->status !== 'confirmed') {
        throw new SessionNotBookableException();
    }

    // Prevent duplicate bookings
    if ($session->bookings()->where('athlete_id', $athlete->id)->exists()) {
        throw new AlreadyBookedException();
    }

    Booking::create([
        'session_id' => $session->id,
        'athlete_id' => $athlete->id,
        'status'     => 'pending_payment',
    ]);

    $session->increment('current_participants');

    // Check threshold
    if ($session->current_participants >= $session->min_participants
        && $session->status === 'published') {
        $session->update(['status' => 'confirmed']);
        // Dispatch SessionConfirmed event → notifications to all participants + coach
    }
});
```

### Critical Rules

- **Always** use `lockForUpdate()` on the session row inside a transaction
- **Always** check capacity inside the lock — never rely on a pre-lock check
- **Always** check for duplicate bookings (unique constraint on `session_id` + `athlete_id`)
- **Never** increment `current_participants` outside the transaction
- Throw domain exceptions (`SessionFullException`, `SessionNotBookableException`, `AlreadyBookedException`) — never return boolean or null

## Booking Model Requirements

| Field | Type | Rules |
|-------|------|-------|
| `session_id` | FK | Required |
| `athlete_id` | FK | Required |
| `status` | enum | `pending_payment`, `confirmed`, `cancelled`, `refunded` |
| `payment_intent_id` | string nullable | Stripe PaymentIntent ID |
| `cancelled_at` | timestamp nullable | When athlete or system cancelled |
| `refund_id` | string nullable | Stripe refund ID if applicable |

Unique constraint on (`session_id`, `athlete_id`) — one booking per athlete per session.

## Booking States

```
PENDING_PAYMENT → CONFIRMED → (session completes normally)
                ↘ CANCELLED → REFUNDED
```

| State | Meaning |
|-------|---------|
| `pending_payment` | Booking created, awaiting Stripe payment confirmation |
| `confirmed` | Payment succeeded (via `payment_intent.succeeded` webhook) |
| `cancelled` | Athlete cancelled, or session cancelled by coach/system |
| `refunded` | Stripe refund processed |

## Threshold-Based Confirmation

When a booking brings `current_participants` to `min_participants`:

1. Session status changes from `published` to `confirmed`
2. Dispatch `SessionConfirmed` event
3. Listeners send notifications to **all** booked athletes and the coach
4. If using payment holds (card payments), capture all pending authorizations

When the deadline passes and `min_participants` is NOT reached:

1. Session status changes to `cancelled`
2. Dispatch `SessionCancelled` event
3. All bookings marked `cancelled` → `refunded`
4. Stripe refunds issued for all payments
5. Notifications sent to all booked athletes and the coach

Use a **scheduled command** (Laravel scheduler) to check for expired sessions that haven't reached threshold.

## Cancellation Policies

### Athlete Cancellation

| Session Status | Cancellation Window | Result |
|----------------|---------------------|--------|
| `confirmed` | ≥ 48 hours before `start_time` | Full refund, decrement participants |
| `confirmed` | < 48 hours before `start_time` | **No refund** |
| `published` (pending) | ≥ 24 hours before `start_time` | Full refund, decrement participants |
| `published` (pending) | < 24 hours before `start_time` | **No refund** |

```php
public function canCancelWithRefund(Booking $booking): bool
{
    $session = $booking->session;
    $hoursUntilStart = now()->diffInHours($session->start_datetime, false);

    return match ($session->status) {
        'confirmed' => $hoursUntilStart >= 48,
        'published' => $hoursUntilStart >= 24,
        default     => false,
    };
}
```

### Coach / Admin Cancellation

- Coach can cancel any session they own (any status before `completed`)
- Admin can cancel any session
- Cancellation of a `confirmed` session triggers **full refund** for all athletes regardless of timing
- Cancelling a `confirmed` session generates credit notes — defer to `peppol-invoicing` instruction

### Participant Count Maintenance

When a booking is cancelled:
1. Decrement `current_participants` inside a transaction with `lockForUpdate()`
2. If `current_participants` drops below `min_participants` after session was `confirmed`, the session stays `confirmed` (no automatic revert to `published` — business decision)

## Recurring Sessions

Coaches can create recurring weekly sessions. Implementation:

- Store a `recurrence_rule` on the session (e.g., `weekly`, `null` for one-off)
- A **scheduled command** generates future session instances from the template
- Each generated instance is an independent `Session` row with its own bookings, capacity, and lifecycle
- Link recurring instances via `parent_session_id` (nullable FK to self)
- Coach edits to the template affect **future unbooked instances only** — never modify instances with existing bookings

## Payment Integration Points

Booking interacts with Stripe at these points — defer details to `stripe-connect` instruction:

| Booking Event | Stripe Action |
|---------------|---------------|
| Athlete books | Create PaymentIntent (Bancontact: immediate charge) |
| Payment succeeds | Webhook → mark booking `confirmed` |
| Payment fails | Webhook → release slot, mark booking `cancelled` |
| Athlete cancels (with refund) | Create Stripe Refund → mark booking `refunded` |
| Session cancelled by system | Batch refunds for all confirmed bookings |

## Events to Dispatch

| Event | When | Listeners Should |
|-------|------|-----------------|
| `BookingCreated` | New booking saved | — |
| `BookingConfirmed` | Payment succeeded | Send confirmation email/notification to athlete |
| `BookingCancelled` | Booking cancelled | Process refund if eligible, send notification |
| `SessionConfirmed` | Threshold met | Notify all athletes + coach, capture holds (if card) |
| `SessionCancelled` | Deadline passed without threshold, or manual cancel | Refund all, notify all |
| `SessionCompleted` | Session end time passed | Trigger payout calculation + invoice generation |

## Service Class Conventions

- Place booking logic in `app/Services/BookingService` or `app/Services/*Booking*`
- Place session lifecycle logic in `app/Services/SessionService` or `app/Services/*Session*`
- **Never** put booking/capacity logic in controllers or Livewire components
- Controllers and Livewire components call the service, handle the response/exception
- Validate inputs via **Form Request** classes (`StoreSessionRequest`, `BookSessionRequest`)
- Authorize via **Policy** classes — coach owns session, athlete can book published/confirmed sessions

## Testing Requirements

- Test atomic booking under concurrent load (two bookings for last slot)
- Test threshold confirmation trigger (booking that meets `min_participants`)
- Test each cancellation window boundary (exactly 48h, 47h59m, 24h, 23h59m)
- Test duplicate booking prevention
- Test session state transitions — invalid transitions must throw
- Test recurring session generation
- Test participant count consistency after cancel + rebook sequences
