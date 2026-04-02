---
description: "Use when creating or modifying GitHub Actions workflows, CI/CD pipeline configuration, automated test matrix, linting steps, Docker image builds, deployment triggers, or .github/workflows/ contents. Covers the full Motivya pipeline: lint, test, build, and deploy stages."
applyTo: ".github/workflows/**"
---

# CI/CD Pipeline Rules

## Pipeline Overview

Every push and pull request triggers a multi-stage pipeline. All stages must pass before merge is allowed.

```
┌─────────┐    ┌──────────┐    ┌─────────┐    ┌──────────┐
│  Lint   │───▶│   Test   │───▶│  Build  │───▶│  Deploy  │
└─────────┘    └──────────┘    └─────────┘    └──────────┘
```

| Stage | Runs on | Blocks merge | Runs on deploy branch only |
|-------|---------|-------------|---------------------------|
| Lint | Every push + PR | Yes | No |
| Test | Every push + PR | Yes | No |
| Build | Push to `main` only | — | Yes |
| Deploy | Push to `main` only | — | Yes |

## Workflow Files

```
.github/workflows/
├── ci.yml          # Lint + Test (every push/PR)
├── deploy.yml      # Build + Deploy (main only)
```

Keep it to 2 files maximum. Do not create per-stage workflow files.

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

## Stage 3: Build (`deploy.yml`)

### Docker Image Build

```yaml
- name: Build Docker image
  run: |
    docker build -t motivya-app:${{ github.sha }} -f .docker/php/Dockerfile .
```

- Tag images with the Git SHA — never `latest` for production.
- Build from the same `.docker/php/Dockerfile` used in local dev.
- Do NOT push images to a registry from this step — deploy stage handles that.

### Asset Compilation

```yaml
- name: Build production assets
  run: |
    npm ci
    npm run build
```

- Assets compiled with production Vite config.
- The `public/build/` output is included in the Docker image or deployment artifact.

## Stage 4: Deploy (`deploy.yml`)

### Trigger

```yaml
on:
  push:
    branches: [main]
```

- Deploy only on push to `main` — never on PR.
- No manual dispatch unless explicitly requested.

### Environment

```yaml
environment:
  name: production
  url: https://motivya.be
```

- Use GitHub Environments for production secrets.
- Secrets (`STRIPE_SECRET`, `DB_PASSWORD`, etc.) are stored in the GitHub Environment — never in workflow files.

### Deploy Steps

The exact deployment target depends on the hosting platform (Laravel Cloud, OVH, etc.). The workflow should:

1. Build production Docker image (or deployment artifact)
2. Push to container registry (if Docker-based)
3. Run `php artisan migrate --force` on the production database
4. Clear and warm caches: `config:cache`, `route:cache`, `view:cache`, `event:cache`
5. Restart workers: `queue:restart`
6. Verify health check endpoint responds 200

```yaml
- name: Post-deploy cache warm
  run: |
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache

- name: Restart queue workers
  run: php artisan queue:restart

- name: Health check
  run: curl --fail --silent --max-time 10 ${{ vars.APP_URL }}/health
```

## Caching in CI

### Composer Cache

```yaml
- name: Cache Composer dependencies
  uses: actions/cache@v4
  with:
    path: vendor
    key: composer-${{ hashFiles('composer.lock') }}
    restore-keys: composer-
```

### NPM Cache

```yaml
- name: Cache NPM dependencies
  uses: actions/cache@v4
  with:
    path: node_modules
    key: npm-${{ hashFiles('package-lock.json') }}
    restore-keys: npm-
```

- Always cache both `vendor/` and `node_modules/`.
- Key on the lockfile hash — not `composer.json` or `package.json`.
- Use `actions/cache@v4` — not deprecated versions.

## Branch Protection Rules

Configure on the `main` branch:

- Require status checks to pass: `lint`, `test` (all matrix jobs)
- Require PR review (at least 1 approval)
- Do NOT allow force push
- Do NOT allow deletion

## Forbidden

- Do NOT provision MySQL, Redis, or any external service in CI — tests use SQLite and array drivers.
- Do NOT store secrets in workflow files — use GitHub Environments or repository secrets.
- Do NOT use `continue-on-error: true` on lint or test steps — they must block merge.
- Do NOT skip `--test` flag on Pint — CI must fail on formatting violations.
- Do NOT use `ubuntu-latest` without pinning — use `ubuntu-24.04` for reproducibility.
- Do NOT run deploy steps on PR branches — only on `main`.
- Do NOT bypass `--force` on production migrations — Laravel requires it outside `local` env.
- Do NOT add Codecov, SonarQube, or third-party quality tools without a documented decision.
