#!/usr/bin/env bash
#
# provision.sh — One-time VPS setup for Motivya production
#
# Run as root (or via sudo) on the VPS:
#   sudo bash provision.sh [SSH_PUBLIC_KEY]
#
# This script is idempotent — safe to re-run after updates.
#
# What it does:
#   1. System basics: timezone, locale, packages
#   2. Creates 'deploy' user (non-privileged, no sudo)
#   3. Hardens SSH (key-only, no root login)
#   4. Installs PHP-FPM, Nginx, and PHP extensions
#   5. Installs MySQL 8.x and creates application database + user
#   6. Installs Valkey (Redis-compatible) for cache/sessions/queues
#   7. Configures Nginx vhost for Motivya
#   8. Sets up UFW firewall (22, 80, 443)
#   9. Installs Fail2ban and unattended-upgrades
#   10. Creates the application directory structure (owned by deploy)
#   11. Sets up shared storage with www-data group permissions
#   12. Installs Certbot + auto-renewal timer
#   13. Creates queue worker systemd service
#   14. Sets up daily MySQL backup cron
#
# The 'deploy' user has NO sudo. All privileged operations belong here.
#
set -euo pipefail

# --- Optional: source local infra config (gitignored, never committed) ------
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
[[ -f "${SCRIPT_DIR}/infra.local" ]] && source "${SCRIPT_DIR}/infra.local"

# --- Configuration -----------------------------------------------------------
DEPLOY_USER="deploy"
APP_DIR="/opt/motivya"
DOMAIN="${MOTIVYA_DOMAIN:?ERROR: Set MOTIVYA_DOMAIN in scripts/infra.local or export it as an env var}"
ADMIN_EMAIL="${MOTIVYA_ADMIN_EMAIL:?ERROR: Set MOTIVYA_ADMIN_EMAIL in scripts/infra.local or export it as an env var}"
PHP_VERSION="8.4"
TIMEZONE="Europe/Brussels"
LOCALE="fr_BE.UTF-8"

# MySQL (generated on first run, stored in /root/.motivya-db-credentials)
DB_NAME="motivya"
DB_USER="motivya"
DB_CREDENTIALS_FILE="/root/.motivya-db-credentials"

# --- Colors -------------------------------------------------------------------
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

info()  { echo -e "${GREEN}[PROVISION]${NC} $1"; }
warn()  { echo -e "${YELLOW}[PROVISION]${NC} $1"; }
error() { echo -e "${RED}[PROVISION]${NC} $1"; exit 1; }

# --- Pre-flight checks -------------------------------------------------------
[[ $EUID -ne 0 ]] && error "This script must be run as root (or via sudo)."
[[ ! -f /etc/os-release ]] && error "Cannot detect OS"
source /etc/os-release
[[ "$VERSION_ID" != "24.04" ]] && warn "Expected Ubuntu 24.04, got $VERSION_ID. Proceeding anyway..."

# --- SSH public key (optional, for first run) ---------------------------------
SSH_PUBLIC_KEY="${1:-}"

# =============================================================================
# 1. System basics
# =============================================================================
info "Updating system packages..."
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get upgrade -y -qq

info "Setting timezone to ${TIMEZONE}..."
timedatectl set-timezone "$TIMEZONE"

info "Setting locale to ${LOCALE}..."
locale-gen "$LOCALE" > /dev/null 2>&1 || true
update-locale LANG="$LOCALE"

# =============================================================================
# 2. Create deploy user (non-privileged)
# =============================================================================
if id "$DEPLOY_USER" &>/dev/null; then
    info "User '${DEPLOY_USER}' already exists."
else
    info "Creating '${DEPLOY_USER}' user..."
    adduser --disabled-password --gecos "Motivya Deploy" --home "/home/${DEPLOY_USER}" "$DEPLOY_USER"
fi

# IMPORTANT: Remove deploy from sudo group (least privilege)
if groups "$DEPLOY_USER" | grep -qw sudo; then
    info "Removing '${DEPLOY_USER}' from sudo group (least privilege)..."
    gpasswd -d "$DEPLOY_USER" sudo 2>/dev/null || true
fi

# Remove any leftover sudoers file
rm -f "/etc/sudoers.d/${DEPLOY_USER}"

# Add deploy to www-data group (for shared file permissions)
usermod -aG www-data "$DEPLOY_USER"

# Set up SSH key for deploy user
if [[ -n "$SSH_PUBLIC_KEY" ]]; then
    DEPLOY_HOME="/home/${DEPLOY_USER}"
    mkdir -p "${DEPLOY_HOME}/.ssh"
    # Replace (not append) to ensure clean state
    echo "$SSH_PUBLIC_KEY" > "${DEPLOY_HOME}/.ssh/authorized_keys"
    chmod 700 "${DEPLOY_HOME}/.ssh"
    chmod 600 "${DEPLOY_HOME}/.ssh/authorized_keys"
    chown -R "${DEPLOY_USER}:${DEPLOY_USER}" "${DEPLOY_HOME}/.ssh"
    info "SSH key installed for '${DEPLOY_USER}'."
else
    info "No SSH key provided — skipping (use: provision.sh 'ssh-ed25519 AAAA...')"
fi

# =============================================================================
# 3. Harden SSH
# =============================================================================
info "Hardening SSH configuration..."
SSHD_CONFIG="/etc/ssh/sshd_config"

sed -i 's/^#\?PermitRootLogin.*/PermitRootLogin no/' "$SSHD_CONFIG"
sed -i 's/^#\?PasswordAuthentication.*/PasswordAuthentication no/' "$SSHD_CONFIG"
sed -i 's/^#\?PubkeyAuthentication.*/PubkeyAuthentication yes/' "$SSHD_CONFIG"
sed -i 's/^#\?ChallengeResponseAuthentication.*/ChallengeResponseAuthentication no/' "$SSHD_CONFIG"

systemctl restart sshd

# =============================================================================
# 4. Install PHP-FPM, Nginx, and extensions
# =============================================================================
info "Installing PHP ${PHP_VERSION}-FPM, Nginx, and extensions..."

apt-get install -y -qq \
    nginx \
    "php${PHP_VERSION}-fpm" \
    "php${PHP_VERSION}-mysql" \
    "php${PHP_VERSION}-sqlite3" \
    "php${PHP_VERSION}-mbstring" \
    "php${PHP_VERSION}-xml" \
    "php${PHP_VERSION}-curl" \
    "php${PHP_VERSION}-zip" \
    "php${PHP_VERSION}-bcmath" \
    "php${PHP_VERSION}-intl" \
    "php${PHP_VERSION}-gd" \
    "php${PHP_VERSION}-redis" \
    unzip curl sqlite3

# Enable and start services
systemctl enable "php${PHP_VERSION}-fpm" nginx
systemctl start "php${PHP_VERSION}-fpm"

# =============================================================================
# 5. Install MySQL
# =============================================================================
info "Installing MySQL server..."
apt-get install -y -qq mysql-server

systemctl enable mysql
systemctl start mysql

# Generate password once, store in credentials file
if [[ ! -f "$DB_CREDENTIALS_FILE" ]]; then
    DB_PASSWORD=$(openssl rand -base64 32 | tr -d '/+=|' | head -c 32)
    cat > "$DB_CREDENTIALS_FILE" <<CRED
DB_NAME=${DB_NAME}
DB_USER=${DB_USER}
DB_PASSWORD=${DB_PASSWORD}
CRED
    chmod 600 "$DB_CREDENTIALS_FILE"
    info "MySQL credentials saved to ${DB_CREDENTIALS_FILE}"
else
    info "Loading existing MySQL credentials from ${DB_CREDENTIALS_FILE}"
    source "$DB_CREDENTIALS_FILE"
fi

# Create database and user (idempotent)
mysql -u root <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL
info "MySQL database '${DB_NAME}' and user '${DB_USER}' configured."

# Harden: bind to localhost only (already default, enforce it)
if ! grep -q '^bind-address.*=.*127.0.0.1' /etc/mysql/mysql.conf.d/mysqld.cnf 2>/dev/null; then
    sed -i 's/^#\?bind-address.*/bind-address = 127.0.0.1/' /etc/mysql/mysql.conf.d/mysqld.cnf
    systemctl restart mysql
fi

# =============================================================================
# 6. Install Valkey (Redis-compatible cache/session/queue backend)
# =============================================================================
info "Installing Valkey..."
apt-get install -y -qq valkey-server

# Bind to localhost only, enable as systemd service
VALKEY_CONF="/etc/valkey/valkey.conf"
if [[ -f "$VALKEY_CONF" ]]; then
    sed -i 's/^bind .*/bind 127.0.0.1 -::1/' "$VALKEY_CONF"
    # Disable protected-mode since we bind to localhost
    sed -i 's/^protected-mode .*/protected-mode yes/' "$VALKEY_CONF"
    # Set max memory (256MB reasonable for cache on this VPS)
    if ! grep -q '^maxmemory ' "$VALKEY_CONF"; then
        echo 'maxmemory 256mb' >> "$VALKEY_CONF"
        echo 'maxmemory-policy allkeys-lru' >> "$VALKEY_CONF"
    fi
fi

systemctl enable valkey-server
systemctl restart valkey-server
info "Valkey installed and running on localhost:6379."

# =============================================================================
# 7. Configure Nginx vhost
# =============================================================================
info "Configuring Nginx for ${DOMAIN}..."
NGINX_CONF="/etc/nginx/sites-available/motivya"
SSL_CERT="/etc/letsencrypt/live/${DOMAIN}/fullchain.pem"
SSL_KEY="/etc/letsencrypt/live/${DOMAIN}/privkey.pem"

# Check if SSL cert exists to determine config
if [[ -f "$SSL_CERT" && -f "$SSL_KEY" ]]; then
    info "SSL certificate found — configuring HTTPS."
    cat > "$NGINX_CONF" <<NGINX
# HTTP -> HTTPS redirect
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN};
    return 301 https://${DOMAIN}\$request_uri;
}

# Motivya Laravel app (HTTPS)
server {
    listen 443 ssl;
    listen [::]:443 ssl;
    server_name ${DOMAIN};

    ssl_certificate     ${SSL_CERT};
    ssl_certificate_key ${SSL_KEY};

    root ${APP_DIR}/current/public;
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
else
    warn "No SSL certificate found at ${SSL_CERT} — configuring HTTP only."
    cat > "$NGINX_CONF" <<NGINX
server {
    listen 80;
    server_name ${DOMAIN};
    root ${APP_DIR}/current/public;
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
fi

ln -sf "$NGINX_CONF" /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

nginx -t && systemctl reload nginx
info "Nginx configured and reloaded."

# =============================================================================
# 8. Firewall (UFW)
# =============================================================================
info "Configuring UFW firewall..."
apt-get install -y -qq ufw
ufw default deny incoming
ufw default allow outgoing
ufw allow 22/tcp comment "SSH"
ufw allow 80/tcp comment "HTTP"
ufw allow 443/tcp comment "HTTPS"
ufw --force enable

# =============================================================================
# 9. Fail2ban + unattended-upgrades
# =============================================================================
info "Installing Fail2ban..."
apt-get install -y -qq fail2ban
cat > /etc/fail2ban/jail.local <<'EOF'
[sshd]
enabled = true
port = ssh
filter = sshd
logpath = /var/log/auth.log
maxretry = 5
bantime = 3600
findtime = 600
EOF
systemctl enable fail2ban
systemctl restart fail2ban

info "Enabling automatic security updates..."
apt-get install -y -qq unattended-upgrades
dpkg-reconfigure -f noninteractive unattended-upgrades

# =============================================================================
# 10. Application directory structure
# =============================================================================
info "Creating application directories at ${APP_DIR}..."

mkdir -p "${APP_DIR}/releases"
mkdir -p "${APP_DIR}/shared/storage/app/public"
mkdir -p "${APP_DIR}/shared/storage/framework/cache/data"
mkdir -p "${APP_DIR}/shared/storage/framework/sessions"
mkdir -p "${APP_DIR}/shared/storage/framework/views"
mkdir -p "${APP_DIR}/shared/storage/logs"

# Create initial 'current' placeholder (first deploy will replace with symlink)
if [ ! -e "${APP_DIR}/current" ]; then
    mkdir -p "${APP_DIR}/current/scripts"
fi

# SQLite database file (initial; MySQL later)
touch "${APP_DIR}/shared/database.sqlite"

# =============================================================================
# 11. File ownership and permissions
# =============================================================================
info "Setting ownership and permissions..."

# deploy owns the entire app tree
chown -R "${DEPLOY_USER}:www-data" "${APP_DIR}"

# Storage and bootstrap/cache must be writable by www-data (PHP-FPM)
chmod -R 775 "${APP_DIR}/shared/storage"
chmod 664 "${APP_DIR}/shared/database.sqlite"

# Set setgid bit so new files inherit www-data group
find "${APP_DIR}/shared/storage" -type d -exec chmod g+s {} +

# =============================================================================
# 12. Certbot + auto-renewal
# =============================================================================
info "Installing Certbot..."
apt-get install -y -qq certbot

# Enable auto-renewal timer (certbot package installs the timer, just ensure it's active)
if systemctl list-timers | grep -q certbot; then
    info "Certbot auto-renewal timer already active."
else
    systemctl enable --now certbot.timer 2>/dev/null || \
        info "Certbot timer not found — renewal via cron should be in place."
fi

# =============================================================================
# 13. Queue worker systemd service
# =============================================================================
info "Creating queue worker systemd service..."
cat > /etc/systemd/system/motivya-queue.service <<UNIT
[Unit]
Description=Motivya Laravel Queue Worker
After=network.target mysql.service valkey-server.service

[Service]
User=www-data
Group=www-data
WorkingDirectory=${APP_DIR}/current
ExecStart=/usr/bin/php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600 --memory=128
Restart=always
RestartSec=5
StandardOutput=append:${APP_DIR}/shared/storage/logs/queue-worker.log
StandardError=append:${APP_DIR}/shared/storage/logs/queue-worker.log

[Install]
WantedBy=multi-user.target
UNIT

systemctl daemon-reload
systemctl enable motivya-queue
# Don't start yet — app may not be deployed
if [[ -L "${APP_DIR}/current" ]]; then
    systemctl restart motivya-queue
    info "Queue worker started."
else
    info "Queue worker configured (will start after first deploy)."
fi

# =============================================================================
# 14. Daily MySQL backup cron
# =============================================================================
info "Setting up daily MySQL backup..."
BACKUP_SCRIPT="${APP_DIR}/backup-db.sh"
cat > "$BACKUP_SCRIPT" <<'BEOF'
#!/usr/bin/env bash
set -euo pipefail
BACKUP_DIR="/opt/motivya/backups"
TIMESTAMP=$(date +%F)
mysqldump -u motivya "motivya" | gzip > "${BACKUP_DIR}/motivya-${TIMESTAMP}.sql.gz"
# Keep only last 14 days
find "$BACKUP_DIR" -name "motivya-*.sql.gz" -mtime +14 -delete
BEOF
chmod +x "$BACKUP_SCRIPT"
chown "${DEPLOY_USER}:www-data" "$BACKUP_SCRIPT"

# MySQL credentials for backup: use .my.cnf for deploy user
DEPLOY_HOME="/home/${DEPLOY_USER}"
if [[ -f "$DB_CREDENTIALS_FILE" ]]; then
    source "$DB_CREDENTIALS_FILE"
    # .my.cnf for mysqldump (backup cron)
    cat > "${DEPLOY_HOME}/.my.cnf" <<MYCNF
[mysqldump]
user=${DB_USER}
password=${DB_PASSWORD}
MYCNF
    chmod 600 "${DEPLOY_HOME}/.my.cnf"
    chown "${DEPLOY_USER}:${DEPLOY_USER}" "${DEPLOY_HOME}/.my.cnf"

    # Copy credentials for deploy.sh to read during first deploy
    cp "$DB_CREDENTIALS_FILE" "${DEPLOY_HOME}/.motivya-db-credentials"
    chmod 600 "${DEPLOY_HOME}/.motivya-db-credentials"
    chown "${DEPLOY_USER}:${DEPLOY_USER}" "${DEPLOY_HOME}/.motivya-db-credentials"
fi

# Cron: 3 AM Brussels time, as deploy user
echo "0 3 * * * ${DEPLOY_USER} ${BACKUP_SCRIPT}" > /etc/cron.d/motivya-backup
chmod 644 /etc/cron.d/motivya-backup
info "Daily MySQL backup configured (3 AM, 14-day retention)."

# =============================================================================
# Done
# =============================================================================
echo ""
info "============================================="
info "  Provisioning complete!"
info "============================================="
info ""
info "  Deploy user:  ${DEPLOY_USER} (no sudo)"
info "  App directory: ${APP_DIR} (owned by ${DEPLOY_USER}:www-data)"
info "  PHP-FPM:       ${PHP_VERSION}"
info "  Nginx:         configured for ${DOMAIN}"
info ""
info "  MySQL:         ${DB_NAME} (credentials in ${DB_CREDENTIALS_FILE})"
info "  Valkey:        localhost:6379"
info "  Queue worker:  motivya-queue.service"
info "  Backup:        daily at 3 AM (14-day retention)"
info ""
info "  Next steps:"
info "  1. Verify SSH:  ssh -i ~/.ssh/motivya_deploy ${DEPLOY_USER}@<VPS_IP> whoami"
info "  2. SSL cert:    certbot certonly --standalone -d ${DOMAIN} --agree-tos -m ${ADMIN_EMAIL}"
info "  3. Update /opt/motivya/shared/.env with MySQL credentials from ${DB_CREDENTIALS_FILE}"
info "  4. Push code to main to trigger deploy via GitHub Actions."
info ""
