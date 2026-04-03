---
description: "Use when creating or modifying GitHub Actions workflows, CI/CD pipeline configuration, automated test matrix, linting steps, Docker image builds, deployment triggers, or .github/workflows/ contents. Covers the full Motivya pipeline: lint, test, build, and deploy stages."
applyTo: ".github/workflows/**"
---

# CI/CD Pipeline Rules

## Pipeline Overview

Three workflow files, each with a distinct role. Do NOT merge them or create additional ones.

```
PR opened/updated               Push to main
       │                              │
       ▼                              ▼
┌─────────────┐                ┌─────────────┐
│  PR Check   │                │     CI      │
│ (pr-check)  │                │  (ci.yml)   │
│ Lint+Test+  │                │ Lint+Test+  │
│ Build+CodeQL│                │ Build artifact│
└─────────────┘                └──────┬──────┘
       │                              │ workflow_run
  Blocks merge                        ▼
                               ┌─────────────┐
                               │   Deploy    │
                               │(deploy.yml) │
                               │ SCP artifact│
                               │ to VPS      │
                               └─────────────┘
```

| Workflow | Trigger | Purpose | Blocks merge |
|----------|---------|---------|-------------|
| PR Check | `pull_request → main` | Gate: Lint, Build check, Test (3 PHP), CodeQL | Yes (required status checks) |
| CI | `push → main` | Produce release artifact (tarball) | — |
| Deploy | `workflow_run` (CI success on main) | Download artifact, SCP to VPS, extract | — |

## Workflow Files

```
.github/workflows/
├── pr-check.yml    # PR gate: lint, build check, test matrix, CodeQL
├── ci.yml          # Main pipeline: lint, test, build release artifact
├── deploy.yml      # CD: download artifact, SCP to VPS, run deploy.sh
```

Keep it to exactly 3 files. Do not create per-stage workflow files.

## Stage 1: Lint (`ci.yml`)

### PHP Lint — Laravel Pint

```yaml
- name: Install dependencies
  run: composer install --no-interaction --prefer-dist --optimize-autoloader

- name: Run Laravel Pint
  run: vendor/bin/pint --test
```

- `--test` flag fails CI if any file is not formatted — does not auto-fix.
- Pint config lives in `pint.json` at the project root.
- Pint is the only PHP formatter — no PHP-CS-Fixer, no StyleCI.

### PHP Static Analysis — Larastan (optional)

```yaml
- name: Run Larastan
  run: vendor/bin/phpstan analyse --memory-limit=512M
  continue-on-error: false
```

- Only include this step if `phpstan.neon` or `phpstan.neon.dist` exists in the project root.
- Do NOT add `continue-on-error: true` — if Larastan is configured, it must pass.
- Respect the configured level — never override it in CI.

### Frontend Lint — NPM

```yaml
- name: Install Node dependencies
  run: npm ci

- name: Build assets
  run: npm run build
```

- `npm ci` (not `npm install`) for deterministic builds.
- `npm run build` validates that Vite/asset compilation succeeds — no separate lint step unless ESLint is configured.

### Translation Key Parity

```yaml
- name: Check translation parity
  run: php artisan test --filter=TranslationParityTest
```

- Runs the Pest test that asserts all `fr/` keys exist in `en/` and `nl/`.
- Only include this step after the translation parity test is written.

## Stage 2: Test (`ci.yml`)

### PHP Version Matrix

```yaml
strategy:
  fail-fast: true
  matrix:
    php: ['8.2', '8.3', '8.4']
```

- Test against PHP 8.2 (minimum), 8.3, and 8.4.
- `fail-fast: true` — stop all matrix jobs when the first one fails.
- Do NOT add PHP 8.1 or earlier — the project requires 8.2+.

### Test Environment

```yaml
env:
  APP_ENV: testing
  APP_KEY: base64:test-key-for-ci-only-not-real=
  DB_CONNECTION: sqlite
  DB_DATABASE: ":memory:"
  CACHE_STORE: array
  QUEUE_CONNECTION: sync
  SESSION_DRIVER: array
  MAIL_MAILER: array
```

- **SQLite `:memory:`** for all CI tests — never provision MySQL in CI.
- **Array drivers** for cache, queue, session, mail — no external services needed.
- `APP_KEY` is a dummy base64 key — never use a real secret.
- Do NOT set `STRIPE_SECRET` or any external API keys in CI unless running integration tests.

### Test Execution

```yaml
- name: Prepare application
  run: |
    cp .env.example .env
    php artisan key:generate

- name: Run migrations
  run: php artisan migrate --force

- name: Run Pest tests
  run: vendor/bin/pest --parallel --coverage --min=80
```

- `--parallel` for speed — Pest parallel testing.
- `--coverage` with `--min=80` — fail if coverage drops below 80%.
- Coverage requires Xdebug or PCOV extension — prefer PCOV for speed:

```yaml
- name: Setup PHP
  uses: shivammathur/setup-php@v2
  with:
    php-version: ${{ matrix.php }}
    extensions: pdo_sqlite, bcmath, intl, gd, zip, pcntl, redis
    coverage: pcov
```

### Required PHP Extensions

Install these in CI to match the Docker dev environment:

```
pdo_sqlite, bcmath, intl, gd, zip, pcntl, redis
```

- `pdo_sqlite` for test DB — not `pdo_mysql`.
- `redis` (phpredis) for code that references the Redis facade — even though CI uses `array` driver.

## Stage 3: Build Release Artifact (`ci.yml`, main only)

The build job runs after tests pass on main. It produces a self-contained tarball.

```yaml
- name: Install Composer dependencies (production)
  run: composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

- name: Build production assets
  run: |
    npm ci
    npm run build

- name: Create release archive
  run: |
    tar czf /tmp/release.tar.gz \
      --exclude='.git' --exclude='node_modules' --exclude='tests' \
      --exclude='.github' --exclude='.env' \
      --exclude='storage/logs/*' --exclude='storage/framework/cache/data/*' \
      --exclude='storage/framework/sessions/*' --exclude='storage/framework/views/*' \
      .

- name: Upload release artifact
  uses: actions/upload-artifact@v7
  with:
    name: release-${{ github.sha }}
    path: /tmp/release.tar.gz
    retention-days: 7
```

- The archive contains app code + `vendor/` (no-dev) + `public/build/` (compiled assets).
- No `node_modules`, `.git`, `tests`, or `.github` in the artifact.
- **No Docker images** — the VPS runs bare PHP-FPM + Nginx (not containers).
- Artifact is retained for 7 days.

## Stage 4: Deploy (`deploy.yml`)

### Trigger

```yaml
on:
  workflow_run:
    workflows: [CI]
    types: [completed]
    branches: [main]
```

- Triggered automatically when CI succeeds on main.
- Downloads the release artifact using `actions/download-artifact@v7` with `GITHUB_TOKEN`.
- No manual dispatch unless explicitly requested.

### Environment

```yaml
environment:
  name: motivya.metanull.eu
  url: https://motivya.metanull.eu
```

- Use the GitHub Environment `motivya.metanull.eu` for deployment secrets.
- Application secrets (Stripe, DB, etc.) live in `.env.production` on the VPS — never in GitHub.

### Deploy Steps

1. Download release artifact from CI run
2. Setup SSH + verify connectivity (netcat port 22) and auth (SSH whoami) — fail fast
3. SCP tarball to VPS `/tmp/`
4. SSH: run `scripts/deploy.sh <tarball>` on VPS which:
   - Extracts to a timestamped release dir (`/opt/motivya/releases/<timestamp>/`)
   - Symlinks shared storage
   - Runs migrations, warms caches
   - Configures Nginx, reloads services
   - Swaps `/opt/motivya/current` symlink (atomic)
   - Prunes old releases (keep last 5)
   - Health check
5. Cleanup: remove tarball from `/tmp/`

## Caching in CI

### Composer Cache

```yaml
- name: Cache Composer dependencies
  uses: actions/cache@v5
  with:
    path: vendor
    key: composer-${{ hashFiles('composer.lock') }}
    restore-keys: composer-
```

### NPM Cache

```yaml
- name: Cache NPM dependencies
  uses: actions/cache@v5
  with:
    path: node_modules
    key: npm-${{ hashFiles('package-lock.json') }}
    restore-keys: npm-
```

- Always cache both `vendor/` and `node_modules/`.
- Key on the lockfile hash — not `composer.json` or `package.json`.

## GitHub Actions Version Policy

**Always use the latest major version of official GitHub Actions.** Outdated versions trigger Node.js deprecation warnings and will eventually break.

| Action | Minimum version |
|--------|----------------|
| `actions/checkout` | `@v6` |
| `actions/cache` | `@v5` |
| `actions/upload-artifact` | `@v7` |
| `actions/download-artifact` | `@v7` |
| `actions/setup-node` | `@v6` |
| `shivammathur/setup-php` | `@v2` |
| `github/codeql-action/*` | `@v3` |

When adding or updating an action, check the action's releases page for the latest major version. Never use `@v3` or `@v4` for artifact actions.

## Permissions Block

Every workflow file MUST include a top-level `permissions:` block with least-privilege scopes. CodeQL raised alerts about this — always declare explicitly.

```yaml
# Example for ci.yml
permissions:
  contents: read

# Example for pr-check.yml (needs security-events for CodeQL)
permissions:
  contents: read
  security-events: write

# Example for deploy.yml
permissions:
  contents: read
  actions: read
```

## Branch Protection Rules

Configured on the `main` branch:

- **Required status checks** (from PR Check workflow): `Lint`, `Build check`, `Test (PHP 8.2)`, `Test (PHP 8.3)`, `Test (PHP 8.4)`, `CodeQL`
- Strict: branch must be up-to-date before merge
- Dismiss stale reviews on new push
- Required conversation resolution
- Required linear history (squash merge)
- Enforce for admins
- Do NOT allow force push
- Do NOT allow deletion

## Forbidden

- **NEVER use `sudo` in any workflow step or deploy script.** The deploy user owns its directories — no elevation is needed. If something requires root, it belongs in the one-time provisioning script, not in automation.
- **NEVER use the `ubuntu` (privileged) VPS account in workflows or scripts.** Only the `deploy` user is used in automation. The `ubuntu` account is for manual admin tasks only.
- Do NOT provision MySQL, Redis, or any external service in CI — tests use SQLite and array drivers.
- Do NOT store secrets in workflow files — use GitHub Environments or repository secrets.
- Do NOT use `continue-on-error: true` on lint or test steps — they must block merge.
- Do NOT skip `--test` flag on Pint — CI must fail on formatting violations.
- Do NOT use `ubuntu-latest` without pinning — use `ubuntu-24.04` for reproducibility.
- Do NOT run deploy steps on PR branches — only on `main`.
- Do NOT bypass `--force` on production migrations — Laravel requires it outside `local` env.
- Do NOT add Codecov, SonarQube, or third-party quality tools without a documented decision.
- Do NOT use outdated GitHub Action versions — see version policy table above.
