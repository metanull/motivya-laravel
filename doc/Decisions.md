# Architecture Decision Records

This file tracks all key technical and business decisions for the Motivya project. Each decision has a status and rationale.

> **Statuses**: `DECIDED` — final, do not revisit without discussion. `PROPOSED` — under consideration. `DEFERRED` — intentionally postponed.

---

## ADR-001: Backend Framework — Laravel 12

- **Status**: DECIDED
- **Date**: 2026-01
- **Decision**: Use Laravel 12 (PHP 8.2+) with Livewire + Blade for the web UI.
- **Rationale**: Mature ecosystem, built-in support for auth, queues, events, Stripe (Cashier), and PEPPOL-related XML generation. Strong community. Team expertise.
- **Alternatives rejected**: The client repo (`vancappeljl/motivya`) uses a Python-based stack — this is a mockup, not the production choice.

## ADR-002: Database — MySQL (prod) / SQLite (dev/test)

- **Status**: DECIDED
- **Date**: 2026-01
- **Decision**: MySQL in production, SQLite in development and testing.
- **Rationale**: MySQL for production reliability, geolocation queries (distance calculations), and hosting compatibility. SQLite for fast test execution with no external dependencies.

## ADR-003: Payments — Stripe Connect + Bancontact

- **Status**: DECIDED
- **Date**: 2026-01
- **Decision**: Use Stripe Connect (Express accounts) for coach payouts. Support Credit Card and Bancontact payment methods. Use Laravel Cashier for subscription management.
- **Rationale**: Client already has a Stripe account. Stripe Connect handles marketplace payout splits. Bancontact is the dominant payment method in Belgium.
- **Constraints**: Stripe sends webhook callbacks — must verify signatures and handle idempotently.

## ADR-004: Invoicing — PEPPOL BIS 3.0

- **Status**: DECIDED
- **Date**: 2026-01
- **Decision**: Generate PEPPOL BIS 3.0 XML invoices for all B2B transactions. Use Billit (via Stripe App) for invoice routing.
- **Rationale**: Belgian law mandates PEPPOL for B2B e-invoicing since January 2026. All coaches have enterprise numbers.
- **Constraints**: Credit notes must also be PEPPOL-compliant. VAT-subject and non-subject coaches require different invoice handling.

## ADR-005: Authentication — Multi-method + Roles

- **Status**: DECIDED
- **Date**: 2026-01
- **Decision**: Support email/password, Google OAuth (Socialite), MFA, role-based access (4 roles), and API tokens (Sanctum).
- **Rationale**: Google OAuth lowers friction for athletes. MFA mandatory for admin/accountant given financial access. Sanctum tokens enable future mobile/PWA frontends.

## ADR-006: Storage — S3-compatible (prod) / Filesystem (dev)

- **Status**: DECIDED
- **Date**: 2026-01
- **Decision**: Use S3-compatible object storage in production (OVH or Laravel Cloud). Local filesystem in development/testing.
- **Rationale**: Laravel's filesystem abstraction makes the switch transparent via config.

## ADR-007: Cache — Valkey-compatible (prod) / File driver (dev)

- **Status**: DECIDED
- **Date**: 2026-01
- **Decision**: Use Valkey-compatible cache (Redis protocol) in production. File driver in development/testing.
- **Rationale**: Valkey is the open-source Redis successor. High performance for session/cache. File driver keeps dev setup simple.

## ADR-008: Internationalization — fr-BE / en-GB / nl-BE

- **Status**: DECIDED
- **Date**: 2026-01
- **Decision**: Three locales: French fr-BE (default), English en-GB, Dutch nl-BE. All UI strings localized via `lang/` directory.
- **Rationale**: Brussels is trilingual. French is the dominant language for the target audience. Browser language detection with fr-BE fallback.
- **Note**: Original client docs use `en-UK` — the correct IETF tag is `en-GB`. Code uses short codes: `fr`, `en`, `nl`.

## ADR-009: VAT Handling — HTVA-based Coach Payout Formula

- **Status**: DECIDED
- **Date**: 2026-01
- **Decision**: All platform margin calculations work on HTVA (excl. VAT) amounts. Coach payout formula: `payout = revenue_HTVA - target_margin_HTVA`. Non-subject coaches receive an adjusted (lower) payout to preserve platform margin.
- **Rationale**: When a coach is non-subject (franchise regime), the platform collects VAT from athletes but cannot recover it from the coach's invoice. Working in HTVA ensures consistent margins regardless of coach VAT status.
- **See**: [Glossary.md](Glossary.md) for VAT term definitions. [UseCases.md](UseCases.md) "Fonctionnement comptable TVA" for worked examples.

## ADR-010: Monetary Amounts — Integer Cents

- **Status**: DECIDED
- **Date**: 2026-01
- **Decision**: All monetary amounts stored as integers in cents (EUR). `1250` = €12,50. Never use floats or decimals.
- **Rationale**: Avoids floating-point rounding errors in financial calculations. Stripe also uses cents. Display formatting happens only at the view layer.

## ADR-011: Session Booking — Atomic with Threshold Confirmation

- **Status**: DECIDED
- **Date**: 2026-01
- **Decision**: Booking must be atomic (DB transactions with pessimistic locking). Sessions confirm automatically when min participants threshold is reached. Sessions cancel automatically if threshold not reached by deadline.
- **Rationale**: Prevents overbooking under concurrency. Threshold-based confirmation is the core business model.
- **Cancellation policies**: 48h for confirmed sessions, 24h for pending sessions.

## ADR-012: Subscription Tiers — Auto-Best-Plan

- **Status**: DECIDED
- **Date**: 2026-01
- **Decision**: Three tiers (Freemium 30%, Active €39+20%, Premium €79+10%). The system automatically applies the most advantageous tier for the coach on each billing cycle.
- **Rationale**: Prevents coaches from overpaying on a higher tier when Freemium would be cheaper. See worked examples in [UseCases.md](UseCases.md).

## ADR-013: Hosting Platform — OVH VPS

- **Status**: DECIDED
- **Date**: 2026-04
- **Decision**: Single OVH VPS Starter (1 vCPU, 2 GB RAM, 20 GB SSD, France datacenter) running Docker Compose. Domain: `metanull.eu`. SSL via Let's Encrypt.
- **Rationale**: Cheapest viable option (~€3.50/mo). Free OVH credits cover initial period. A single VPS running the same Docker Compose stack as local dev (Nginx + PHP-FPM + MySQL + Valkey) handles MVP traffic easily. No multi-server orchestration needed at this scale.
- **Deployment**: GitHub Actions SSH-based deploy on push to `main`. No container registry — code synced via rsync/git pull, containers rebuilt on the VPS.
- **Cost model**: VPS is billed monthly (not per-hour). Shutting down does not save money. At ~€3.50/mo this is acceptable.
- **Scaling path**: If traffic outgrows VPS Starter, upgrade to VPS Comfort (2 vCPU, 4 GB) in-place. Move to managed Kubernetes or Laravel Cloud only if truly needed.
- **Alternatives rejected**: OVH Public Cloud (hourly billing but higher baseline cost, more complex). Azure (explored in client repo PR #3, too expensive for MVP). Laravel Cloud (convenient but more expensive than bare VPS for a single app).

## ADR-014: Future Mobile App — React 19 PWA

- **Status**: DEFERRED
- **Date**: 2026-01
- **Decision**: A React 19 PWA for mobile is planned but **not in scope** for this repository. The Laravel API (Sanctum tokens) will support it when ready.
- **Rationale**: MVP ships with Livewire web UI. Mobile app is a separate project.