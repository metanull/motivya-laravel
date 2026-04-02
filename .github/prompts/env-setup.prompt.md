---
description: "Generate a complete .env.example file with all documented environment variables and sensible local development defaults for the Motivya project. Covers Laravel core, MySQL, Valkey, Stripe, Google OAuth, Mailpit, i18n, and storage settings."
argument-hint: "Optional: 'with meilisearch' or 'production' for non-default profiles"
agent: "agent"
tools: [read, edit, search]
---

# Env Setup

Generate a `.env.example` file with every environment variable the Motivya project requires, grouped by concern with sensible local development defaults.

## Before Writing

1. Read [copilot-instructions.md](../copilot-instructions.md) for the tech stack overview.
2. Read [docker-setup.instructions.md](../instructions/docker-setup.instructions.md) for Docker service hostnames and ports.
3. Read [stripe-connect.instructions.md](../instructions/stripe-connect.instructions.md) for Stripe-specific env vars.
4. Read [auth-roles.instructions.md](../instructions/auth-roles.instructions.md) for Google OAuth and Sanctum.
5. Read [api-conventions.instructions.md](../instructions/api-conventions.instructions.md) for CORS and rate limiting.
6. Read [i18n-localization.instructions.md](../instructions/i18n-localization.instructions.md) for locale defaults.
7. Check if `.env.example` already exists — update rather than overwrite.

## Rules

- Every variable must have a **comment** explaining its purpose
- Group variables under section headers using `#---` separator comments
- Default values target **local development with Docker** (MySQL on `mysql:3306`, Valkey on `valkey:6379`, Mailpit on `mailpit:1025`)
- Secrets use **placeholder values** (`your-key-here`, `sk_test_...`) — never real credentials
- No variable should reference `env()` — this file IS the `.env`
- Include `# Optional:` prefix for variables that have working defaults in `config/` files
- Boolean values as `true` / `false` (lowercase)
- Port numbers as integers (no quotes)

## Variable Groups

Generate these sections in order. Every variable listed below MUST be included.

### 1. Application

```env
#--- Application ---
APP_NAME=Motivya
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_TIMEZONE=Europe/Brussels
APP_URL=http://localhost:8000
APP_LOCALE=fr
APP_FALLBACK_LOCALE=fr
APP_FAKER_LOCALE=fr_BE
```

Notes:
- `APP_KEY` is always empty — generated via `php artisan key:generate`
- `APP_TIMEZONE` must be `Europe/Brussels` for Belgian date logic
- `APP_LOCALE` and `APP_FALLBACK_LOCALE` both `fr` — French is the default
- `APP_FAKER_LOCALE` is `fr_BE` for realistic Belgian test data

### 2. Logging

```env
#--- Logging ---
LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=debug
```

### 3. Database

```env
#--- Database ---
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=motivya
DB_USERNAME=motivya
DB_PASSWORD=secret
```

Notes:
- `DB_HOST=mysql` matches the Docker service name
- For non-Docker local dev, use `127.0.0.1`
- Tests override this with SQLite `:memory:` in `phpunit.xml`

### 4. Cache, Queue, Session (Valkey)

```env
#--- Cache / Queue / Session (Valkey) ---
CACHE_STORE=redis
REDIS_CLIENT=phpredis
REDIS_HOST=valkey
REDIS_PORT=6379
REDIS_PASSWORD=null
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120
```

Notes:
- `REDIS_HOST=valkey` matches the Docker service name (Valkey is Redis-compatible)
- `REDIS_CLIENT=phpredis` — the Docker image installs the phpredis extension
- For non-Docker dev, change `REDIS_HOST` to `127.0.0.1`

### 5. Mail (Mailpit)

```env
#--- Mail (Mailpit) ---
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=noreply@motivya.test
MAIL_FROM_NAME="${APP_NAME}"
```

Notes:
- Mailpit captures all email at `http://localhost:8025` — never send real mail in dev
- For non-Docker dev, change `MAIL_HOST` to `127.0.0.1`

### 6. Stripe

```env
#--- Stripe ---
STRIPE_KEY=pk_test_your-publishable-key-here
STRIPE_SECRET=sk_test_your-secret-key-here
STRIPE_WEBHOOK_SECRET=whsec_your-webhook-secret-here
# Optional: Stripe Connect platform fee percentage (overrides default in config)
# STRIPE_PLATFORM_FEE_PERCENT=
```

Notes:
- Use Stripe test mode keys for local dev — never production keys in `.env.example`
- `STRIPE_WEBHOOK_SECRET` is the signing secret from the Stripe dashboard or CLI
- All Stripe config accessed via `config('services.stripe.*')` — never `env()` directly

### 7. Google OAuth

```env
#--- Google OAuth ---
GOOGLE_CLIENT_ID=your-google-client-id-here
GOOGLE_CLIENT_SECRET=your-google-client-secret-here
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
```

Notes:
- Required for social login (Socialite `google` driver)
- `GOOGLE_REDIRECT_URI` auto-derives from `APP_URL`

### 8. Storage

```env
#--- Storage ---
FILESYSTEM_DISK=local
# Optional: S3-compatible storage (production only)
# AWS_ACCESS_KEY_ID=
# AWS_SECRET_ACCESS_KEY=
# AWS_DEFAULT_REGION=eu-west-1
# AWS_BUCKET=motivya
# AWS_ENDPOINT=
# AWS_USE_PATH_STYLE_ENDPOINT=false
```

Notes:
- Local filesystem for dev — S3-compatible in production (OVH / Laravel Cloud)
- S3 vars are commented out — uncomment for staging/production

### 9. CORS

```env
#--- CORS ---
# Optional: Comma-separated allowed origins (defaults to APP_URL in config)
# CORS_ALLOWED_ORIGINS=http://localhost:3000,http://localhost:5173
```

Notes:
- Only needed if a frontend SPA runs on a different port
- Defaults to `APP_URL` if not set

### 10. Sanctum

```env
#--- Sanctum ---
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:8000,127.0.0.1,127.0.0.1:8000
```

### 11. Vite

```env
#--- Vite ---
VITE_APP_NAME="${APP_NAME}"
```

### 12. Optional: Meilisearch

Include this section only if the user requests it (e.g., `/env-setup with meilisearch`):

```env
#--- Meilisearch (optional) ---
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://meilisearch:7700
MEILISEARCH_KEY=
```

## Output

1. Generate the complete `.env.example` file at the project root
2. Confirm `.env.example` is NOT in `.gitignore` (it should be committed)
3. Confirm `.env` IS in `.gitignore` (it should never be committed)
4. Print a summary table:

```
| Group           | Variables | Notes                        |
|-----------------|-----------|------------------------------|
| Application     | 10        | APP_KEY left empty           |
| Logging         | 3         |                              |
| Database        | 6         | Docker MySQL defaults        |
| Cache/Queue     | 8         | Valkey (Redis-compatible)    |
| Mail            | 8         | Mailpit capture              |
| Stripe          | 3 (+1)    | Test keys placeholder        |
| Google OAuth    | 3         | Socialite driver             |
| Storage         | 1 (+6)    | S3 commented out             |
| CORS            | 0 (+1)    | Commented out                |
| Sanctum         | 1         | Stateful domains             |
| Vite            | 1         |                              |
| Total           | ~44       |                              |
```

## Validation

After generating, verify:
- No real API keys, passwords, or secrets — only placeholders
- `APP_KEY` is empty (user runs `key:generate`)
- Docker hostnames (`mysql`, `valkey`, `mailpit`) used, not `127.0.0.1`
- `APP_TIMEZONE` is `Europe/Brussels`
- `APP_LOCALE` and `APP_FALLBACK_LOCALE` are both `fr`
- Stripe keys use `pk_test_` / `sk_test_` / `whsec_` prefixes
- No variable appears twice
- All groups have section header comments
