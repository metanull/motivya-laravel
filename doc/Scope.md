# Scope

This document defines what is in scope for each development phase. It is the **authoritative source** for what to build and what to defer. If a feature appears in `Features.md` or `UseCases.md` but is not listed here as MVP, it is **not in scope** for the current phase.

> **Rule for AI agents**: Before implementing a feature, check this file. If the feature is listed as Phase 2, Nice-to-have, or Out-of-scope, ask the user before proceeding.

## Phase 1: MVP (Epics 1–4)

These features must be implemented for launch. Corresponds to Stories.md Epics 1–4.

### Epic 1: Foundation & Identity

| Feature | Status | Notes |
|---------|--------|-------|
| Laravel 12 project setup | PLANNED | Standard scaffolding |
| Email/password authentication | PLANNED | Laravel Breeze or Fortify |
| Google OAuth login | PLANNED | Socialite |
| MFA (optional for coach/athlete, mandatory for admin/accountant) | PLANNED | |
| Role-based access (4 roles) | PLANNED | `UserRole` backed enum |
| Admin portal: review, approve, deny coach applications | PLANNED | KYC workflow |
| API token authentication (Sanctum) | PLANNED | For future mobile/frontend apps |

### Epic 2: Coach "Business-in-a-Box"

| Feature | Status | Notes |
|---------|--------|-------|
| Coach profile creation | PLANNED | Specialties, geographic zone, enterprise number |
| Stripe Express onboarding (KYC) | PLANNED | Stripe Connect |
| Session creation (activity, level, date, time, location, price, capacity) | PLANNED | |
| Session editing and deletion | PLANNED | With state guards |
| Recurring weekly sessions | PLANNED | "Every Wednesday at 19:00" |
| Activity-specific cover images (admin-curated library) | PLANNED | Coaches select from admin uploads |
| Coach dashboard: upcoming/confirmed/pending sessions | PLANNED | |
| Session statistics (bookings, fill rate, revenue) | PLANNED | Basic stats |
| Revenue tracking and payout history | PLANNED | |

### Epic 3: Athlete Experience & Payments

| Feature | Status | Notes |
|---------|--------|-------|
| Session discovery by postal code | PLANNED | Belgian 4-digit codes |
| Session filtering (activity, level, date, time) | PLANNED | |
| Geolocation search (2 km radius, optional) | PLANNED | Browser geolocation |
| Interactive map with session markers | PLANNED | Cluster support |
| One-click booking with atomic capacity tracking | PLANNED | DB transactions + locking |
| Stripe payment (Credit Card + Bancontact) | PLANNED | Stripe Connect |
| Tentative booking → confirmation when threshold met | PLANNED | Min participants logic |
| Automatic refund on session cancellation | PLANNED | |
| Cancellation policies (48h confirmed / 24h pending) | PLANNED | |
| Athlete dashboard: upcoming + past bookings | PLANNED | |
| Session reminders (email, 24h before) | PLANNED | |
| WhatsApp share button + copy-link on session detail | PLANNED | |
| Favourites (save sessions) | PLANNED | Per user |

### Epic 4: Accountant Portal + Invoicing

| Feature | Status | Notes |
|---------|--------|-------|
| PEPPOL BIS 3.0 XML invoice generation | PLANNED | Belgian mandate |
| Invoice on session completion | PLANNED | Triggered by event |
| Credit notes on refund | PLANNED | PEPPOL-compliant |
| VAT engine: subject vs non-subject coach handling | PLANNED | Adjusts payout formula |
| Subscription tier auto-selection (best plan for coach) | PLANNED | Freemium/Active/Premium |
| Accountant read-only transaction view | PLANNED | |
| Financial export (CSV/Excel) | PLANNED | Coaches, sessions, payments |
| Commission tracking and verification | PLANNED | |

### Cross-cutting (MVP)

| Feature | Status | Notes |
|---------|--------|-------|
| i18n: French (default), English, Dutch | PLANNED | All UI strings |
| GDPR/Privacy page (FR/EN/NL) | PLANNED | With print option |
| Mobile-first responsive UI | PLANNED | Tailwind + Livewire |
| Email notifications (booking, session, payout) | PLANNED | Event-driven |
| Browser language detection with fr-BE fallback | PLANNED | |
| Docker local dev environment | PLANNED | Nginx + PHP-FPM + MySQL + Valkey + Mailpit |
| CI pipeline (lint + test) | PLANNED | GitHub Actions |

---

## Phase 2: Post-MVP Enhancements

These features are valuable but explicitly **deferred** until after MVP launch. They may be tracked as GitHub Issues for later prioritization.

| Feature | Source | Rationale for deferral |
|---------|--------|----------------------|
| Coach ratings and reviews | UseCases.md (Coach §) | Needs content moderation system |
| Automatic review response + moderation | UseCases.md (Coach §) | AI moderation is complex |
| iCal / Google Calendar sync | UseCases.md (Coach + Athlete §) | Integration effort; not blocking for launch |
| Push notifications per favourite session | Features.md | Requires push infrastructure (service worker) |
| Coach profile vacation mode (temporary deactivation) | UseCases.md (Coach §) | Nice UX but not MVP-critical |
| Session packs (bundles) | UseCases.md (Coach nice-to-have) | Pricing model extension |
| Loyalty offers / special discounts | UseCases.md (Coach nice-to-have) | Pricing model extension |
| "Offer a session to a friend" / gift vouchers | UseCases.md (Coach §) | Payment flow variant |
| Client acquisition statistics (coach) | UseCases.md (Coach §) | Analytics extension |
| Social media push recommendations for coaches | UseCases.md (Coach §) | Marketing feature |
| Admin: global communications (mass email/notifications) | UseCases.md (Admin §) | Admin tooling |
| Admin: coach scoring system | UseCases.md (Admin nice-to-have) | Algorithm work |
| Accountant: external accounting software integration | UseCases.md (Accountant nice-to-have) | API integration |
| Accountant: cash flow forecasting | UseCases.md (Accountant nice-to-have) | Analytics |
| Admin: conversion rate tracking | UseCases.md (Admin §) | Analytics |
| Newsletter subscription with activity preferences | Features.md | Marketing feature |
| Dynamic hero counters (participants, coaches, sessions) | Features.md | Vanity metrics |
| "Activities Near You" social proof section | Features.md | Marketing feature |
| Admin: automated marketing campaigns | UseCases.md (Admin nice-to-have) | Marketing |
| Deploy pipeline (build + deploy) | CI instruction | Depends on hosting platform choice |
| Epic 5: Analytics dashboards + platform stats | Stories.md | Post-launch analytics |

---

## Nice-to-Have / Low Priority

These are explicitly low priority as stated in the source documents. Implement only if time permits after Phase 2.

| Feature | Source | Notes |
|---------|--------|-------|
| Weather-based outfit recommender | Features.md | WeatherAPI.com integration, admin manages outfit data |
| Admin outfit management (weather buckets, imagery) | Features.md | Depends on outfit recommender |
| Geolocation-driven city detection for counters | Features.md | Depends on hero counters |
| Coach: suggested optimal time slots based on demand | UseCases.md (Coach nice-to-have) | ML/analytics |
| Coach: optimal pricing recommendations | UseCases.md (Coach nice-to-have) | Market analysis |
| Coach: high-potential geographic zones | UseCases.md (Coach nice-to-have) | Analytics |
| Admin: ambassador program | UseCases.md (Admin nice-to-have) | Community feature |
| Admin: dynamic pricing models | UseCases.md (Admin nice-to-have) | Complex pricing |
| Athlete: book with friends | UseCases.md (Athlete nice-to-have) | Group booking variant |
| Athlete: sports progression tracking | UseCases.md (Athlete nice-to-have) | Beyond booking platform |
| Athlete: personalized session recommendations | UseCases.md (Athlete nice-to-have) | ML-based |
| Athlete: weather-based session suggestions | UseCases.md (Athlete nice-to-have) | Depends on weather API |

---

## Out of Scope

These will **not** be implemented in the Laravel app:

| Item | Reason |
|------|--------|
| React Native / PWA mobile app | Separate project. API tokens (Sanctum) support it, but the app itself is not this repo. |
| Azure / cloud-specific architecture | Explored in `vancappeljl/motivya` PR #3 — not relevant to this Laravel implementation |
| Python backend | The client mockup repo uses Python. We use Laravel. |
| Admin: zone development detection (auto) | Too speculative for a marketplace |
| Real-time monitoring dashboard | Over-engineering for MVP scale |

---

## How to Update This File

1. **To promote a Phase 2 item to MVP**: Move the row to the appropriate Epic table and set status to `PLANNED`.
2. **To defer an MVP item**: Move the row to Phase 2 with a rationale.
3. **To add a new feature**: Add it the appropriate section with a source reference.
4. **Status values**: `PLANNED` → `IN PROGRESS` → `DONE`. Track in GitHub Issues for granular progress.
