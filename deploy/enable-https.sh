#!/usr/bin/env bash
# Gives the server its own HTTPS certificate so Cloudflare can use
# "Full (strict)". Run it once the domain points at this server (Cloudflare
# SSL/TLS mode "Full" while it runs). Until then the server uses a stand-in
# certificate. Let's Encrypt renews the certificate by itself.
#
#   sudo bash /var/www/indianassignments/deploy/enable-https.sh
set -euo pipefail

APP_DIR=/var/www/indianassignments
DOMAIN=indianassignments.com
SNIPPET=/etc/nginx/snippets/indianassignments-ssl.conf
cd "${APP_DIR}"
[ "$(id -u)" -eq 0 ] || { echo "Run with sudo."; exit 1; }

# The stand-in certificate keeps Nginx's HTTPS port working until the real one exists.
apt-get install -yq ssl-cert >/dev/null
if [ ! -f "${SNIPPET}" ]; then
    printf 'ssl_certificate /etc/ssl/certs/ssl-cert-snakeoil.pem;\nssl_certificate_key /etc/ssl/private/ssl-cert-snakeoil.key;\n' > "${SNIPPET}"
fi
cp deploy/nginx.conf /etc/nginx/sites-available/indianassignments
nginx -t -q
systemctl reload nginx

EMAIL=$(grep -E '^CONTACT_EMAIL=' .env | cut -d= -f2- | tr -d '"' || true)
if [ -n "${EMAIL}" ]; then ACCOUNT=(-m "${EMAIL}"); else ACCOUNT=(--register-unsafely-without-email); fi
certbot certonly --webroot -w "${APP_DIR}/public" \
    -d "${DOMAIN}" -d "www.${DOMAIN}" \
    --non-interactive --agree-tos --keep-until-expiring \
    "${ACCOUNT[@]}" \
    --deploy-hook "systemctl reload nginx" \
    || { echo "!! No certificate yet: does ${DOMAIN} point at this server, with Cloudflare SSL set to Full?"; exit 1; }

printf 'ssl_certificate /etc/letsencrypt/live/%s/fullchain.pem;\nssl_certificate_key /etc/letsencrypt/live/%s/privkey.pem;\n' "${DOMAIN}" "${DOMAIN}" > "${SNIPPET}"
nginx -t -q
systemctl reload nginx

echo
echo "==> HTTPS-DONE"
