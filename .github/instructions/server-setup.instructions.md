---
description: "Use when configuring the production server, writing deployment scripts, setting up Docker Compose for production, configuring Nginx/SSL, or managing the OVH VPS. Covers the single-VPS production topology, SSH access, domain, and deployment flow."
applyTo: "scripts/**,docker-compose.prod.yml,.docker/**"
---

# Server Setup — OVH VPS Production

## Topology

A single OVH VPS Starter runs the entire stack via Docker Compose.

```
┌─────────────────────────────────────────────┐
│  OVH VPS Starter (France)                   │
│  1 vCPU · 2 GB RAM · 20 GB SSD             │
│  Ubuntu 24.04 LTS                           │
│                                             │
│  ┌───────────────────────────────────────┐  │
│  │ Docker Compose                        │  │
│  │                                       │  │
│  │  nginx:1.26  ──▶  php:8.2-fpm (app)  │  │
│  │     :80/:443        :9000             │  │
│  │                                       │  │
│  │  mysql:8.0    valkey:8                │  │
│  │     :3306        :6379                │  │
│  └───────────────────────────────────────┘  │
│                                             │
│  Certbot (Let's Encrypt) for SSL            │
│  UFW firewall: 22, 80, 443 only            │
└─────────────────────────────────────────────┘
```

## Domain & DNS

- **Domain**: `metanull.eu` (registered at OVH)
- **App URL**: `motivya.metanull.eu` (CNAME → `metanull.eu`)
- **Landing page**: `metanull.eu` / `www.metanull.eu` — static page linking to GitHub
- **DNS**: OVH DNS zone — A record for `metanull.eu` pointing to VPS IP, CNAME `www` and `motivya` → `metanull.eu`
- **SSL**: Let's Encrypt via Certbot, auto-renewing (cron or systemd timer) — covers all three hostnames

## VPS Provisioning Checklist

These steps are automated by `scripts/provision.sh` but listed here for reference:

### 1. Base OS

- Ubuntu 24.04 LTS (OVH default image)
- `apt update && apt upgrade -y`
- Set timezone: `timedatectl set-timezone Europe/Brussels`
- Set locale: `localectl set-locale LANG=fr_BE.UTF-8`

### 2. Security Hardening — Two-Account Model

The VPS has exactly two user accounts with distinct roles:

| Account | Purpose | SSH key | sudo | Used by |
|---------|---------|---------|------|--------|
| `ubuntu` | System administration (manual only) | `~/.ssh/ubuntu` | Yes | Human operator only |
| `deploy` | Application deployment | `~/.ssh/motivya_deploy` | **No** | CD pipeline + human |

**Rules:**
- The `ubuntu` account is NEVER used in GitHub Actions, deploy scripts, or any automation.
- The `deploy` account is NEVER given sudo access.
- The `deploy` user owns `/opt/motivya/` and all its contents — no elevation needed for deploys.
- If something requires root, it belongs in the one-time provisioning script (`scripts/provision.sh`), run manually via the `ubuntu` account.
- SSH key-only authentication (disable password auth)
- `PermitRootLogin no` in `/etc/ssh/sshd_config`
- UFW firewall: allow 22 (SSH), 80 (HTTP), 443 (HTTPS) only
- Fail2ban for SSH brute-force protection
- Automatic security updates: `unattended-upgrades`

### 3. Docker Installation

- Install Docker Engine (not Docker Desktop) from official apt repo
- Install Docker Compose v2 (plugin, not standalone)
- Add `deploy` user to `docker` group
- Configure Docker log rotation: `max-size: 10m`, `max-file: 3`

### 4. Application Directory

Owned by `deploy:deploy`. No root/sudo operations touch this tree during deploy.

```
/opt/motivya/                     # Owned by deploy:deploy
├── current -> releases/<ts>/     # Symlink to active release (atomic swap)
├── releases/                     # Timestamped release directories
│   ├── 20260403120000/
│   └── ...
├── shared/                       # Persists across deployments
│   ├── .env                      # Production .env (not in Git)
│   ├── database.sqlite           # SQLite DB (initial; MySQL later)
│   └── storage/                  # Laravel storage (symlinked into releases)
│       ├── app/public/
│       ├── framework/cache/data/
│       ├── framework/sessions/
│       ├── framework/views/
│       └── logs/
├── .env.production               # Legacy (from client repo setup)
├── backup-db.sh
├── backups/
├── certbot/
├── src/                          # Legacy (from client repo setup)
└── static/
```

### 5. SSL Setup

```bash
# Initial certificate
certbot certonly --standalone -d motivya.metanull.eu -d metanull.eu -d www.metanull.eu --agree-tos -m admin@metanull.eu

# Auto-renewal cron (runs twice daily, renews only if expiring within 30 days)
echo "0 */12 * * * certbot renew --quiet --deploy-hook 'docker compose -f /opt/motivya/docker-compose.prod.yml restart nginx'" | crontab -
```

### 6. Docker Compose Production

The production Compose file differs from dev:

| Aspect | Dev (`docker-compose.yml`) | Prod (`docker-compose.prod.yml`) |
|--------|---------------------------|----------------------------------|
| PHP image | Bind-mount source | COPY source into image |
| Nginx | Port 8000 | Ports 80 + 443 with SSL |
| MySQL | Port 3306 exposed | Port 3306 internal only |
| Valkey | Port 6379 exposed | Port 6379 internal only |
| Mailpit | Included | **Not included** — use real SMTP |
| Volumes | Bind mounts | Named volumes for persistence |
| Restart | No | `restart: unless-stopped` |
| Logging | Default | JSON file with rotation |

### 7. Environment Variables

Production `.env.production` (stored on VPS, never in Git):

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://motivya.metanull.eu

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=motivya
DB_USERNAME=motivya
DB_PASSWORD=<generated>

CACHE_STORE=redis
REDIS_HOST=valkey
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

MAIL_MAILER=smtp
MAIL_HOST=<smtp-provider>
MAIL_PORT=587

STRIPE_KEY=<stripe-publishable>
STRIPE_SECRET=<stripe-secret>
STRIPE_WEBHOOK_SECRET=<stripe-webhook-secret>
```

## Deployment Flow

Deploy is artifact-based — no git, composer, or npm on the VPS.

```
Developer pushes to main
        │
        ▼
GitHub Actions CI (ci.yml)
  lint → test → build release artifact (tarball)
        │ (all pass)
        ▼
GitHub Actions Deploy (deploy.yml)
  1. Download artifact from CI run
  2. SSH pre-checks (netcat port 22 + SSH whoami) — fail fast
  3. SCP release.tar.gz to VPS /tmp/
  4. SSH: bash /opt/motivya/current/scripts/deploy.sh <tarball>
        │
        ▼
VPS: scripts/deploy.sh (runs as deploy user, NO sudo)
  1. Extract to /opt/motivya/releases/<timestamp>/
  2. Symlink shared storage
  3. Create/symlink .env
  4. php artisan migrate --force
  5. php artisan config:cache, route:cache, view:cache
  6. Configure Nginx (via sudo for service reload only — see sudoers)
  7. Swap /opt/motivya/current symlink (atomic)
  8. Set permissions (www-data for storage)
  9. Prune old releases (keep last 5)
  10. Health check (curl localhost)
```

**First deploy bootstrap:** The deploy workflow inline-extracts the tarball to `/opt/motivya/current/` if `deploy.sh` doesn't exist yet, then runs `deploy.sh` normally.

## GitHub Environment Secrets

Secrets are stored in the **`motivya.metanull.eu`** GitHub Environment (not repo-level).
Configure at **Settings → Environments → motivya.metanull.eu → Environment secrets**:

| Secret | Value | Used by |
|--------|-------|---------|
| `VPS_HOST` | VPS IP address | `deploy.yml` SSH step |
| `VPS_SSH_KEY` | Private SSH key for `deploy` user | `deploy.yml` SSH step |
| `VPS_SSH_USER` | `deploy` | `deploy.yml` SSH step |

These are the only secrets needed for deployment. Application secrets (Stripe, DB password, etc.) live in `.env.production` on the VPS — never in GitHub.

## Backup Strategy

- **Database**: Daily mysqldump cron → `/opt/motivya/backups/motivya-$(date +%F).sql.gz`
- **Retention**: Keep 7 daily backups, delete older
- **Code**: Git is the source of truth — no code backup needed
- **Storage**: S3-compatible backup for uploaded files (when file uploads are implemented)

## Cost Summary

| Resource | Monthly cost |
|----------|-------------|
| VPS Starter | ~€3.50 |
| Domain `.ovh` | ~€0.30 |
| SSL (Let's Encrypt) | Free |
| **Total** | **~€3.80/mo** |

## Forbidden

- **NEVER use `sudo` in deploy scripts or CI/CD pipelines.** The `deploy` user owns `/opt/motivya/` — no elevation needed. If a deploy step requires root, the architecture is wrong.
- **NEVER use the `ubuntu` account in GitHub secrets, workflows, or automated scripts.** It is reserved for manual administration only.
- **NEVER install packages (apt-get) in deploy scripts.** Runtime dependencies are installed once via `scripts/provision.sh` (run manually as `ubuntu`).
- Do NOT expose MySQL or Valkey ports to the internet — internal Docker network only
- Do NOT store production secrets in Git or GitHub Secrets (except SSH key for deploy)
- Do NOT use `root` for SSH access or Docker operations
- Do NOT disable UFW or Fail2ban
- Do NOT use `docker compose down` in deploy scripts — it drops volumes. Use `restart` or `up -d --build`
- Do NOT run `migrate:fresh` in production — only `migrate --force`
