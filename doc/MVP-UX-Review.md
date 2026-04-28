# MVP UX Review

## Context

This document captures the UX polishing findings and recommendations for the Motivya MVP. It is referenced by the "Focused MVP UX Polish" epic. The goal is a small, focused polish pass so coaches and athletes can use the platform smoothly during real tests — without a broad redesign.

---

## UX Polishing Recommendations

### 1. Homepage Usefulness

**Issue**: The welcome page shows the app name as both title and tagline, providing no context about what Motivya offers or who it is for.

**Recommendation**:
- Replace the repeated tagline with a meaningful subtitle that explains Motivya's value proposition.
- Add a short role-specific feature summary (coach / athlete benefits).
- Keep the existing CTA buttons (Browse Sessions, Register, Login).

**Impact**: First-time visitors understand the platform immediately.

---

### 2. Status Labels

**Issue**: On the session detail page (`sessions/show.blade.php`), the status badge only renders colours for `Published` and `Confirmed` statuses. `Draft`, `Completed`, and `Cancelled` sessions render with no badge colour variant, making them hard to distinguish at a glance.

**Recommendation**:
- Extend the `@class` / `match` logic on the status badge to cover all five session statuses with semantically meaningful colours:
  - `Draft` → gray
  - `Published` → blue/indigo
  - `Confirmed` → green
  - `Completed` → purple
  - `Cancelled` → red

**Impact**: Coaches viewing their own draft/completed/cancelled sessions get immediate visual feedback.

---

### 3. Coach Form Guidance

**Issue**: The session create and edit forms lack hint text for fields whose business rules are not obvious to new coaches:
- **Min participants**: coaches may not know this is the threshold that triggers session confirmation.
- **Max participants**: no hint about capacity.
- **Price**: no reminder that the price is per person.

**Recommendation**:
- Add a small helper text (`text-xs text-gray-500`) below the min participants, max participants, and price fields.
- Translations in all three locales (fr, en, nl).

**Impact**: Coaches fill in forms correctly on first try, reducing support queries.

---

### 4. Post-Publish Sharing

**Issue**: After a coach publishes a session, there is no quick way to share it from the dashboard session card. The share buttons exist on the public session detail page, but coaches need to navigate there to access them.

**Recommendation**:
- Add WhatsApp share and copy-link buttons to the session card in `coach/partials/session-card.blade.php` when the session is `Published` or `Confirmed`.
- Reuse the same share pattern already present in `session/show.blade.php`.

**Impact**: Coaches can immediately share a newly published session without an extra navigation step.

---

### 5. Role-Based Dashboard Clarity

#### Athlete Dashboard

**Issue**: When an athlete has no upcoming bookings, the empty state only shows a plain text message. There is no actionable CTA to guide the athlete toward discovering sessions.

**Recommendation**:
- Enrich the empty state with a "Browse sessions" link styled as a button, pointing to `sessions.index`.

#### Coach Dashboard

**Issue**: The "Upcoming" tab empty state already includes "Create one to get started!", but the "Drafts" and "Past" empty states are bare messages.

**Recommendation**:
- For the Drafts empty state: add a link to create a new session.
- Past tab is informational only; no CTA needed.

**Impact**: Both roles have clear next-action paths when their dashboards are empty.

---

## Role-Based Assessment

### Coach

| Area | Status | Notes |
|------|--------|-------|
| Session create/edit form | ⚠️ Needs guidance | Min/max participants and price lack hints |
| Post-publish sharing | ⚠️ Missing shortcut | Share buttons only on public detail page |
| Dashboard status visibility | ✅ Good | Session card already shows colour-coded status badges |
| Empty states | ✅ Upcoming has CTA; ⚠️ Drafts could improve |

### Athlete

| Area | Status | Notes |
|------|--------|-------|
| Session detail status | ⚠️ Incomplete | Draft/Completed/Cancelled badges lack colour |
| Dashboard empty state | ⚠️ No CTA | Upcoming tab empty state is text-only |
| Quick links | ✅ Good | Explore / Favourites / Profile already present |

---

## Out of Scope (Phase 2)

- Full redesign of any screen
- New navigation patterns
- Push notifications
- Analytics dashboards
- Any feature not already planned in the MVP scope
