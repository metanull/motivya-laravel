# Production Readiness Runbook

> **Scope**: This runbook covers the non-secret operational steps for first deploy, post-deploy verification, and pre-demo readiness on the OVH VPS. It tells you exactly which commands to run, in which order, on the server versus locally, and which are safe in production.
>
> **Security rule**: Never commit `.env` files, passwords, or Stripe keys. Never run `MvpJourneySeeder` in production.

---

## Table of Contents

1. [First Deploy Checklist](#1-first-deploy-checklist)
2. [Scheduler Setup Verification](#2-scheduler-setup-verification)
3. [Queue Worker Verification](#3-queue-worker-verification)
4. [Public Storage Symlink](#4-public-storage-symlink)
5. [Postal Code Reference Data](#5-postal-code-reference-data)
6. [Session Coordinate Backfill](#6-session-coordinate-backfill)
7. [Payment Reconciliation](#7-payment-reconciliation)
8. [Readiness Page Review](#8-readiness-page-review)
9. [Pre-Demo Quick Smoke](#9-pre-demo-quick-smoke)
10. [Local-Only Commands (never run in production)](#10-local-only-commands-never-run-in-production)

---

## 1. First Deploy Checklist

Run these steps **as root** on a fresh VPS before first deploy:

```bash
# 1. Provision the VPS (sets up PHP, Nginx, MySQL, Valkey, queue worker, scheduler timer)
sudo bash scripts/provision.sh 'ssh-ed25519 AAAA... your-public-key'

# 2. Obtain SSL certificate (requires DNS pointing to the VPS)
certbot certonly --standalone -d motivya.example.com --agree-tos -m admin@example.com
nginx -t && systemctl reload nginx

# 3. Prepare shared .env (as deploy user — never commit)
cp /opt/motivya/current/.env.example /opt/motivya/shared/.env
# Edit /opt/motivya/shared/.env: APP_KEY, DB_*, STRIPE_*, MAIL_*, etc.
```

Run these steps **as the deploy user** after each release artifact is available:

```bash
bash /opt/motivya/current/scripts/deploy.sh /tmp/motivya-release.tar.gz
```

Deploy.sh automatically:
- Extracts the release, symlinks shared storage, creates `public/storage → shared/storage/app/public`
- Runs `php artisan migrate --force`
- Warms config/route/view caches
- Signals the queue worker to restart

---

## 2. Scheduler Setup Verification

The scheduler is installed as `motivya-scheduler.service` + `motivya-scheduler.timer` by `provision.sh`. Verify it is running:

```bash
# Check timer is active and next fire time
systemctl status motivya-scheduler.timer

# View last scheduler log lines
journalctl -u motivya-scheduler.service --since "30 minutes ago"

# View scheduler log file (alternative)
tail -n 50 /opt/motivya/shared/storage/logs/scheduler.log
```

Expected output after first run: heartbeat rows for all critical commands appear in the database.

```bash
# Verify heartbeats (as deploy user)
cd /opt/motivya/current
php artisan tinker --execute="echo App\Models\SchedulerHeartbeat::count().' heartbeat rows';"
```

If the timer is not active after provisioning:

```bash
# Start the timer (as root, only needed if current is a real release)
systemctl start motivya-scheduler.timer
systemctl enable motivya-scheduler.timer
```

The readiness page (`/admin/readiness`) shows green/yellow/red per command. The scheduler section title now reads **"Exécution des tâches planifiées"** (fr) / **"Scheduled Task Status"** (en) and tells you to run `systemctl status motivya-scheduler.timer` if a command has never run.

---

## 3. Queue Worker Verification

```bash
# Check queue worker status
systemctl status motivya-queue.service

# View queue worker logs
tail -n 50 /opt/motivya/shared/storage/logs/queue-worker.log

# Restart if code was changed outside a deploy
systemctl restart motivya-queue.service
```

---

## 4. Public Storage Symlink

The `public/storage` symlink is created by `deploy.sh` **before** the atomic `current` swap, so every release contains a valid symlink. Verify it exists:

```bash
ls -la /opt/motivya/current/public/storage
# Expected: symlink → /opt/motivya/shared/storage/app/public
```

If missing (e.g., on a release deployed before this fix):

```bash
ln -sfn /opt/motivya/shared/storage/app/public /opt/motivya/current/public/storage
```

The readiness page shows a **red** status if the symlink is absent, and **yellow** if a referenced image file is not found under the symlink target.

---

## 5. Postal Code Reference Data

**Production-safe** — runs only an idempotent data load, no demo users:

```bash
cd /opt/motivya/current
php artisan geo:load-postal-codes
```

Verify:

```bash
php artisan tinker --execute="echo App\Models\PostalCodeCoordinate::count().' rows';"
```

The readiness page shows a **red** status with the exact command when `postal_code_coordinates = 0`.

---

## 6. Session Coordinate Backfill

After loading postal codes, backfill existing sessions that are missing GPS coordinates:

```bash
cd /opt/motivya/current
php artisan sessions:backfill-coordinates
```

This command is idempotent. It skips sessions that already have lat/lng. Verify:

```bash
php artisan tinker --execute="echo App\Models\SportSession::whereNull('latitude')->count().' sessions still missing coordinates';"
```

The readiness page shows separate statuses for **reference data** (`postal_code_reference`) and **session coordinates** (`session_coordinates`).

---

## 7. Payment Reconciliation

**Dry-run first — safe in production:**

```bash
cd /opt/motivya/current
php artisan payments:reconcile-bookings --dry-run
```

Review output. If there are bookings that can be safely repaired (single unambiguous Stripe match):

```bash
php artisan payments:reconcile-bookings --repair
```

The `--repair` flag queries Stripe and writes only when a single confirmed safe match is found. Repairs are recorded as audit events.

The readiness page shows a **red** status for confirmed paid bookings missing `stripe_payment_intent_id`, with a link to the anomalies page.

---

## 8. Readiness Page Review

Navigate to `/admin/readiness` as an admin. Every check should be green before a partner demo. 

| Check key | What it measures | Fix command / link |
|---|---|---|
| `stripe` | Stripe API key format | `/admin/configuration/billing` |
| `mail` | Mail driver config | Edit `.env` |
| `database` | DB connection | Restart MySQL / check `.env` |
| `cache` | Cache driver | Restart Valkey |
| `queue` | Queue driver config | `systemctl restart motivya-queue` |
| `scheduler` | All heartbeats recent | Section 2 of this runbook |
| `public_storage` | Symlink + image reachability | Section 4 |
| `postal_code_reference` | Reference coordinate rows | Section 5 |
| `session_coordinates` | Sessions with GPS | Section 6 |
| `payment_anomalies` | Paid bookings missing intent | Section 7 |
| `stripe_connect` | Coach onboarding complete | `/admin/coach-approval` |
| `admin_mfa` | Admin user with MFA | `/admin/users` |
| `accountant` | Accountant user exists | `/admin/users` |
| `activity_images` | At least one image uploaded | `/admin/activity-images` |
| `billing_config` | Billing config route registered | App deploy |

The readiness page also shows the **Operational Repair Tools** panel with the exact commands to copy and run on the server for each issue.

---

## 9. Pre-Demo Quick Smoke

Run the health snapshot command to verify the most critical production state:

```bash
cd /opt/motivya/current
php artisan mvp:health-snapshot
```

The command outputs a JSON/table summary of scheduler heartbeats, postal code counts, sessions missing coordinates, public storage status, payment anomalies, and Stripe config. It exits non-zero when critical blockers are present.

For the full athlete booking flow, follow `doc/MVP-Smoke-Test.md` locally with `MvpJourneySeeder` data.

---

## 10. Local-Only Commands (never run in production)

| Command | Reason |
|---|---|
| `php artisan db:seed --class=MvpJourneySeeder` | Creates predictable demo credentials |
| `php artisan db:seed` | Runs `MvpJourneySeeder` via `DatabaseSeeder` |
| `php artisan migrate:fresh` | Drops all production data |
| `php artisan migrate:reset` | Drops all production data |
| `php artisan tinker` without `--execute` | Interactive REPL risks accidental writes |

Production data loads that are safe:

| Command | Safe? |
|---|---|
| `php artisan geo:load-postal-codes` | ✅ Idempotent, no demo data |
| `php artisan sessions:backfill-coordinates` | ✅ Idempotent, updates GPS only |
| `php artisan payments:reconcile-bookings --dry-run` | ✅ Read-only |
| `php artisan payments:reconcile-bookings --repair` | ✅ With review (writes only safe matches) |
| `php artisan migrate --force` | ✅ Run by deploy.sh |
| `php artisan mvp:health-snapshot` | ✅ Read-only |
