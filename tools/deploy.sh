#!/usr/bin/env bash
# Deploys the log theme and log-core plugin to uPress over FTPS.
# Password comes from the macOS Keychain (service: log-pm-ftp).
set -euo pipefail
cd "$(dirname "$0")/.."

HOST=FTP_HOST_REMOVED
USER='FTP_USER_REMOVED'
PASS="$(security find-generic-password -s log-pm-ftp -w)"

if [ -f wp-content/themes/log/package.json ]; then
  (cd wp-content/themes/log && npm run build --silent)
fi

lftp -u "$USER,$PASS" "ftps://$HOST" <<LFTP
set ftp:ssl-force true
set ftp:ssl-protect-data true
set ssl:verify-certificate no
set net:timeout 30
set net:max-retries 2
mirror -R --delete --verbose=1 \
  --exclude-glob node_modules/ --exclude-glob .DS_Store --exclude-glob src/ \
  wp-content/plugins/log-core /wp-content/plugins/log-core
mirror -R --delete --verbose=1 \
  --exclude-glob node_modules/ --exclude-glob .DS_Store --exclude-glob src/ \
  wp-content/themes/log /wp-content/themes/log
bye
LFTP
echo "Deployed."
