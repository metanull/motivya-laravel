#!/usr/bin/env bash
#
# deploy.sh — Deploy a pre-built Motivya release artifact to the VPS
#
# Usage:
#   bash deploy.sh /tmp/motivya-release.tar.gz
#
# The archive is built by CI (ci.yml "Build Release Artifact" job) and contains
# the full application with production vendor/ and compiled public/build/ assets.
# No git, composer, or npm is required on the VPS.
#
# The script is idempotent — safe to re-run.
#
set -euo pipefail

# --- Configuration -----------------------------------------------------------
APP_DIR="/opt/motivya"
CURRENT="${APP_DIR}/current"
DOMAIN="motivya.metanull.eu"
PHP_VERSION="8.3"

# --- Arguments ---------------------------------------------------------------
ARCHIVE="${1:-}"
[[ -z "$ARCHIVE" ]] && { echo "[DEPLOY] ERROR: Usage: deploy.sh <archive.tar.gz>"; exit 1; }
[[ ! -f "$ARCHIVE" ]] && { echo "[DEPLOY] ERROR: Archive not found: ${ARCHIVE}"; exit 1; }

# --- Colors -------------------------------------------------------------------
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

info()  { echo -e "${GREEN}[DEPLOY]${NC} $1"; }
warn()  { echo -e "${YELLOW}[DEPLOY]${NC} $1"; }
error() { echo -e "${RED}[DEPLOY]${NC} $1"; exit 1; }

# --- 1. Install runtime (idempotent, first deploy only) ----------------------
install_runtime() {
    if dpkg -l "php${PHP_VERSION}-fpm" &> /dev/null 2>&1; then
        return 0
    fi

    info "Installing PHP ${PHP_VERSION}-FPM, Nginx, and dependencies..."
    export DEBIAN_FRONTEND=noninteractive
    apt-get update -qq
    apt-get install -y -qq \
        "php${PHP_VERSION}-fpm" "php${PHP_VERSION}-sqlite3" "php${PHP_VERSION}-mbstring" \
        "php${PHP_VERSION}-xml" "php${PHP_VERSION}-curl" "php${PHP_VERSION}-zip" \
        "php${PHP_VERSION}-bcmath" "php${PHP_VERSION}-intl" "php${PHP_VERSION}-gd" \
        nginx unzip curl sqlite3
}

# --- 2. Extract release -------------------------------------------------------
deploy_release() {
    local RELEASE_DIR="${APP_DIR}/releases/$(date +%Y%m%d%H%M%S)"
    mkdir -p "$RELEASE_DIR"

    info "Extracting release to ${RELEASE_DIR}..."
    tar xzf "$ARCHIVE" -C "$RELEASE_DIR"

    # Shared storage: persist across deploys
    mkdir -p "${APP_DIR}/shared/storage"
    mkdir -p "${APP_DIR}/shared/storage/app/public"
    mkdir -p "${APP_DIR}/shared/storage/framework/cache/data"
    mkdir -p "${APP_DIR}/shared/storage/framework/sessions"
    mkdir -p "${APP_DIR}/shared/storage/framework/views"
    mkdir -p "${APP_DIR}/shared/storage/logs"

    # Symlink shared storage into the release
    rm -rf "${RELEASE_DIR}/storage"
    ln -sfn "${APP_DIR}/shared/storage" "${RELEASE_DIR}/storage"

    # Ensure bootstrap/cache exists
    mkdir -p "${RELEASE_DIR}/bootstrap/cache"

    # Swap the current symlink (atomic on Linux)
    ln -sfn "$RELEASE_DIR" "${CURRENT}"

    info "Release deployed: ${RELEASE_DIR}"
}

# --- 3. Configure Laravel (first deploy) -------------------------------------
configure_laravel() {
    cd "$CURRENT"

    if [[ ! -f "${APP_DIR}/shared/.env" ]]; then
        info "Creating .env from .env.example..."
        cp .env.example "${APP_DIR}/shared/.env"
        php artisan key:generate --force --env=production 2>/dev/null || true
        # Use SQLite for initial deployment (no MySQL yet)
        sed -i 's/^DB_CONNECTION=.*/DB_CONNECTION=sqlite/' "${APP_DIR}/shared/.env"
        sed -i "s|^DB_DATABASE=.*|DB_DATABASE=${APP_DIR}/shared/database.sqlite|" "${APP_DIR}/shared/.env"
        touch "${APP_DIR}/shared/database.sqlite"
    fi

    # Symlink .env
    ln -sfn "${APP_DIR}/shared/.env" "${CURRENT}/.env"
}

# --- 4. Run migrations -------------------------------------------------------
run_migrations() {
    cd "$CURRENT"
    info "Running database migrations..."
    php artisan migrate --force
}

# --- 5. Warm caches ----------------------------------------------------------
warm_caches() {
    cd "$CURRENT"
    info "Warming Laravel caches..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
}

# --- 6. Configure Nginx (idempotent) -----------------------------------------
configure_nginx() {
    local NGINX_CONF="/etc/nginx/sites-available/motivya"

    info "Configuring Nginx..."
    cat > "$NGINX_CONF" <<NGINX
server {
    listen 80;
    server_name ${DOMAIN} _;
    root ${CURRENT}/public;
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

    ln -sf "$NGINX_CONF" /etc/nginx/sites-enabled/
    rm -f /etc/nginx/sites-enabled/default
    nginx -t
    systemctl reload nginx
    systemctl restart "php${PHP_VERSION}-fpm"
}

# --- 7. Set permissions -------------------------------------------------------
set_permissions() {
    info "Setting file permissions..."
    chown -R www-data:www-data "${APP_DIR}/shared/storage"
    chown -R www-data:www-data "${CURRENT}/bootstrap/cache"
    chmod -R 775 "${APP_DIR}/shared/storage" "${CURRENT}/bootstrap/cache"
    [[ -f "${APP_DIR}/shared/database.sqlite" ]] && chown www-data:www-data "${APP_DIR}/shared/database.sqlite"
}

# --- 8. Prune old releases (keep last 5) -------------------------------------
prune_releases() {
    local RELEASES_DIR="${APP_DIR}/releases"
    local COUNT
    COUNT=$(ls -1d "${RELEASES_DIR}"/*/ 2>/dev/null | wc -l)
    if (( COUNT > 5 )); then
        info "Pruning old releases (keeping last 5)..."
        ls -1dt "${RELEASES_DIR}"/*/ | tail -n +6 | xargs rm -rf
    fi
}

# --- 9. Health check ----------------------------------------------------------
health_check() {
    info "Running health check..."
    sleep 2
    local HTTP_STATUS
    HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" --max-time 10 "http://localhost/" || echo "000")

    if [[ "$HTTP_STATUS" == "200" ]]; then
        info "Health check PASSED (HTTP ${HTTP_STATUS})"
    else
        error "Health check FAILED (HTTP ${HTTP_STATUS}). Check: journalctl -u nginx -u php${PHP_VERSION}-fpm --since '5 min ago'"
    fi
}

# =============================================================================
# Main
# =============================================================================
info "Starting deployment..."
install_runtime
deploy_release
configure_laravel
run_migrations
warm_caches
configure_nginx
set_permissions
prune_releases
health_check
info "Deployment complete! $(date)"
