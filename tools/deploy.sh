#!/usr/bin/env bash
# Deploys the log theme and log-core plugin to uPress over FTPS.
# Reads FTP_HOST, FTP_USER and FTP_PASSWORD from the environment (GitHub Actions secrets).
# Locally: host and user come from .env (not in git), the password from the macOS Keychain (service: log-pm-ftp).
set -euo pipefail
cd "$(dirname "$0")/.."

if [ -f .env ]; then
  set -a; . ./.env; set +a
fi
: "${FTP_HOST:?FTP_HOST is not set (add it to .env)}"
: "${FTP_USER:?FTP_USER is not set (add it to .env)}"
if [ -z "${FTP_PASSWORD:-}" ]; then
  FTP_PASSWORD="$(security find-generic-password -s log-pm-ftp -w)"
fi
export LFTP_PASSWORD="$FTP_PASSWORD"

if [ -f wp-content/themes/log/package.json ]; then
  (cd wp-content/themes/log && npm run build --silent)
fi

# Explicit FTPS (AUTH TLS on port 21); the server also requires TLS session reuse on data connections.
lftp --env-password -u "$FTP_USER" "ftp://$FTP_HOST" <<LFTP
set ftp:ssl-force true
set ftp:ssl-protect-data true
set ftp:ssl-auth TLS
set ssl:verify-certificate no
set net:timeout 30
set net:max-retries 2
mirror -R --delete --verbose=1 \
  --exclude-glob node_modules/ --exclude-glob .DS_Store --exclude-glob src/ --exclude-glob .impeccable/ \
  wp-content/plugins/log-core /wp-content/plugins/log-core
mirror -R --delete --verbose=1 \
  --exclude-glob node_modules/ --exclude-glob .DS_Store --exclude-glob src/ --exclude-glob .impeccable/ \
  wp-content/themes/log /wp-content/themes/log
bye
LFTP
echo "Deployed."
