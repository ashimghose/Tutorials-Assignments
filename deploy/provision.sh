#!/usr/bin/env bash
# One-time set-up of the Phase 2 server (Ubuntu 24.04 on Lightsail).
# Installs PHP 8.4, PostgreSQL, Nginx, Node.js, the queue worker and the
# scheduler, then clones the app and deploys it. Safe to run again: each
# step skips what is already done. Secrets it creates (the database
# password, APP_KEY) are written only to the server's .env.
#
#   curl -fsSL https://raw.githubusercontent.com/ashimghose/Tutorials-Assignments/main/deploy/provision.sh | sudo bash
set -euo pipefail

APP_DIR=/var/www/indianassignments
REPO=https://github.com/ashimghose/Tutorials-Assignments.git
DOMAIN=indianassignments.com
DB_NAME=indianassignments
DB_USER=indianassignments
export DEBIAN_FRONTEND=noninteractive

step() { printf '\n==> %s\n' "$1"; }

[ "$(id -u)" -eq 0 ] || { echo "Run with sudo."; exit 1; }

step "Swap file (2 GB), so builds don't run out of memory"
if [ ! -f /swapfile ]; then
    fallocate -l 2G /swapfile && chmod 600 /swapfile && mkswap /swapfile && swapon /swapfile
    echo '/swapfile none swap sw 0 0' >> /etc/fstab
fi

step "Packages: PHP 8.4, PostgreSQL, Nginx, Node.js 24"
apt-get update -q
apt-get install -yq software-properties-common ca-certificates curl git unzip cron
if ! ls /etc/apt/sources.list.d | grep -q ondrej; then
    add-apt-repository -y ppa:ondrej/php
fi
if ! command -v node >/dev/null || ! node --version | grep -q '^v24'; then
    curl -fsSL https://deb.nodesource.com/setup_24.x | bash -
fi
apt-get update -q
apt-get install -yq nginx postgresql postgresql-client nodejs certbot python3-certbot-nginx \
    php8.4-fpm php8.4-cli php8.4-pgsql php8.4-sqlite3 php8.4-mbstring php8.4-xml php8.4-curl \
    php8.4-zip php8.4-bcmath php8.4-intl php8.4-gd php8.4-opcache
if ! command -v composer >/dev/null; then
    curl -fsSL https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

step "PHP limits: 25 MB order files, 256 MB memory"
cat > /etc/php/8.4/fpm/conf.d/99-indianassignments.ini <<'INI'
upload_max_filesize = 30M
post_max_size = 32M
memory_limit = 256M
expose_php = Off
INI
systemctl restart php8.4-fpm

step "Database"
if ! sudo -u postgres psql -tAc "SELECT 1 FROM pg_roles WHERE rolname='${DB_USER}'" | grep -q 1; then
    DB_PASSWORD=$(openssl rand -hex 24)
    sudo -u postgres psql -qc "CREATE ROLE ${DB_USER} LOGIN PASSWORD '${DB_PASSWORD}'"
    sudo -u postgres psql -qc "CREATE DATABASE ${DB_NAME} OWNER ${DB_USER}"
else
    DB_PASSWORD=""
fi

step "App code in ${APP_DIR}"
mkdir -p /var/www
chown www-data:www-data /var/www
if [ ! -d "${APP_DIR}/.git" ]; then
    sudo -u www-data -H git clone -q "${REPO}" "${APP_DIR}"
fi

step "Settings file (.env)"
cd "${APP_DIR}"
if [ ! -f .env ]; then
    sudo -u www-data cp .env.example .env
    chmod 640 .env
    set_env() {
        local key=$1 value=$2
        if grep -qE "^#? ?${key}=" .env; then
            sed -i -E "s|^#? ?${key}=.*|${key}=${value}|" .env
        else
            echo "${key}=${value}" >> .env
        fi
    }
    set_env APP_NAME '"Indian Assignments"'
    set_env APP_ENV production
    set_env APP_DEBUG false
    set_env APP_URL "https://${DOMAIN}"
    set_env LOG_STACK daily
    set_env LOG_LEVEL warning
    set_env DB_CONNECTION pgsql
    set_env DB_HOST 127.0.0.1
    set_env DB_PORT 5432
    set_env DB_DATABASE "${DB_NAME}"
    set_env DB_USERNAME "${DB_USER}"
    set_env DB_PASSWORD "${DB_PASSWORD}"
    set_env SESSION_SECURE_COOKIE true
    set_env APP_PHASE 2
    for flag in ACCOUNTS CMS BATCHES ACADEMIC_ORDERS CHAT; do set_env "FEATURE_${flag}" true; done
    set_env FEATURE_CHECKOUT false
    set_env RAZORPAY_KEY_ID ''
    chown www-data:www-data .env
fi

step "Web server"
apt-get install -yq ssl-cert
if [ ! -f /etc/nginx/snippets/indianassignments-ssl.conf ]; then
    printf 'ssl_certificate /etc/ssl/certs/ssl-cert-snakeoil.pem;\nssl_certificate_key /etc/ssl/private/ssl-cert-snakeoil.key;\n' \
        > /etc/nginx/snippets/indianassignments-ssl.conf
fi
cp deploy/nginx.conf /etc/nginx/sites-available/indianassignments
ln -sf /etc/nginx/sites-available/indianassignments /etc/nginx/sites-enabled/indianassignments
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl reload nginx

step "Queue worker and scheduler"
cp deploy/queue-worker.service /etc/systemd/system/indianassignments-queue.service
systemctl daemon-reload
systemctl enable -q indianassignments-queue
cat > /etc/cron.d/indianassignments <<CRON
* * * * * www-data cd ${APP_DIR} && php artisan schedule:run >> /dev/null 2>&1
CRON

step "First deploy"
bash deploy/deploy.sh

printf '\n==> SERVER-READY\n'
