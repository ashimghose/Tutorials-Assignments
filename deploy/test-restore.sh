#!/usr/bin/env bash
# Proves the backups can be used: downloads the newest backup from R2, opens
# it with the backup password, loads it into a throw-away database, counts
# the accounts in it, then deletes the copy. The live database isn't touched.
#
#   sudo bash /var/www/indianassignments/deploy/test-restore.sh
set -euo pipefail

APP_DIR=/var/www/indianassignments
cd "${APP_DIR}"
[ "$(id -u)" -eq 0 ] || { echo "Run with sudo."; exit 1; }

WORK=$(mktemp -d)
chown www-data:www-data "${WORK}"
cleanup() {
    rm -rf "${WORK}"
    sudo -u postgres dropdb --if-exists restore_test 2>/dev/null || true
}
trap cleanup EXIT

sudo -u www-data -H php artisan tinker --execute="
    \$disk = Storage::disk(config('backup.backup.destination.disks')[0]);
    \$file = collect(\$disk->allFiles())->filter(fn (\$f) => str_ends_with(\$f, '.zip'))->sort()->last();
    if (! \$file) { fwrite(STDERR, '!! No backup found'.PHP_EOL); exit(1); }
    echo 'Latest backup: '.\$file.PHP_EOL;
    file_put_contents('${WORK}/backup.zip', \$disk->get(\$file));
    \$zip = new ZipArchive;
    \$zip->open('${WORK}/backup.zip');
    \$zip->setPassword((string) config('backup.backup.password'));
    if (! \$zip->extractTo('${WORK}/out')) { fwrite(STDERR, '!! The backup password did not open the zip'.PHP_EOL); exit(1); }
"

SQL=$(find "${WORK}/out" -name '*.sql' | head -n 1)
[ -n "${SQL}" ] || { echo "!! No database dump inside the backup"; exit 1; }

sudo -u postgres dropdb --if-exists restore_test
sudo -u postgres createdb restore_test
sudo -u postgres psql -q -v ON_ERROR_STOP=1 -d restore_test < "${SQL}" >/dev/null
USERS=$(sudo -u postgres psql -tA -d restore_test -c 'SELECT count(*) FROM users')
echo "Restored into a test database: ${USERS} account(s) found"

echo
echo "==> RESTORE-OK"
