---
description: "Use when configuring the native-service production server, writing deployment scripts, configuring Nginx/SSL/systemd, or managing the OVH VPS. Covers the single-VPS production topology, SSH access, domain, and deployment flow."
applyTo: "scripts/**"
---

# Server Setup - OVH VPS Production

## Topology

A single OVH VPS Starter runs the production stack as native Ubuntu services.

```
┌─────────────────────────────────────────────┐
│  OVH VPS Starter (France)                   │
│  1 vCPU · 2 GB RAM · 20 GB SSD             │
│  Ubuntu 25.10                               │
│                                             │
│  ┌───────────────────────────────────────┐  │
│  │ Native services                       │  │
│  │                                       │  │
│  │  nginx  ──▶  php8.4-fpm               │  │
│  │   :80/:443     unix socket            │  │
│  │                                       │  │
│  │  mysql       valkey-server            │  │
│  │ 127.0.0.1    127.0.0.1                │  │
│  └───────────────────────────────────────┘  │
│                                             │
│  Certbot (Let's Encrypt) for SSL            │
│  UFW firewall: 22, 80, 443 only             │
└─────────────────────────────────────────────┘
```

## Domain & DNS

- **Domain**: `metanull.eu` (registered at OVH)
- **App URL**: `motivya.metanull.eu` (CNAME -> `metanull.eu`)
- **Landing page**: `metanull.eu` / `www.metanull.eu` - static page linking to GitHub
- **DNS**: OVH DNS zone - A record for `metanull.eu` pointing to VPS IP, CNAME `www` and `motivya` -> `metanull.eu`
- **SSL**: Let's Encrypt via Certbot, auto-renewing through the systemd timer - covers all three hostnames

## VPS Provisioning Checklist

These steps are automated by `scripts/provision.sh` but listed here for reference:

### 1. Base OS

- Ubuntu 25.10 (current production baseline)
- `apt update && apt upgrade -y`
- Set timezone: `timedatectl set-timezone Europe/Brussels`
- Set locale: `localectl set-locale LANG=fr_BE.UTF-8`

### 2. Security Hardening - Two-Account Model

The VPS has exactly two user accounts with distinct roles:

| Account | Purpose | SSH key | sudo | Used by |
|---------|---------|---------|------|--------|
| `ubuntu` | System administration (manual only) | `~/.ssh/ubuntu` | Yes | Human operator only |
| `deploy` | Application deployment | `~/.ssh/motivya_deploy` | **No** | CD pipeline + human |

**Rules:**
- The `ubuntu` account is NEVER used in GitHub Actions, deploy scripts, or any automation.
- The `deploy` account is NEVER given sudo access.
- The `deploy` user owns `/opt/motivya/` and all its contents - no elevation needed for deploys.
- If something requires root, it belongs in the one-time provisioning script (`scripts/provision.sh`), run manually via the `ubuntu` account.
- SSH key-only authentication (disable password auth)
- `PermitRootLogin no` in `/etc/ssh/sshd_config`
- UFW firewall: allow 22 (SSH), 80 (HTTP), 443 (HTTPS) only
- Fail2ban for SSH brute-force protection
- Automatic security updates: `unattended-upgrades`

### 3. Runtime Installation

- Install Nginx, PHP 8.4 FPM, required PHP extensions, MySQL, Valkey, Certbot, UFW, and Fail2ban via apt
- Add `deploy` user to `www-data` for shared file permissions
- Add `deploy` user to `docker` only when local operational tooling on the VPS still needs Docker for non-production tasks; production serving does not depend on containers

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
│   ├── database.sqlite           # Legacy fallback file created only when MySQL credentials are absent
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

# Auto-renewal is handled by the systemd timer installed with Certbot.
# If you need to verify it manually:
systemctl status certbot.timer
```

### 6. Native-Service Production

Production does not use `docker-compose.prod.yml`.

| Aspect | Local dev (`docker-compose.yml`) | Production VPS |
|--------|----------------------------------|----------------|
| Web server | Containerized Nginx | Native `nginx` systemd service |
| PHP runtime | Containerized app service | Native `php8.4-fpm` |
| Database | Containerized MySQL | Native `mysql` bound to localhost |
| Cache / queue backend | Containerized Valkey | Native `valkey-server` bound to localhost |
| Process supervision | Docker | `systemd` units and timers |
| Mailpit | Included | Not installed |
| Deploy artifact | Bind-mounted source | Release tarball extracted to `/opt/motivya/releases/<ts>/` |

### 7. Environment Variables

Production `.env` lives at `/opt/motivya/shared/.env` on the VPS and is symlinked into each release. Legacy `.env.production` files may still exist on disk, but the deploy flow uses the shared `.env` symlink.

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://motivya.metanull.eu

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=motivya
DB_USERNAME=motivya
DB_PASSWORD=<generated>

CACHE_STORE=redis
REDIS_HOST=127.0.0.1
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

Deploy is artifact-based - no git, composer, or npm on the VPS.

```
Developer pushes to main
        │
        ▼
GitHub Actions CI (ci.yml)
  lint -> test -> build release artifact (tarball)
        │ (all pass)
        ▼
GitHub Actions Deploy (deploy.yml)
  1. Download artifact from CI run
  2. SSH pre-checks (netcat port 22 + SSH whoami) - fail fast
  3. SCP release.tar.gz to VPS /tmp/
  4. SSH: extract a staging copy and run `scripts/deploy.sh` from the artifact
        │
        ▼
VPS: scripts/deploy.sh (runs as deploy user, NO sudo)
  1. Extract to /opt/motivya/releases/<timestamp>/
  2. Symlink shared storage and `public/storage`
  3. Create/symlink `.env` from `/opt/motivya/shared/.env`
  4. Run `php artisan migrate --force`
  5. Warm config, route, and view caches
  6. Signal queue workers with `php artisan queue:restart`
  7. Swap `/opt/motivya/current` atomically
  8. Prune old releases (keep last 5)
  9. Health check via `curl http://localhost/health`
```

**First deploy bootstrap:** The deploy workflow always extracts the tarball to a staging directory first so the latest `deploy.sh` from the artifact is used even when the current release is stale.

## GitHub Environment Secrets

Secrets are stored in the **`motivya.metanull.eu`** GitHub Environment (not repo-level).
Configure at **Settings -> Environments -> motivya.metanull.eu -> Environment secrets**:

| Secret | Value | Used by |
|--------|-------|---------|
| `VPS_HOST` | VPS IP address | `deploy.yml` SSH step |
| `VPS_SSH_KEY` | Private SSH key for `deploy` user | `deploy.yml` SSH step |
| `VPS_SSH_USER` | `deploy` | `deploy.yml` SSH step |

These are the only secrets needed for deployment. Application secrets (Stripe, DB password, etc.) live in `/opt/motivya/shared/.env` on the VPS - never in GitHub.

## Backup Strategy

- **Database**: Daily mysqldump cron -> `/opt/motivya/backups/motivya-$(date +%F).sql.gz`
- **Retention**: Keep 7 daily backups, delete older
- **Code**: Git is the source of truth - no code backup needed
- **Storage**: S3-compatible backup for uploaded files (when file uploads are implemented)

## Cost Summary

| Resource | Monthly cost |
|----------|-------------|
| VPS Starter | ~EUR 3.50 |
| Domain `.ovh` | ~EUR 0.30 |
| SSL (Let's Encrypt) | Free |
| **Total** | **~EUR 3.80/mo** |

## Forbidden

- **NEVER use `sudo` in deploy scripts or CI/CD pipelines.** The `deploy` user owns `/opt/motivya/` - no elevation needed. If a deploy step requires root, the architecture is wrong.
- **NEVER use the `ubuntu` account in GitHub secrets, workflows, or automated scripts.** It is reserved for manual administration only.
- **NEVER install packages (apt-get) in deploy scripts.** Runtime dependencies are installed once via `scripts/provision.sh` (run manually as `ubuntu`).
- Do NOT expose MySQL or Valkey ports to the internet - bind them to localhost only
- Do NOT store production secrets in Git or GitHub Secrets (except SSH key for deploy)
- Do NOT use `root` for SSH access or routine application operations
- Do NOT disable UFW or Fail2ban
- Do NOT rebuild or replace the production runtime from ad hoc container commands; provision native services through `scripts/provision.sh` and deploy application code through `scripts/deploy.sh`
- Do NOT run `migrate:fresh` in production - only `migrate --force`
