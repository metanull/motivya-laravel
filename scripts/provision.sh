#!/usr/bin/env bash
#
# provision.sh — One-time VPS setup for Motivya production
#
# Usage: Run as root on a fresh Ubuntu 24.04 OVH VPS:
#   curl -fsSL https://raw.githubusercontent.com/metanull/motivya-laravel/main/scripts/provision.sh | bash
#   — OR —
#   scp scripts/provision.sh root@<VPS_IP>:/tmp/ && ssh root@<VPS_IP> bash /tmp/provision.sh
#
# What it does:
#   1. Creates a 'deploy' user with sudo and SSH key access
#   2. Hardens SSH (key-only, no root login)
#   3. Installs Docker Engine + Compose v2
#   4. Sets up UFW firewall (22, 80, 443)
#   5. Installs Fail2ban and unattended-upgrades
#   6. Installs Certbot for Let's Encrypt
#   7. Creates the application directory structure
#   8. Sets up daily MySQL backup cron
#
# Prerequisites:
#   - Fresh Ubuntu 24.04 VPS with root access
#   - Your SSH public key (will be prompted)
#
set -euo pipefail

# --- Configuration -----------------------------------------------------------
DEPLOY_USER="deploy"
APP_DIR="/opt/motivya"
DOMAIN="metanull.eu"
TIMEZONE="Europe/Brussels"
LOCALE="fr_BE.UTF-8"

# --- Colors -------------------------------------------------------------------
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

info()  { echo -e "${GREEN}[INFO]${NC} $1"; }
warn()  { echo -e "${YELLOW}[WARN]${NC} $1"; }
error() { echo -e "${RED}[ERROR]${NC} $1"; exit 1; }

# --- Pre-flight checks -------------------------------------------------------
[[ $EUID -ne 0 ]] && error "This script must be run as root"
[[ ! -f /etc/os-release ]] && error "Cannot detect OS"
source /etc/os-release
[[ "$VERSION_ID" != "24.04" ]] && warn "Expected Ubuntu 24.04, got $VERSION_ID. Proceeding anyway..."

# --- Prompt for SSH public key ------------------------------------------------
echo ""
echo "============================================================"
echo "  Motivya VPS Provisioning — metanull.eu"
echo "============================================================"
echo ""

# Accept key as argument ($1) or prompt interactively
if [[ -n "${1:-}" ]]; then
    SSH_PUBLIC_KEY="$1"
    info "Using SSH public key from argument"
else
    read -rp "Paste the SSH public key for the 'deploy' user: " SSH_PUBLIC_KEY
fi
[[ -z "$SSH_PUBLIC_KEY" ]] && error "SSH public key cannot be empty"

# --- 1. System basics ---------------------------------------------------------
info "Updating system packages..."
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get upgrade -y -qq

info "Setting timezone to ${TIMEZONE}..."
timedatectl set-timezone "$TIMEZONE"

info "Setting locale to ${LOCALE}..."
locale-gen "$LOCALE" > /dev/null 2>&1 || true
update-locale LANG="$LOCALE"

# --- 2. Create deploy user ----------------------------------------------------
info "Creating '${DEPLOY_USER}' user..."
if id "$DEPLOY_USER" &>/dev/null; then
    warn "User '${DEPLOY_USER}' already exists, skipping creation"
else
    adduser --disabled-password --gecos "Motivya Deploy" "$DEPLOY_USER"
    usermod -aG sudo "$DEPLOY_USER"
    # Allow sudo without password for deploy scripts
    echo "${DEPLOY_USER} ALL=(ALL) NOPASSWD:ALL" > "/etc/sudoers.d/${DEPLOY_USER}"
    chmod 440 "/etc/sudoers.d/${DEPLOY_USER}"
fi

# Set up SSH key
DEPLOY_HOME=$(eval echo "~${DEPLOY_USER}")
mkdir -p "${DEPLOY_HOME}/.ssh"
echo "$SSH_PUBLIC_KEY" > "${DEPLOY_HOME}/.ssh/authorized_keys"
chmod 700 "${DEPLOY_HOME}/.ssh"
chmod 600 "${DEPLOY_HOME}/.ssh/authorized_keys"
chown -R "${DEPLOY_USER}:${DEPLOY_USER}" "${DEPLOY_HOME}/.ssh"

# --- 3. Harden SSH ------------------------------------------------------------
info "Hardening SSH configuration..."
SSHD_CONFIG="/etc/ssh/sshd_config"
cp "$SSHD_CONFIG" "${SSHD_CONFIG}.bak"

# Apply hardening settings
sed -i 's/^#\?PermitRootLogin.*/PermitRootLogin no/' "$SSHD_CONFIG"
sed -i 's/^#\?PasswordAuthentication.*/PasswordAuthentication no/' "$SSHD_CONFIG"
sed -i 's/^#\?PubkeyAuthentication.*/PubkeyAuthentication yes/' "$SSHD_CONFIG"
sed -i 's/^#\?ChallengeResponseAuthentication.*/ChallengeResponseAuthentication no/' "$SSHD_CONFIG"

systemctl restart sshd

# --- 4. Firewall (UFW) -------------------------------------------------------
info "Configuring UFW firewall..."
apt-get install -y -qq ufw
ufw default deny incoming
ufw default allow outgoing
ufw allow 22/tcp comment "SSH"
ufw allow 80/tcp comment "HTTP"
ufw allow 443/tcp comment "HTTPS"
ufw --force enable

# --- 5. Fail2ban --------------------------------------------------------------
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

# --- 6. Unattended upgrades ---------------------------------------------------
info "Enabling automatic security updates..."
apt-get install -y -qq unattended-upgrades
dpkg-reconfigure -f noninteractive unattended-upgrades

# --- 7. Docker Engine + Compose -----------------------------------------------
info "Installing Docker Engine..."
apt-get install -y -qq ca-certificates curl gnupg

install -m 0755 -d /etc/apt/keyrings
if [[ ! -f /etc/apt/keyrings/docker.gpg ]]; then
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg | gpg --dearmor -o /etc/apt/keyrings/docker.gpg
    chmod a+r /etc/apt/keyrings/docker.gpg
fi

echo \
  "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu \
  $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | \
  tee /etc/apt/sources.list.d/docker.list > /dev/null

apt-get update -qq
apt-get install -y -qq docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin

# Add deploy user to docker group
usermod -aG docker "$DEPLOY_USER"

# Configure Docker log rotation
mkdir -p /etc/docker
cat > /etc/docker/daemon.json <<'EOF'
{
  "log-driver": "json-file",
  "log-opts": {
    "max-size": "10m",
    "max-file": "3"
  }
}
EOF
systemctl restart docker

# --- 8. Certbot ---------------------------------------------------------------
info "Installing Certbot..."
apt-get install -y -qq certbot

# Note: SSL certificate must be obtained AFTER DNS is configured.
# Run manually: certbot certonly --standalone -d metanull.eu --agree-tos -m admin@metanull.eu

# --- 9. Application directory -------------------------------------------------
info "Creating application directory at ${APP_DIR}..."
mkdir -p "${APP_DIR}"/{src,storage,backups,certbot}
chown -R "${DEPLOY_USER}:${DEPLOY_USER}" "$APP_DIR"

# --- 10. Backup cron ----------------------------------------------------------
info "Setting up daily MySQL backup cron..."
BACKUP_SCRIPT="${APP_DIR}/backup-db.sh"
cat > "$BACKUP_SCRIPT" <<'BEOF'
#!/usr/bin/env bash
set -euo pipefail
BACKUP_DIR="/opt/motivya/backups"
TIMESTAMP=$(date +%F)
docker compose -f /opt/motivya/docker-compose.prod.yml exec -T mysql \
  mysqldump -u motivya --password="${DB_PASSWORD}" motivya \
  | gzip > "${BACKUP_DIR}/motivya-${TIMESTAMP}.sql.gz"
# Keep only last 7 days
find "$BACKUP_DIR" -name "motivya-*.sql.gz" -mtime +7 -delete
BEOF
chmod +x "$BACKUP_SCRIPT"
chown "${DEPLOY_USER}:${DEPLOY_USER}" "$BACKUP_SCRIPT"

# Add cron (runs at 3 AM Brussels time)
echo "0 3 * * * ${DEPLOY_USER} ${BACKUP_SCRIPT}" > /etc/cron.d/motivya-backup
chmod 644 /etc/cron.d/motivya-backup

# --- Done! --------------------------------------------------------------------
echo ""
echo "============================================================"
info "VPS provisioning complete!"
echo "============================================================"
echo ""
echo "Next steps:"
echo "  1. Test SSH: ssh ${DEPLOY_USER}@<VPS_IP>"
echo "  2. Configure DNS: A record for ${DOMAIN} → <VPS_IP>"
echo "  3. Obtain SSL: certbot certonly --standalone -d ${DOMAIN} --agree-tos -m admin@${DOMAIN}"
echo "  4. Clone repo: cd ${APP_DIR}/src && git clone https://github.com/metanull/motivya-laravel.git ."
echo "  5. Create .env.production at ${APP_DIR}/.env.production"
echo "  6. First deploy: bash ${APP_DIR}/src/scripts/deploy.sh"
echo "  7. Set GitHub Secrets: VPS_HOST, VPS_SSH_KEY, VPS_SSH_USER"
echo ""
warn "IMPORTANT: Root SSH login is now DISABLED. Use '${DEPLOY_USER}' from now on."
echo ""
