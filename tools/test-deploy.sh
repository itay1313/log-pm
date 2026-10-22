#!/usr/bin/env bash
# Runs tools/tests/deploy-test.php against log-core's deploy endpoint code in php-wasm (no native PHP on this Mac).
set -euo pipefail
cd "$(dirname "$0")/.."
dir="$(mktemp -d)"
trap 'rm -rf "$dir"' EXIT
cp wp-content/plugins/log-core/inc/deploy.php tools/tests/deploy-test.php "$dir/"
openssl genrsa -out "$dir/k1.pem" 2048 2>/dev/null
openssl genrsa -out "$dir/k2.pem" 2048 2>/dev/null
cd "$dir" && npx -y @php-wasm/cli deploy-test.php | tee result.txt
grep -q ' 0 failed' result.txt
