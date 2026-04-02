---
description: "Generate the complete Docker local dev environment: docker-compose.yml, Dockerfile, Nginx config, php.ini, init.sql, .env.docker, and .gitignore updates. Follows Motivya's production-parity stack (Nginx, PHP 8.2-FPM, MySQL 8.0, Valkey 8, Mailpit)."
argument-hint: "Optional: include extra services, e.g. 'with stripe-cli and meilisearch'"
agent: "agent"
tools: [read, edit, search, execute]
---

# Docker Scaffold

Generate all files for the Motivya Docker local development environment in one pass.

## Before Writing

1. Read [docker-setup.instructions.md](../instructions/docker-setup.instructions.md) — this is the authoritative source for every rule below.
2. Check if any Docker files already exist in the workspace (`docker-compose.yml`, `.docker/`, `Dockerfile`). If they do, update rather than overwrite.
3. Read `.gitignore` to confirm whether Docker entries are already present.

## User Input

The user may optionally request extra services. Map them:

| Request | Service to add |
|---------|---------------|
| "stripe" or "webhooks" | `stripe-cli` service with webhook forwarding |
| "search" or "meilisearch" | `meilisearch` service on port 7700 |
| (no extras) | Only the 4 required services |

If the user's input is empty or just "docker-scaffold", generate the 5 required services only.

## Files to Generate

### 1. `.docker/php/Dockerfile`

```dockerfile
FROM php:8.2-fpm
```

Requirements:
- Install system deps: `libicu-dev`, `libzip-dev`, `libpng-dev`, `libjpeg-dev`, `libfreetype6-dev`
- Install PHP extensions via `docker-php-ext-install`: `pdo_mysql`, `bcmath`, `intl`, `gd`, `zip`, `pcntl`, `opcache`
- Install `redis` extension via `pecl` (phpredis — compatible with Valkey)
- Install Composer: `COPY --from=composer:latest /usr/bin/composer /usr/bin/composer`
- Install Node 20: `COPY --from=node:20-slim /usr/local/bin/node /usr/local/bin/node` and `COPY --from=node:20-slim /usr/local/lib/node_modules /usr/local/lib/node_modules` with symlink for `npm`
- Set `WORKDIR /var/www/html`
- Do NOT `COPY` application code — bind mount handles this
- Do NOT install Xdebug — that goes in `docker-compose.override.yml`

### 2. `.docker/php/php.ini`

Dev-appropriate overrides only:

```ini
display_errors = On
error_reporting = E_ALL
memory_limit = 256M
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 60
opcache.enable = 0
```

### 3. `.docker/nginx/default.conf`

```nginx
server {
    listen 80;
    server_name localhost;
    root /var/www/html/public;
    index index.php;

    client_max_body_size 10M;

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
- `root` points to Laravel's `public/` directory
- PHP proxied to `app:9000` via FastCGI
- Static assets served directly by Nginx
- `client_max_body_size` matches `php.ini` upload limit

### 4. `.docker/mysql/init.sql`

```sql
CREATE DATABASE IF NOT EXISTS motivya CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS motivya_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Two databases: `motivya` for dev, `motivya_testing` for integration tests that need MySQL.

### 5. `.docker/.env.docker`

```env
MYSQL_ROOT_PASSWORD=rootsecret
MYSQL_DATABASE=motivya
MYSQL_USER=motivya
MYSQL_PASSWORD=secret

STRIPE_SECRET_KEY=sk_test_placeholder
```

- Placeholder values only — the user replaces with real keys.
- This file must be gitignored.

### 6. `docker-compose.yml`

Generate with these exact constraints:

**app service:**
- Build from `.docker/php/Dockerfile`
- Bind-mount `.:/var/www/html`
- Bind-mount `.docker/php/php.ini:/usr/local/etc/php/conf.d/99-dev.ini`
- `env_file: .docker/.env.docker`
- Environment variables mapping to Laravel's `.env` keys (DB_HOST=mysql, REDIS_HOST=valkey, MAIL_HOST=mailpit, etc.)
- `depends_on` MySQL and Valkey with `condition: service_healthy`
- Expose port `9000` internally only — not mapped to host
- Working dir `/var/www/html`
- No `command:` override — runs PHP-FPM by default from the base image
- Health check: `php -r "echo 'ok';"` or `php-fpm` process check

**nginx service:**
- Image: `nginx:1.26-alpine` — pinned, no `latest`
- Bind-mount `.:/var/www/html` (for static asset serving from `public/`)
- Bind-mount `.docker/nginx/default.conf:/etc/nginx/conf.d/default.conf`
- Port: `127.0.0.1:8000:80`
- `depends_on` app with `condition: service_healthy`
- Health check: `curl -f http://localhost/ || exit 1`

**mysql service:**
- Image: `mysql:8.0` — pinned, no `latest`
- `env_file: .docker/.env.docker`
- Port: `127.0.0.1:3306:3306` — localhost only, never `0.0.0.0`
- Volume: `mysql-data:/var/lib/mysql`
- Volume: `.docker/mysql/init.sql:/docker-entrypoint-initdb.d/init.sql`
- Health check: `mysqladmin ping -h localhost`

**valkey service:**
- Image: `valkey/valkey:8` — pinned
- Port: `127.0.0.1:6379:6379` — localhost only
- Volume: `valkey-data:/data`
- Health check: `valkey-cli ping`

**mailpit service:**
- Image: `axllent/mailpit:latest`
- Ports: `127.0.0.1:8025:8025` (UI), `127.0.0.1:1025:1025` (SMTP)
- Health check: `wget --spider -q http://localhost:8025`

**stripe-cli service (only if requested):**
- Image: `stripe/stripe-cli:latest`
- Command: `listen --forward-to http://nginx:80/stripe/webhook`
- Environment: `STRIPE_API_KEY` from `.env.docker`
- `depends_on` nginx with `condition: service_healthy`
- Profile: `stripe` (so it doesn't start by default unless `--profile stripe` is passed)

**meilisearch service (only if requested):**
- Image: `getmeili/meilisearch:v1`
- Port: `127.0.0.1:7700:7700`
- Volume: `meilisearch-data:/meili_data`
- Health check: `wget --spider -q http://localhost:7700/health`

**Volumes section:** Named volumes for `mysql-data`, `valkey-data`, and optionally `meilisearch-data`.

**Networks section:** Single `motivya` bridge network, all services attached.

### 7. `.gitignore` Updates

Append these lines if not already present:

```gitignore
# Docker
.docker/.env.docker
docker-compose.override.yml
```

## Output Order

1. `.docker/php/Dockerfile`
2. `.docker/php/php.ini`
3. `.docker/nginx/default.conf`
4. `.docker/mysql/init.sql`
5. `.docker/.env.docker`
6. `docker-compose.yml`
7. `.gitignore` update
8. Summary with first-run commands:

```bash
docker compose up -d
docker compose exec app composer install
docker compose exec app cp .env.example .env
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
docker compose exec app npm install && npm run build
```

## Validation

After generating all files, verify:
- No `0.0.0.0` port bindings on MySQL, Valkey, or Nginx — must be `127.0.0.1`
- No secrets hardcoded in `docker-compose.yml` — all in `.env.docker`
- No `latest` tag on MySQL, Valkey, or Nginx images — pinned versions only
- No `COPY` of application code in the Dockerfile
- No Xdebug in the base Dockerfile
- No `php artisan serve` as the default app command — FPM + Nginx is the standard
- Health checks present on every service
- `depends_on` with `condition: service_healthy` on app (for mysql/valkey) and nginx (for app)
- `.docker/.env.docker` and `docker-compose.override.yml` are in `.gitignore`
