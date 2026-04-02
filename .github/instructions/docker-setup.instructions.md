---
description: "Use when creating or modifying Docker Compose files, Dockerfiles, container configuration, local dev environment setup, service orchestration, or .docker/ directory contents. Covers the Nginx, PHP-FPM, MySQL, Valkey, and Mailpit container stack for Motivya local development."
applyTo: "docker-compose*.yml,Dockerfile*,.docker/**"
---

# Docker Local Development Environment

## Purpose

Provide a single `docker compose up -d` command that mirrors production services locally.
Tests still use SQLite `:memory:` — Docker MySQL is for manual dev and integration testing only.

## Required Services

| Service | Image | Port | Purpose |
|---------|-------|------|---------|
| `app` | Custom PHP 8.2-FPM + Node 20 | 9000 (internal) | PHP-FPM process — not exposed directly |
| `nginx` | `nginx:1.26-alpine` | 8000 | Reverse proxy to `app:9000` — production-parity web server |
| `mysql` | `mysql:8.0` | 3306 | Production-parity relational DB |
| `valkey` | `valkey/valkey:8` | 6379 | Cache + queue + session (Redis-compatible) |
| `mailpit` | `axllent/mailpit:latest` | 8025 (UI), 1025 (SMTP) | Email capture — never send real mail in dev |

### Optional Services

Add only when the feature is being actively developed:

| Service | Image | Port | Purpose |
|---------|-------|------|---------|
| `meilisearch` | `getmeili/meilisearch:v1` | 7700 | Full-text session search (when Scout is integrated) |
| `stripe-cli` | `stripe/stripe-cli:latest` | — | Webhook forwarding to `app:8000/stripe/webhook` |

## File Structure

```
docker-compose.yml          # Main orchestration
.docker/
  php/
    Dockerfile              # PHP 8.2-FPM + extensions + Composer + Node
    php.ini                 # Dev overrides (display_errors, xdebug)
  nginx/
    default.conf            # Nginx vhost: proxy to app:9000, serve static files
  mysql/
    init.sql                # Optional: CREATE DATABASE IF NOT EXISTS motivya
  .env.docker               # Docker-specific env overrides (do NOT commit secrets)
```

## Dockerfile Rules (`.docker/php/Dockerfile`)

```dockerfile
FROM php:8.2-fpm

# Required PHP extensions for Laravel + MySQL + Valkey
# pdo_mysql, bcmath, intl, gd, zip, redis (phpredis), pcntl, opcache
```

- Base image: `php:8.2-fpm` — match the project's PHP 8.2+ requirement.
- Install extensions via `docker-php-ext-install` or `pecl`: `pdo_mysql`, `bcmath`, `intl`, `gd`, `zip`, `redis` (phpredis for Valkey compatibility), `pcntl`, `opcache`.
- Install Composer via `COPY --from=composer:latest`.
- Install Node 20 via `nvm` or `node:20` multi-stage copy — needed for `npm run build`.
- Set `WORKDIR /var/www/html`.
- Do NOT copy application code into the image — use a bind mount in `docker-compose.yml`.
- Do NOT install Xdebug in the base image — use a separate `docker-compose.override.yml` or build arg.

## Nginx Config Rules (`.docker/nginx/default.conf`)

```nginx
server {
    listen 80;
    server_name localhost;
    root /var/www/html/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff2?)$ {
        expires 1d;
        log_not_found off;
    }
}
```

Rules:
- `root` must point to Laravel's `public/` directory.
- PHP requests proxied to `app:9000` via FastCGI — the `app` service name is the hostname.
- Static assets served directly by Nginx — never forwarded to PHP-FPM.
- No SSL in dev — production SSL is handled by the hosting platform (Laravel Cloud / OVH).
- Max upload size should match `php.ini`: `client_max_body_size 10M;`.

## docker-compose.yml Rules

### Volumes

```yaml
volumes:
  mysql-data:       # Named volume — persists across restarts
  valkey-data:      # Named volume
```

- Bind-mount the project root to `/var/www/html` in both the `app` and `nginx` services.
- Use named volumes for `mysql-data` and `valkey-data` — never bind-mount database storage.
- Bind-mount `.docker/php/php.ini` to `/usr/local/etc/php/conf.d/99-dev.ini`.
- Bind-mount `.docker/nginx/default.conf` to `/etc/nginx/conf.d/default.conf` in the `nginx` service.

### Networks

- Single `motivya` bridge network — all services on the same network.
- Service names (`app`, `mysql`, `valkey`, `mailpit`, `nginx`) are the hostnames — use them in `.env`.

### Environment Variables

Map to Laravel's `.env` expectations:

```yaml
app:
  environment:
    DB_CONNECTION: mysql
    DB_HOST: mysql
    DB_PORT: 3306
    DB_DATABASE: motivya
    DB_USERNAME: motivya
    DB_PASSWORD: secret
    CACHE_STORE: redis
    REDIS_HOST: valkey
    REDIS_PORT: 6379
    QUEUE_CONNECTION: redis
    SESSION_DRIVER: redis
    MAIL_MAILER: smtp
    MAIL_HOST: mailpit
    MAIL_PORT: 1025
    MAIL_FROM_ADDRESS: "noreply@motivya.test"
    APP_URL: "http://localhost:8000"
```

- Never hardcode secrets in `docker-compose.yml` — use `env_file: .docker/.env.docker`.
- The `.env.docker` file must be in `.gitignore`.
- MySQL root password and app password go in `.env.docker`, not inline.

### Health Checks

Every service must have a health check:

```yaml
mysql:
  healthcheck:
    test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
    interval: 10s
    retries: 5

valkey:
  healthcheck:
    test: ["CMD", "valkey-cli", "ping"]
    interval: 5s
    retries: 3

mailpit:
  healthcheck:
    test: ["CMD", "wget", "--spider", "-q", "http://localhost:8025"]
    interval: 10s
    retries: 3

nginx:
  healthcheck:
    test: ["CMD-SHELL", "curl -f http://localhost/ || exit 1"]
    interval: 10s
    retries: 3
```

- The `app` service must `depends_on` MySQL and Valkey with `condition: service_healthy`.
- The `nginx` service must `depends_on` the `app` service with `condition: service_healthy`.
- Add a health check to the `app` service using `php-fpm` ping:

```yaml
app:
  healthcheck:
    test: ["CMD-SHELL", "php-fpm-healthcheck || exit 1"]
    interval: 10s
    retries: 3
```

Alternatively, use `fcgi` or a simple `test: ["CMD", "php", "-r", "echo 'ok';"]` if `php-fpm-healthcheck` is not installed.

### Stripe CLI (Optional)

When present, forward webhooks to the Nginx container (which proxies to PHP-FPM):

```yaml
stripe-cli:
  image: stripe/stripe-cli:latest
  command: listen --forward-to http://nginx:80/stripe/webhook
  environment:
    STRIPE_API_KEY: ${STRIPE_SECRET_KEY}
  depends_on:
    app:
      condition: service_healthy
```

- Stripe API keys come from `.env.docker` — never commit them.

## .gitignore Additions

```gitignore
.docker/.env.docker
docker-compose.override.yml
```

- `docker-compose.override.yml` is for personal Xdebug/port overrides — never commit.
- `.env.docker` contains credentials — never commit.

## Parity with Production

| Concern | Production | Docker Dev |
|---------|-----------|------------|
| Web server | Nginx | Nginx 1.26-alpine (exact match) |
| PHP version | 8.2+ | 8.2-fpm (exact match) |
| Database | MySQL 8.0 | MySQL 8.0 (exact match) |
| Cache/Queue | Valkey | Valkey 8 (exact match) |
| Storage | S3-compatible | Local filesystem (bind mount) |
| Mail | Real SMTP | Mailpit (captured, never sent) |
| SSL | Yes | No — `http://localhost` in dev |

- Storage stays on local filesystem in dev — do NOT add MinIO unless explicitly requested.
- Tests still use SQLite `:memory:` regardless of Docker — do not change `phpunit.xml` database config.

## Common Commands

After `docker compose up -d`:

```bash
# First-time setup
docker compose exec app composer install
docker compose exec app cp .env.example .env
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
docker compose exec app npm install && npm run build

# Run tests (uses SQLite, not Docker MySQL)
docker compose exec app php artisan test

# Fresh database
docker compose exec app php artisan migrate:fresh --seed

# Queue worker
docker compose exec app php artisan queue:work redis
```

## Forbidden

- Do NOT use `docker-compose` (v1) — use `docker compose` (v2 plugin syntax).
- Do NOT expose MySQL port `3306` to `0.0.0.0` — bind to `127.0.0.1:3306:3306`.
- Do NOT use `latest` tag for MySQL or Valkey — pin to major version (`mysql:8.0`, `valkey/valkey:8`).
- Do NOT add phpMyAdmin, Adminer, or Redis Commander — use CLI or IDE database tools.
- Do NOT run `php artisan serve` as the default `app` entrypoint — the `app` service runs PHP-FPM and Nginx handles HTTP.
- `php artisan serve` is acceptable ONLY as a temporary `command:` override in `docker-compose.override.yml` for quick local debugging without Nginx. It must never be committed as the default.
- Do NOT store application state in the container — all data in named volumes or bind mounts.
- Do NOT use `latest` tag for Nginx — pin to major.minor version (`nginx:1.26-alpine`).
