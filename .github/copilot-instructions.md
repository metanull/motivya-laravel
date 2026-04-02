# Project Guidelines

## Overview

Motivya is a marketplace for the Brussels sports community connecting Coaches and Athletes. It automates session discovery, booking, payment (Stripe), and PEPPOL-compliant e-invoicing under Belgian law.

Four roles: **Coach**, **Athlete** (end-user), **Accountant**, **Admin**.

See [doc/Glossary.md](../doc/Glossary.md) for the canonical domain vocabulary, [doc/Scope.md](../doc/Scope.md) for MVP boundaries, [doc/Decisions.md](../doc/Decisions.md) for architecture decision records, [doc/Features.md](../doc/Features.md) for the full feature matrix, [doc/UseCases.md](../doc/UseCases.md) for use cases, [doc/Stories.md](../doc/Stories.md) for epics, and [doc/ClientRepo.md](../doc/ClientRepo.md) for the relationship with the client's inspiration repo.

## Tech Stack

- **Backend**: Laravel 12, PHP 8.2+
- **UI**: Livewire + Blade (mobile-first)
- **Database**: MySQL in production; SQLite in dev/test
- **Payments**: Stripe Connect + Laravel Cashier; must support Bancontact
- **Invoicing**: PEPPOL BIS 3.0 XML invoices (Belgian mandate since Jan 2026)
- **Auth**: Email/password, Google OAuth, MFA, role-based access, API tokens
- **Storage**: S3-compatible (OVH/Laravel Cloud) in prod; local filesystem in dev/test
- **Cache**: Valkey-compatible in prod; file driver in dev/test
- **i18n**: French fr-BE (default), English en-GB, Dutch nl-BE

## Build & Test

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install && npm run build

# Run tests
php artisan test

# Dev server
php artisan serve
```

## Architecture

- Follow standard Laravel conventions (controllers, form requests, models, policies, migrations)
- Use Livewire components for interactive UI; keep Blade templates thin
- Business logic in dedicated **Service** classes (`app/Services/`), not in controllers or models
- Use **Form Request** classes for all validation
- Use **Policies** for authorization; map to the four roles (coach, athlete, accountant, admin)
- Use **Events + Listeners** for side effects (notifications, invoice generation, Stripe webhooks)
- Stripe webhook handling via dedicated controller; verify signatures

## Conventions

- All monetary amounts stored as **integers in cents** (EUR)
- VAT calculations must distinguish between VAT-subject and non-subject coaches; see [doc/UseCases.md](../doc/UseCases.md) "Fonctionnement comptable TVA" section
- Coach payout formula for non-subject coaches: `payout = revenue_excl_vat - target_margin_excl_vat`
- Session booking must be **atomic** (DB transactions) to prevent overbooking
- Cancellation policies: 48h for confirmed sessions, 24h for pending sessions
- Use Laravel's localization (`lang/` directory) for all user-facing strings — never hardcode text
- Environment-based config: never commit `.env`; use `config()` helper, not `env()` outside config files
- Prefer `php artisan make:*` generators for scaffolding new classes

## Testing

- Use **Pest** (Laravel's default in v12) for all tests
- Feature tests for HTTP endpoints and Livewire components
- Unit tests for service classes and VAT/payout calculations
- Use SQLite `:memory:` database for tests
- Factories and seeders for test data; keep production seeders minimal
