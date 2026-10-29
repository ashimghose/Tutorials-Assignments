# The Phase 2 server

One Lightsail server (Ubuntu 24.04, Mumbai, static IP 16.4.125.251) runs the
whole site: Nginx, PHP 8.4-FPM, PostgreSQL, the queue worker and the
scheduler. Cloudflare sits in front of it.

| What | Where |
|---|---|
| App code | `/var/www/indianassignments` (a clone of `main`, owned by `www-data`) |
| Settings and secrets | `/var/www/indianassignments/.env` (never committed) |
| Web server | `/etc/nginx/sites-available/indianassignments` (from `deploy/nginx.conf`) |
| Queue worker | `systemctl status indianassignments-queue` (from `deploy/queue-worker.service`) |
| Scheduler | `/etc/cron.d/indianassignments`, runs `schedule:run` every minute |
| Logs | `storage/logs/laravel-*.log`, `journalctl -u indianassignments-queue` |

## First set-up

In Lightsail's browser terminal ("Connect using SSH"):

```bash
curl -fsSL https://raw.githubusercontent.com/ashimghose/Tutorials-Assignments/main/deploy/provision.sh | sudo bash
```

It ends with `SERVER-READY`. It creates the database and its password, and
writes `.env` with production values and the Phase 2 modules on (online
checkout off). It's safe to run again.

Then make the first admin: `cd /var/www/indianassignments && sudo -u www-data php artisan app:make-admin`.

## Email and file storage keys

```bash
sudo bash /var/www/indianassignments/deploy/set-keys.sh
```

It asks for the Brevo SMTP login and key, the sender address and name, the
owner's email, and the R2 key, secret, endpoint and bucket names (secrets are
typed hidden). It writes them to `.env`, switches order files and backups to
R2, creates the backup password once (shown on screen to write down), then
checks R2, sends a test email and runs a backup.

## Deploying a new version

```bash
sudo bash /var/www/indianassignments/deploy/deploy.sh
```

This puts the site in maintenance mode, pulls `main`, installs, builds,
migrates, seeds new content only, caches the config and restarts the worker.
After changing `.env`, run `sudo -u www-data php artisan optimize` in the app
folder so the change takes effect.
