# Stories Index

This directory contains the detailed story breakdown for the Motivya project. Each file maps to a GitHub Milestone (epic). Stories within each file are designed to be small, single-responsibility, and independently deliverable.

## Structure

| File | Epic | Milestone | Depends On |
|------|------|-----------|------------|
| [epic-1-foundation.md](epic-1-foundation.md) | Foundation & Identity | Epic 1 | Issue #15 (prerequisites) |
| [epic-2-coach.md](epic-2-coach.md) | Coach "Business-in-a-Box" | Epic 2 | Epic 1 |
| [epic-3-athlete.md](epic-3-athlete.md) | Athlete Experience & Payments | Epic 3 | Epic 1, Epic 2 |
| [epic-4-accountant.md](epic-4-accountant.md) | Accountant Portal + Invoicing | Epic 4 | Epic 3 |

## Conventions

- **Story IDs** use the format `E{epic}-S{sequence}` (e.g., `E1-S01`). Child stories append a letter: `E1-S01a`, `E1-S01b`.
- **Labels** are suggested per story. Create them in GitHub before importing.
- **Acceptance criteria** are written as checkboxes — map directly to the GitHub issue body.
- **Dependencies** reference other story IDs. A story should not start until its dependencies are `DONE`.
- **Size** estimates: `XS` (< 1h), `S` (1–3h), `M` (3–8h), `L` (1–2d). Stories sized `L` should be broken into children.

## Suggested GitHub Labels

| Label | Color | Description |
|-------|-------|-------------|
| `infrastructure` | `#0e8a16` | Project setup, config, CI/CD |
| `auth` | `#1d76db` | Authentication, authorization, roles |
| `payments` | `#b60205` | Stripe, Cashier, financial logic |
| `invoicing` | `#d93f0b` | PEPPOL, VAT, credit notes |
| `booking` | `#0075ca` | Session booking, cancellation |
| `ui` | `#7057ff` | Blade/Livewire views, components |
| `i18n` | `#e4e669` | Translations, locale handling |
| `messaging` | `#c5def5` | Email, WhatsApp share |
| `coach` | `#fbca04` | Coach-specific features |
| `athlete` | `#006b75` | Athlete-specific features |
| `admin` | `#b4a7d6` | Admin-specific features |
| `accountant` | `#f9d0c4` | Accountant-specific features |
| `testing` | `#bfd4f2` | Tests only (no production code) |

## Suggested GitHub Milestones

| Milestone | Due (suggested) | Description |
|-----------|-----------------|-------------|
| Epic 1: Foundation & Identity | — | Auth, roles, layout, i18n, admin KYC portal |
| Epic 2: Coach Business-in-a-Box | — | Coach profile, sessions, recurring, dashboard |
| Epic 3: Athlete Experience & Payments | — | Discovery, booking, Stripe, refunds, athlete dashboard |
| Epic 4: Accountant Portal + Invoicing | — | PEPPOL, VAT engine, credit notes, exports |
