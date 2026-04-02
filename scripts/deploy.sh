#!/usr/bin/env bash
#
# deploy.sh — Zero-downtime deployment for Motivya on OVH VPS
#
# Usage: Called by GitHub Actions via SSH, or manually:
#   ssh deploy@<VPS_IP> "bash /opt/motivya/src/scripts/deploy.sh"
#
# What it does:
#   1. Pulls latest code from main
#   2. Installs Composer dependencies (no-dev)
#   3. Runs database migrations
#   4. Builds and restarts Docker containers
#   5. Warms Laravel caches
#   6. Restarts queue workers
#   7. Verifies health check
#
# Prerequisites:
#   - VPS provisioned with provision.sh
#   - .env.production exists at /opt/motivya/.env.production
#   - Docker Compose stack is running
#
set -euo pipefail

# --- Configuration -----------------------------------------------------------
APP_DIR="/opt/motivya"
SRC_DIR="${APP_DIR}/src"
COMPOSE_FILE="${APP_DIR}/docker-compose.prod.yml"
DOMAIN="motivya.metanull.eu"

# --- Colors -------------------------------------------------------------------
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

info()  { echo -e "${GREEN}[DEPLOY]${NC} $1"; }
warn()  { echo -e "${YELLOW}[DEPLOY]${NC} $1"; }
error() { echo -e "${RED}[DEPLOY]${NC} $1"; exit 1; }

# --- Pre-flight checks -------------------------------------------------------
[[ ! -d "$SRC_DIR" ]] && error "Source directory ${SRC_DIR} does not exist"
[[ ! -f "$COMPOSE_FILE" ]] && error "Compose file ${COMPOSE_FILE} does not exist"

cd "$SRC_DIR"

# --- 1. Pull latest code -----------------------------------------------------
info "Pulling latest code from main..."
git fetch origin main
git reset --hard origin/main

# --- 2. Copy production configs -----------------------------------------------
info "Syncing Docker configs..."
cp -r .docker/ "${APP_DIR}/.docker/"
cp docker-compose.prod.yml "${COMPOSE_FILE}" 2>/dev/null || true

# --- 3. Build and restart containers -----------------------------------------
info "Building and restarting containers..."
cd "$APP_DIR"
docker compose -f "$COMPOSE_FILE" build --no-cache app
docker compose -f "$COMPOSE_FILE" up -d --remove-orphans

# Wait for containers to be healthy
info "Waiting for containers to start..."
sleep 5

# --- 4. Install Composer dependencies ----------------------------------------
info "Installing Composer dependencies (production)..."
docker compose -f "$COMPOSE_FILE" exec -T app \
    composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# --- 5. Run migrations -------------------------------------------------------
info "Running database migrations..."
docker compose -f "$COMPOSE_FILE" exec -T app \
    php artisan migrate --force

# --- 6. Build frontend assets ------------------------------------------------
info "Building frontend assets..."
docker compose -f "$COMPOSE_FILE" exec -T app \
    npm ci --production=false
docker compose -f "$COMPOSE_FILE" exec -T app \
    npm run build

# --- 7. Warm caches ----------------------------------------------------------
info "Warming Laravel caches..."
docker compose -f "$COMPOSE_FILE" exec -T app php artisan config:cache
docker compose -f "$COMPOSE_FILE" exec -T app php artisan route:cache
docker compose -f "$COMPOSE_FILE" exec -T app php artisan view:cache
docker compose -f "$COMPOSE_FILE" exec -T app php artisan event:cache

# --- 8. Restart queue workers ------------------------------------------------
info "Restarting queue workers..."
docker compose -f "$COMPOSE_FILE" exec -T app php artisan queue:restart

# --- 9. Health check ----------------------------------------------------------
info "Running health check..."
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" --max-time 10 "https://${DOMAIN}/health" || echo "000")

if [[ "$HTTP_STATUS" == "200" ]]; then
    info "Health check passed (HTTP ${HTTP_STATUS})"
else
    warn "Health check returned HTTP ${HTTP_STATUS} — verify manually"
fi

# --- Done! --------------------------------------------------------------------
info "Deployment complete! $(date)"
