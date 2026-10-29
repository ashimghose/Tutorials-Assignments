#!/usr/bin/env bash
# Asks for the email (Brevo) and file storage (Cloudflare R2) keys, writes
# them into the server's .env, then checks each service works. Keys are typed
# here only: nothing is shown on screen or sent anywhere else. Safe to run
# again to change a key.
#
#   sudo bash /var/www/indianassignments/deploy/set-keys.sh
set -euo pipefail

APP_DIR=/var/www/indianassignments
cd "${APP_DIR}"
[ "$(id -u)" -eq 0 ] || { echo "Run with sudo."; exit 1; }

as_app() { sudo -u www-data -H "$@"; }

set_env() {
    local key=$1 value=$2
    value=${value//\\/\\\\}
    value=${value//\"/\\\"}
    value="\"${value}\""
    if grep -qE "^#? ?${key}=" .env; then
        KEY="${key}" VALUE="${value}" perl -i -pe 's/^#? ?\Q$ENV{KEY}\E=.*/$ENV{KEY}=$ENV{VALUE}/' .env
    else
        printf '%s=%s\n' "${key}" "${value}" >> .env
    fi
}

ask() { local answer; read -r -p "$1: " answer </dev/tty; printf '%s' "${answer}"; }
ask_secret() { local answer; read -r -s -p "$1 (hidden): " answer </dev/tty; echo >&2; printf '%s' "${answer}"; }

echo "== Email (Brevo: SMTP and API -> SMTP)"
SMTP_LOGIN=$(ask "SMTP login (looks like 1a2b3c@smtp-brevo.com)")
SMTP_KEY=$(ask_secret "SMTP key")
FROM_ADDRESS=$(ask "Send emails from (e.g. noreply@indianassignments.com)")
FROM_NAME=$(ask "Sender name people see (e.g. Indian Assignments)")
CONTACT=$(ask "Your email for replies and alerts")

echo
echo "== Files and backups (Cloudflare R2 -> Manage API tokens)"
R2_KEY=$(ask "R2 Access Key ID")
R2_SECRET=$(ask_secret "R2 Secret Access Key")
R2_URL=$(ask "R2 endpoint (https://<account id>.r2.cloudflarestorage.com)")
R2_FILES=$(ask "Files bucket name [ia-files]"); R2_FILES=${R2_FILES:-ia-files}
R2_BACKUPS=$(ask "Backups bucket name [ia-backups]"); R2_BACKUPS=${R2_BACKUPS:-ia-backups}

set_env MAIL_MAILER smtp
set_env MAIL_SCHEME smtp
set_env MAIL_HOST smtp-relay.brevo.com
set_env MAIL_PORT 587
set_env MAIL_USERNAME "${SMTP_LOGIN}"
set_env MAIL_PASSWORD "${SMTP_KEY}"
set_env MAIL_FROM_ADDRESS "${FROM_ADDRESS}"
set_env MAIL_FROM_NAME "${FROM_NAME}"
set_env CONTACT_EMAIL "${CONTACT}"
set_env BACKUP_NOTIFY_EMAIL "${CONTACT}"
set_env R2_ACCESS_KEY_ID "${R2_KEY}"
set_env R2_SECRET_ACCESS_KEY "${R2_SECRET}"
set_env R2_ENDPOINT "${R2_URL%/}"
set_env R2_BUCKET "${R2_FILES}"
set_env R2_BACKUP_BUCKET "${R2_BACKUPS}"
set_env ORDER_FILES_DISK r2
set_env BACKUP_DISK r2-backups

NEW_BACKUP_PASSWORD=""
if ! grep -qE '^BACKUP_ARCHIVE_PASSWORD=.+' .env; then
    NEW_BACKUP_PASSWORD=$(openssl rand -base64 18 | tr -d '/+=')
    set_env BACKUP_ARCHIVE_PASSWORD "${NEW_BACKUP_PASSWORD}"
fi

as_app php artisan optimize >/dev/null
systemctl restart indianassignments-queue

echo
echo "== Checking"
as_app php artisan tinker --execute="
    \$disk = Storage::disk('r2'); \$disk->put('setup-check.txt', 'ok'); \$disk->delete('setup-check.txt');
    echo 'Files bucket: OK'.PHP_EOL;
    Mail::raw('The website can send email.', fn (\$m) => \$m->to('${CONTACT//\'/}')->subject('Test email from your website'));
    echo 'Test email: sent to ${CONTACT//\'/}'.PHP_EOL;
" || echo "!! A check failed: send a screenshot."
as_app php artisan backup:run --only-db --disable-notifications >/dev/null && echo "Backup to R2: OK" || echo "!! The backup failed: send a screenshot."

if [ -n "${NEW_BACKUP_PASSWORD}" ]; then
    echo
    echo "== Backup password (write it down and keep it safe, NOT in chat):"
    echo "   ${NEW_BACKUP_PASSWORD}"
    echo "   Without it the backups can't be opened."
fi
echo
echo "==> KEYS-DONE"
