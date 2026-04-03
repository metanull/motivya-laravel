#!/usr/bin/env bash
#
# deploy.sh — Deploy a pre-built Motivya release artifact to the VPS
#
# Usage (run as 'deploy' user):
#   bash deploy.sh /tmp/motivya-release.tar.gz
#
# The archive is built by CI (ci.yml "Build Release Artifact" job) and contains
# the full application with production vendor/ and compiled public/build/ assets.
# No git, composer, npm, or sudo is required.
#
# Prerequisites (handled by provision.sh, run once as root):
#   - PHP-FPM and Nginx installed and configured
#   - /opt/motivya/ owned by deploy:www-data
#   - /opt/motivya/shared/storage/ directory tree exists
#   - Nginx vhost pointing to /opt/motivya/current/public
#
# This script is idempotent — safe to re-run.
# This script uses NO sudo — all operations are within /opt/motivya/.
#
set -euo pipefail

# --- Configuration -----------------------------------------------------------
APP_DIR="/opt/motivya"
CURRENT="${APP_DIR}/current"
PHP_VERSION="8.4"

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

# --- Pre-flight checks -------------------------------------------------------
preflight() {
    # Verify we are NOT root
    [[ $EUID -eq 0 ]] && error "This script must NOT be run as root. Run as '$(whoami)' or the deploy user."

    # Verify PHP is available
    command -v php &>/dev/null || error "PHP not found. Run provision.sh first (as root)."

    # Verify app directory is writable
    [[ -w "$APP_DIR" ]] || error "${APP_DIR} is not writable. Run provision.sh first (as root)."

    # Verify shared storage exists
    [[ -d "${APP_DIR}/shared/storage" ]] || error "${APP_DIR}/shared/storage missing. Run provision.sh first."
}

# --- 1. Extract release -------------------------------------------------------
deploy_release() {
    local RELEASE_DIR="${APP_DIR}/releases/$(date +%Y%m%d%H%M%S)"
    mkdir -p "$RELEASE_DIR"

    info "Extracting release to ${RELEASE_DIR}..."
    tar xzf "$ARCHIVE" -C "$RELEASE_DIR"

    # Symlink shared storage into the release
    rm -rf "${RELEASE_DIR}/storage"
    ln -sfn "${APP_DIR}/shared/storage" "${RELEASE_DIR}/storage"

    # Ensure bootstrap/cache exists
    mkdir -p "${RELEASE_DIR}/bootstrap/cache"

    # Swap the current symlink atomically
    ln -sfn "$RELEASE_DIR" "${CURRENT}"

    info "Release deployed: ${RELEASE_DIR}"
}

# --- 2. Configure Laravel (first deploy) -------------------------------------
configure_laravel() {
    cd "$CURRENT"

    if [[ ! -f "${APP_DIR}/shared/.env" ]]; then
        info "Creating .env from .env.example..."
        cp .env.example "${APP_DIR}/shared/.env"

        # Production defaults
        sed -i 's/^APP_ENV=.*/APP_ENV=production/' "${APP_DIR}/shared/.env"
        sed -i 's/^APP_DEBUG=.*/APP_DEBUG=false/' "${APP_DIR}/shared/.env"
        sed -i 's|^APP_URL=.*|APP_URL=https://motivya.metanull.eu|' "${APP_DIR}/shared/.env"

        # Use SQLite for initial deployment (no MySQL yet)
        sed -i 's/^DB_CONNECTION=.*/DB_CONNECTION=sqlite/' "${APP_DIR}/shared/.env"
        # Uncomment and set DB_DATABASE (may be commented in .env.example)
        sed -i '/^#.*DB_DATABASE=/d' "${APP_DIR}/shared/.env"
        sed -i "/^DB_CONNECTION=/a DB_DATABASE=${APP_DIR}/shared/database.sqlite" "${APP_DIR}/shared/.env"

        touch "${APP_DIR}/shared/database.sqlite"
    fi

    # Symlink .env (must happen before key:generate or artisan commands)
    ln -sfn "${APP_DIR}/shared/.env" "${CURRENT}/.env"

    # Generate key if missing
    if ! grep -q '^APP_KEY=base64:' "${APP_DIR}/shared/.env"; then
        info "Generating application key..."
        php artisan key:generate --force
    fi
}

# --- 3. Run migrations -------------------------------------------------------
run_migrations() {
    cd "$CURRENT"
    info "Running database migrations..."
    php artisan migrate --force
}

# --- 4. Warm caches ----------------------------------------------------------
warm_caches() {
    cd "$CURRENT"
    info "Warming Laravel caches..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
}

# --- 5. Prune old releases (keep last 5) -------------------------------------
prune_releases() {
    local RELEASES_DIR="${APP_DIR}/releases"
    local COUNT
    COUNT=$(ls -1d "${RELEASES_DIR}"/*/ 2>/dev/null | wc -l)
    if (( COUNT > 5 )); then
        info "Pruning old releases (keeping last 5)..."
        ls -1dt "${RELEASES_DIR}"/*/ | tail -n +6 | xargs rm -rf
    fi
}

# --- 6. Health check ----------------------------------------------------------
health_check() {
    info "Running health check..."
    sleep 2
    local HTTP_STATUS
    HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" --max-time 10 "http://localhost/" || echo "000")

    if [[ "$HTTP_STATUS" == "200" ]]; then
        info "Health check PASSED (HTTP ${HTTP_STATUS})"
    else
        warn "Health check returned HTTP ${HTTP_STATUS} (may be OK on first deploy without SSL)."
    fi
}

# =============================================================================
# Main
# =============================================================================
info "Starting deployment as $(whoami)..."
preflight
deploy_release
configure_laravel
run_migrations
warm_caches
prune_releases
health_check
info "Deployment complete! $(date)"
