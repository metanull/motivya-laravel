# Motivya

Motivya is a marketplace for the Brussels sports community. It connects **Coaches** and **Athletes**, handling session discovery, booking, payment, and legal e-invoicing — all under Belgian law.

## Roles

| Role | Purpose |
|------|---------|
| **Coach** | Creates and manages sports sessions, receives payouts |
| **Athlete** | Discovers sessions, books and pays |
| **Accountant** | Read-only financial oversight and exports |
| **Admin** | Platform management, coach validation, disputes |

## Key Domains

- **Sessions** — Coaches publish sessions with schedule, price, and participant thresholds. Sessions confirm automatically when enough athletes book.
- **Bookings & Payments** — Athletes book and pay via Stripe (including Bancontact). Cancellation policies protect both sides.
- **Invoicing** — PEPPOL BIS 3.0 e-invoices generated automatically, compliant with the Belgian mandate (Jan 2026).
- **VAT** — Distinguishes VAT-subject and non-subject (franchise) coaches. Platform margin preserved on every transaction.

## Documentation

| Document | Description |
|----------|-------------|
| [Glossary](doc/Glossary.md) | Canonical domain vocabulary and definitions |
| [Scope](doc/Scope.md) | MVP boundaries and phase breakdown |
| [Features](doc/Features.md) | Feature matrix by role |
| [Use Cases](doc/UseCases.md) | Original use cases from the client |
| [Stories](doc/Stories.md) | Epics and user stories |
| [Decisions](doc/Decisions.md) | Architecture decision records |
| [Client Repo](doc/ClientRepo.md) | Relationship with the client's inspiration repo |

## Tech Stack

Laravel 12 · Livewire · Blade · Tailwind CSS · MySQL · Stripe Connect · PEPPOL BIS 3.0

## Quick Start

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install && npm run build
php artisan serve
```

## Tests

```bash
php artisan test
```