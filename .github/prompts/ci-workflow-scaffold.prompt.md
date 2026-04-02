---
description: "Generate the actual .github/workflows/ci.yml and deploy.yml GitHub Actions workflow files, fully assembled from the ci-pipeline instruction rules. Includes lint, test matrix, build, deploy, and caching."
argument-hint: "Optional flags: 'with larastan', 'without deploy', 'coverage 60'"
agent: "agent"
tools: [read, edit, search, execute]
---

# CI Workflow Scaffold

Generate the two GitHub Actions workflow files for the Motivya project, fully assembled and ready to commit.

## Before Writing

1. Read [ci-pipeline.instructions.md](../instructions/ci-pipeline.instructions.md) — this is the **authoritative source**. Every rule in that file must be reflected in the generated YAML.
2. Read [testing-conventions.instructions.md](../instructions/testing-conventions.instructions.md) for test environment expectations.
3. Search `.github/workflows/` to check if workflows already exist — update rather than duplicate.
4. Check if `phpstan.neon` or `phpstan.neon.dist` exists — include Larastan step only if present.
5. Check if `pint.json` exists — confirm Pint is configured.
6. Check if `.docker/php/Dockerfile` exists — include Docker build step only if present.

## Input

The user may provide optional flags:
- `with larastan` — include Larastan step even if config file not yet present (will add a TODO comment)
- `without deploy` — generate only `ci.yml`, skip `deploy.yml`
- `coverage N` — override the minimum coverage threshold (default: 80)
- `with manual dispatch` — add `workflow_dispatch` trigger to `deploy.yml`

If no flags are provided, generate both files with default settings.

## File 1: `.github/workflows/ci.yml`

```yaml
name: CI

on:
  push:
    branches: ['**']
  pull_request:
    branches: [main]

concurrency:
  group: ci-${{ github.ref }}
  cancel-in-progress: true

jobs:
  lint:
    name: Lint
    runs-on: ubuntu-24.04
    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: pdo_sqlite, bcmath, intl, gd, zip, pcntl, redis
          coverage: none

      - name: Cache Composer dependencies
        uses: actions/cache@v4
        with:
          path: vendor
          key: composer-${{ hashFiles('composer.lock') }}
          restore-keys: composer-

      - name: Install Composer dependencies
        run: composer install --no-interaction --prefer-dist --optimize-autoloader

      - name: Run Laravel Pint
        run: vendor/bin/pint --test

      # Include only if phpstan.neon or phpstan.neon.dist exists:
      # - name: Run Larastan
      #   run: vendor/bin/phpstan analyse --memory-limit=512M

      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: '22'

      - name: Cache NPM dependencies
        uses: actions/cache@v4
        with:
          path: node_modules
          key: npm-${{ hashFiles('package-lock.json') }}
          restore-keys: npm-

      - name: Install Node dependencies
        run: npm ci

      - name: Build assets
        run: npm run build

  test:
    name: Test (PHP ${{ matrix.php }})
    runs-on: ubuntu-24.04
    needs: lint
    strategy:
      fail-fast: true
      matrix:
        php: ['8.2', '8.3', '8.4']

    env:
      APP_ENV: testing
      DB_CONNECTION: sqlite
      DB_DATABASE: ':memory:'
      CACHE_STORE: array
      QUEUE_CONNECTION: sync
      SESSION_DRIVER: array
      MAIL_MAILER: array

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Setup PHP ${{ matrix.php }}
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          extensions: pdo_sqlite, bcmath, intl, gd, zip, pcntl, redis
          coverage: pcov

      - name: Cache Composer dependencies
        uses: actions/cache@v4
        with:
          path: vendor
          key: composer-${{ matrix.php }}-${{ hashFiles('composer.lock') }}
          restore-keys: composer-${{ matrix.php }}-

      - name: Install Composer dependencies
        run: composer install --no-interaction --prefer-dist --optimize-autoloader

      - name: Prepare application
        run: |
          cp .env.example .env
          php artisan key:generate

      - name: Run migrations
        run: php artisan migrate --force

      - name: Run Pest tests
        run: vendor/bin/pest --parallel --coverage --min=80
```

### ci.yml Rules

**Triggers:**
- `push` on all branches — every push gets lint + test
- `pull_request` targeting `main` — ensures PR checks
- `concurrency` with `cancel-in-progress: true` — cancels stale runs on the same branch

**Lint job:**
- Single PHP version (8.3) — linting doesn't need a matrix
- `coverage: none` — no coverage overhead for lint
- Pint with `--test` — fails on violations, never auto-fixes
- Larastan only if config exists — commented out by default, uncomment when ready
- Node + npm for Vite build validation

**Test job:**
- `needs: lint` — only runs after lint passes
- PHP 8.2 / 8.3 / 8.4 matrix with `fail-fast: true`
- `coverage: pcov` — faster than Xdebug
- SQLite `:memory:` + array drivers — no external services
- `APP_KEY` generated via `key:generate` (not hardcoded in env block)
- `--parallel` for speed, `--coverage --min=80` for quality gate
- Composer cache keyed on `matrix.php` + `composer.lock` hash

**Conditional steps:**

| Step | Condition | How to detect |
|------|-----------|--------------|
| Larastan | `phpstan.neon` exists | Search project root |
| Translation parity | Test file exists | Search `tests/Feature/TranslationParityTest.php` |
| Larastan step | `with larastan` flag | User explicitly requests |

If the condition is not met, include the step as a YAML comment with a note about when to enable it.

## File 2: `.github/workflows/deploy.yml`

```yaml
name: Deploy

on:
  push:
    branches: [main]

concurrency:
  group: deploy-production
  cancel-in-progress: false

jobs:
  build-and-deploy:
    name: Build & Deploy
    runs-on: ubuntu-24.04
    environment:
      name: motivya.metanull.eu
      url: https://motivya.metanull.eu

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: pdo_sqlite, bcmath, intl, gd, zip, pcntl, redis
          coverage: none

      - name: Cache Composer dependencies
        uses: actions/cache@v4
        with:
          path: vendor
          key: composer-${{ hashFiles('composer.lock') }}
          restore-keys: composer-

      - name: Install Composer dependencies
        run: composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: '22'

      - name: Cache NPM dependencies
        uses: actions/cache@v4
        with:
          path: node_modules
          key: npm-${{ hashFiles('package-lock.json') }}
          restore-keys: npm-

      - name: Install Node dependencies
        run: npm ci

      - name: Build production assets
        run: npm run build

      # --- Deploy to OVH VPS via SSH ---
      - name: Deploy to VPS
        uses: appleboy/ssh-action@v1
        with:
          host: ${{ secrets.VPS_HOST }}
          username: ${{ secrets.VPS_SSH_USER }}
          key: ${{ secrets.VPS_SSH_KEY }}
          script: bash /opt/motivya/src/scripts/deploy.sh
```

### deploy.yml Rules

**Triggers:**
- `push` to `main` only — never on PR branches
- `concurrency` with `cancel-in-progress: false` — never cancel an in-progress deploy
- Add `workflow_dispatch` only if user requests manual dispatch

**Environment:**
- Uses GitHub Environments `production` — secrets stored there, not in workflow
- `url` set to `https://motivya.metanull.eu`

**Build:**
- `--no-dev` on Composer — production has no dev dependencies
- Assets compiled with production Vite config

**Deploy:**
- SSH into OVH VPS using `appleboy/ssh-action@v1`
- Runs `scripts/deploy.sh` on the VPS — handles git pull, composer install, migrations, Docker rebuild, cache warming, health check
- All secrets (`VPS_HOST`, `VPS_SSH_KEY`, `VPS_SSH_USER`) stored in GitHub repository secrets
- Application secrets (Stripe, DB) live in `.env.production` on the VPS — never in GitHub

**Required GitHub Secrets:**

| Secret | Value |
|--------|-------|
| `VPS_HOST` | OVH VPS IP address |
| `VPS_SSH_KEY` | Private SSH key for `deploy` user |
| `VPS_SSH_USER` | `deploy` |

## Customization Flags

### `with larastan`

Uncomment the Larastan step in `ci.yml` and add it as active:

```yaml
      - name: Run Larastan
        run: vendor/bin/phpstan analyse --memory-limit=512M
```

### `without deploy`

Generate only `ci.yml`. Do not create `deploy.yml`.

### `coverage N`

Replace `--min=80` with `--min=N` in the Pest test step:

```yaml
      - name: Run Pest tests
        run: vendor/bin/pest --parallel --coverage --min=60
```

### `with manual dispatch`

Add to `deploy.yml` triggers:

```yaml
on:
  push:
    branches: [main]
  workflow_dispatch:
```

## Output Order

1. **`.github/workflows/ci.yml`** — lint + test pipeline
2. **`.github/workflows/deploy.yml`** — build + deploy pipeline (unless `without deploy`)
3. **Summary table** of jobs, triggers, and conditions

## Validation

After generating, verify:
- `ubuntu-24.04` pinned — never `ubuntu-latest`
- `actions/checkout@v4`, `actions/cache@v4`, `actions/setup-node@v4` — latest major versions
- `shivammathur/setup-php@v2` with correct extensions and coverage driver
- `fail-fast: true` on test matrix
- PHP matrix: `['8.2', '8.3', '8.4']` — no older versions
- `composer install` has `--no-interaction --prefer-dist --optimize-autoloader`
- `npm ci` (not `npm install`)
- Test env uses SQLite + array drivers — no MySQL, no Redis service containers
- Pint uses `--test` flag
- Coverage uses PCOV, not Xdebug
- Deploy uses `--no-dev` for Composer
- Deploy concurrency does NOT cancel in-progress
- No secrets hardcoded in workflow files
- No `continue-on-error: true` on lint or test steps
- Composer cache key includes PHP version in test job
