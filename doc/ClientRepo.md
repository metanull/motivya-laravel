# External References

## Repository Relationship

Motivya has two GitHub repositories with distinct roles:

### This Repository: `metanull/motivya-laravel`

- **Purpose**: The production Laravel application
- **Authority**: **Authoritative** for all architecture, code, and technical decisions
- **Tech stack**: Laravel 12, PHP 8.2+, MySQL, Livewire, Stripe Connect
- **Working branch**: `feat/copilot-init` (current), merges to `main`

### Client Repository: `vancappeljl/motivya`

- **Purpose**: Inspiration and client communication
- **Authority**: **Not authoritative**. The client modifies it with AI agents to communicate ideas. Do not copy code or architecture from it.
- **Tech stack**: Python-based mockup — **not our stack**
- **What to use it for**:
  - Understanding what the client has in mind (UI flows, feature ideas)
  - Reading the French use cases and business logic descriptions
  - Seeing the client's latest feature priorities
- **What NOT to do**:
  - Do not import code, dependencies, or architectural patterns from it
  - Do not treat its structure as a reference for our Laravel project
  - Do not assume its decisions (hosting, framework, database) apply to us

#### Notable content in `vancappeljl/motivya`

| Branch | Content | Status |
|--------|---------|--------|
| `main` | AI-generated live mockup (Python/exotic stack) | Active — client updates this |
| PR #3 (`copilot/refactor-project-architecture-again`) | Exploratory documentation: README, ARCHITECTURE, DECISION_BRIEF, PEPPOL, hosting evaluations (Azure, OVH, managed) | **Will not be merged** — was temporary storage |

The PR #3 `docs/` folder contains useful domain research:
- `PEPPOL.md` — Detailed PEPPOL BIS 3.0 research (useful reference for our `peppol-invoicing` instruction)
- `DECISION_BRIEF.md` — Early architecture exploration (superseded by our `doc/Decisions.md`)
- `NOTES_DECISIONS.md` — Raw decision notes (partially outdated)
- `ARCHITECTURE.md` — Generic architecture (not Laravel-specific, not authoritative)
- Hosting evaluations (Azure, OVH) — Informational only, hosting decision is separate

## How AI Agents Should Use This Information

1. **Never fetch from `vancappeljl/motivya`** during code generation — our `doc/` and `.github/instructions/` are the source of truth
2. **If asked to review client ideas**: Fetch from `vancappeljl/motivya` main branch, compare with our `doc/Scope.md`, and flag any gaps or conflicts
3. **If a feature in the client repo is not in our `doc/Scope.md`**: It is out of scope until explicitly added
