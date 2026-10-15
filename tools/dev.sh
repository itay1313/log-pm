#!/usr/bin/env bash
# Local WordPress via WordPress Playground (no Docker, no MySQL).
# Theme and plugin folders are mounted live, so edits show on refresh.
# ACF here is the free version: repeater/gallery/options fields only work on uPress with ACF Pro.
set -euo pipefail
cd "$(dirname "$0")/.."

# LOG's ArcGIS export (client data, git-ignored) is mounted only when present.
PRIVATE=()
if [ -f tools/fixtures/private/blue_lines_main.geojson ]; then
  PRIVATE=(--mount=./tools/fixtures/private/blue_lines_main.geojson:/wordpress/wp-content/mu-plugins/blue_lines_main.geojson)
fi

npx --yes @wp-playground/cli@latest server \
  --port=8881 \
  --blueprint=tools/playground.json \
  --mount=./wp-content/themes/log:/wordpress/wp-content/themes/log \
  --mount=./wp-content/plugins/log-core:/wordpress/wp-content/plugins/log-core \
  --mount=./tools/dev-mu/log-dev-tests.php:/wordpress/wp-content/mu-plugins/log-dev-tests.php \
  --mount=./tools/fixtures/instagram-sample.json:/wordpress/wp-content/mu-plugins/instagram-sample.json \
  ${PRIVATE[@]+"${PRIVATE[@]}"} \
  "$@"
