#!/usr/bin/env bash
# Local WordPress via WordPress Playground (no Docker, no MySQL).
# Theme and plugin folders are mounted live, so edits show on refresh.
# ACF here is the free version: repeater/gallery/options fields only work on uPress with ACF Pro.
set -euo pipefail
cd "$(dirname "$0")/.."

npx --yes @wp-playground/cli@latest server \
  --port=8881 \
  --blueprint=tools/playground.json \
  --mount=./wp-content/themes/log:/wordpress/wp-content/themes/log \
  --mount=./wp-content/plugins/log-core:/wordpress/wp-content/plugins/log-core \
  "$@"
