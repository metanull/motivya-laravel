#!/usr/bin/env bash
#
# deploy.sh — Deploy Motivya Laravel app to OVH VPS
#
# Usage: Called by GitHub Actions via SSH, or manually:
#   ssh root@<VPS_IP> "bash /opt/motivya/src/scripts/deploy.sh"
#
# Supports two modes:
#   - Bare-metal: PHP-FPM + Nginx (initial scaffold, no Docker yet)
#   - Docker:     docker-compose.prod.yml (when available)
#
# The script is idempotent — safe to re-run.
#
set -euo pipefail

# --- Configuration -----------------------------------------------------------
APP_DIR="/opt/motivya"
SRC_DIR="${APP_DIR}/src"
DOMAIN="motivya.metanull.eu"
PHP_VERSION="8.3"

# --- Colors -------------------------------------------------------------------
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

info()  { echo -e "${GREEN}[DEPLOY]${NC} $1"; }
warn()  { echo -e "${YELLOW}[DEPLOY]${NC} $1"; }
error() { echo -e "${RED}[DEPLOY]${NC} $1"; exit 1; }

# --- Detect mode --------------------------------------------------------------
COMPOSE_FILE="${APP_DIR}/docker-compose.prod.yml"
if [[ -f "$COMPOSE_FILE" ]] && command -v docker &> /dev/null; then
    MODE="docker"
else
    MODE="bare-metal"
fi
info "Deploy mode: ${MODE}"

# --- Pre-flight ---------------------------------------------------------------
[[ ! -d "$SRC_DIR" ]] && error "Source directory ${SRC_DIR} does not exist. Clone the repo first."
cd "$SRC_DIR"

# =============================================================================
# BARE-METAL MODE: PHP-FPM + Nginx on the host
# =============================================================================
if [[ "$MODE" == "bare-metal" ]]; then

    # --- 1. Install runtime dependencies (idempotent) -------------------------
    if ! command -v "php${PHP_VERSION}" &> /dev/null && ! dpkg -l "php${PHP_VERSION}-fpm" &> /dev/null 2>&1; then
        info "Installing PHP ${PHP_VERSION}, Nginx, and dependencies..."
        export DEBIAN_FRONTEND=noninteractive
        apt-get update -qq
        apt-get install -y -qq \
            "php${PHP_VERSION}-fpm" "php${PHP_VERSION}-sqlite3" "php${PHP_VERSION}-mbstring" \
            "php${PHP_VERSION}-xml" "php${PHP_VERSION}-curl" "php${PHP_VERSION}-zip" \
            "php${PHP_VERSION}-bcmath" "php${PHP_VERSION}-intl" "php${PHP_VERSION}-gd" \
            nginx unzip curl git sqlite3
    fi

    if ! command -v composer &> /dev/null; then
        info "Installing Composer..."
        curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
    fi

    if ! command -v node &> /dev/null; then
        info "Installing Node.js 22..."
        curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
        DEBIAN_FRONTEND=noninteractive apt-get install -y -qq nodejs
    fi

    # --- 2. Pull latest code --------------------------------------------------
    info "Pulling latest code from main..."
    git fetch origin main
    git reset --hard origin/main

    # --- 3. Install PHP dependencies ------------------------------------------
    info "Installing Composer dependencies (production)..."
    composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

    # --- 4. Build frontend ----------------------------------------------------
    info "Building frontend assets..."
    npm ci
    npm run build

    # --- 5. Configure Laravel -------------------------------------------------
    if [[ ! -f .env ]]; then
        info "Creating .env from .env.example..."
        cp .env.example .env
        php artisan key:generate --force
    fi

    # Use SQLite for initial scaffold (no MySQL yet)
    sed -i 's/^DB_CONNECTION=.*/DB_CONNECTION=sqlite/' .env
    sed -i 's|^DB_DATABASE=.*|DB_DATABASE=/opt/motivya/database.sqlite|' .env
    touch /opt/motivya/database.sqlite

    info "Running database migrations..."
    php artisan migrate --force

    # --- 6. Warm caches -------------------------------------------------------
    info "Warming Laravel caches..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

    # --- 7. Configure Nginx ---------------------------------------------------
    info "Configuring Nginx..."
    cat > /etc/nginx/sites-available/motivya <<NGINX
server {
    listen 80;
    server_name ${DOMAIN} _;
    root ${SRC_DIR}/public;
    index index.php;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    charset utf-8;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php\$ {
        fastcgi_pass unix:/run/php/php${PHP_VERSION}-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINX

    ln -sf /etc/nginx/sites-available/motivya /etc/nginx/sites-enabled/
    rm -f /etc/nginx/sites-enabled/default
    nginx -t
    systemctl reload nginx
    systemctl restart "php${PHP_VERSION}-fpm"

    # --- 8. Set permissions ---------------------------------------------------
    info "Setting file permissions..."
    chown -R www-data:www-data storage bootstrap/cache
    chmod -R 775 storage bootstrap/cache
    chown www-data:www-data /opt/motivya/database.sqlite

# =============================================================================
# DOCKER MODE: docker-compose.prod.yml
# =============================================================================
else
    info "Pulling latest code from main..."
    git fetch origin main
    git reset --hard origin/main

    info "Syncing Docker configs..."
    cp -r .docker/ "${APP_DIR}/.docker/"
    cp docker-compose.prod.yml "${COMPOSE_FILE}" 2>/dev/null || true

    info "Building and restarting containers..."
    cd "$APP_DIR"
    docker compose -f "$COMPOSE_FILE" build --no-cache app
    docker compose -f "$COMPOSE_FILE" up -d --remove-orphans
    sleep 5

    info "Installing Composer dependencies (production)..."
    docker compose -f "$COMPOSE_FILE" exec -T app \
        composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

    info "Running database migrations..."
    docker compose -f "$COMPOSE_FILE" exec -T app php artisan migrate --force

    info "Building frontend assets..."
    docker compose -f "$COMPOSE_FILE" exec -T app npm ci
    docker compose -f "$COMPOSE_FILE" exec -T app npm run build

    info "Warming Laravel caches..."
    docker compose -f "$COMPOSE_FILE" exec -T app php artisan config:cache
    docker compose -f "$COMPOSE_FILE" exec -T app php artisan route:cache
    docker compose -f "$COMPOSE_FILE" exec -T app php artisan view:cache
    docker compose -f "$COMPOSE_FILE" exec -T app php artisan event:cache

    info "Restarting queue workers..."
    docker compose -f "$COMPOSE_FILE" exec -T app php artisan queue:restart
fi

# --- Health check (both modes) ------------------------------------------------
info "Running health check..."
sleep 2
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" --max-time 10 "http://localhost/health" || echo "000")

if [[ "$HTTP_STATUS" == "200" ]]; then
    info "Health check PASSED (HTTP ${HTTP_STATUS})"
else
    error "Health check FAILED (HTTP ${HTTP_STATUS}). Check logs: journalctl -u nginx -u php${PHP_VERSION}-fpm --since '5 min ago'"
fi

info "Deployment complete! $(date)"
