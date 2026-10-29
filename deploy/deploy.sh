#!/usr/bin/env bash
# Puts the latest main live on the server. Run on the server:
#   sudo bash /var/www/indianassignments/deploy/deploy.sh
# Everything runs as www-data, the user PHP and the queue worker run as.
set -euo pipefail

APP_DIR=/var/www/indianassignments
cd "${APP_DIR}"

as_app() { sudo -u www-data -H "$@"; }

[ "$(id -u)" -eq 0 ] || { echo "Run with sudo."; exit 1; }

as_app php artisan down --retry=30 || true
trap 'as_app php artisan up' EXIT

as_app git fetch -q origin main
as_app git reset -q --hard origin/main

as_app composer install -q --no-dev --no-interaction --prefer-dist --optimize-autoloader
as_app npm ci --no-audit --no-fund --silent
as_app npm run build --silent

grep -q '^APP_KEY=base64:' .env || as_app php artisan key:generate --force
as_app php artisan migrate --force
as_app php artisan db:seed --force
as_app php artisan storage:link 2>/dev/null || true
as_app php artisan optimize
as_app php artisan filament:optimize

systemctl reload php8.4-fpm
systemctl restart indianassignments-queue

echo "Deployed $(as_app git log -1 --format='%h %s')"
