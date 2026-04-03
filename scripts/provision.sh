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
#   5. Configures Nginx vhost for Motivya
#   6. Sets up UFW firewall (22, 80, 443)
#   7. Installs Fail2ban and unattended-upgrades
#   8. Creates the application directory structure (owned by deploy)
#   9. Sets up shared storage with www-data group permissions
#   10. Installs Certbot for Let's Encrypt
#
# The 'deploy' user has NO sudo. All privileged operations belong here.
#
set -euo pipefail

# --- Configuration -----------------------------------------------------------
DEPLOY_USER="deploy"
APP_DIR="/opt/motivya"
DOMAIN="motivya.metanull.eu"
PHP_VERSION="8.4"
TIMEZONE="Europe/Brussels"
LOCALE="fr_BE.UTF-8"

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
# 5. Configure Nginx vhost
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
# 6. Firewall (UFW)
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
# 7. Fail2ban + unattended-upgrades
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
# 8. Application directory structure
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
# 9. File ownership and permissions
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
# 10. Certbot
# =============================================================================
info "Installing Certbot..."
apt-get install -y -qq certbot

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
info "  Next steps:"
info "  1. Verify SSH:  ssh -i ~/.ssh/motivya_deploy ${DEPLOY_USER}@<VPS_IP> whoami"
info "  2. SSL cert:    certbot certonly --standalone -d ${DOMAIN} --agree-tos -m admin@metanull.eu"
info "  3. Push code to main to trigger first deploy via GitHub Actions."
info ""
