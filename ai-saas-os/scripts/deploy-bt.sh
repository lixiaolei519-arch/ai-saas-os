#!/usr/bin/env bash
set -euo pipefail

# Manual Baota deployment draft for v1.5.0.
# Review variables before running on a production server.

PROJECT_DIR="${PROJECT_DIR:-/www/wwwroot/ai-saas-os}"
BRANCH="${BRANCH:-main}"
PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"
RUN_SEED="${RUN_SEED:-0}"

cd "$PROJECT_DIR"

echo "[INFO] pulling $BRANCH"
git fetch origin
git checkout "$BRANCH"
git pull origin "$BRANCH"

echo "[INFO] installing composer dependencies"
"$COMPOSER_BIN" install --no-dev --optimize-autoloader

echo "[INFO] running migrations"
"$PHP_BIN" artisan migrate --force

if [ "$RUN_SEED" = "1" ]; then
  echo "[INFO] running production seed"
  "$PHP_BIN" artisan db:seed --force
fi

echo "[INFO] rebuilding caches"
"$PHP_BIN" artisan optimize:clear
"$PHP_BIN" artisan config:cache
"$PHP_BIN" artisan route:cache
"$PHP_BIN" artisan view:cache
"$PHP_BIN" artisan queue:restart

echo "[INFO] running production checks"
"$PHP_BIN" artisan app:production-check
"$PHP_BIN" artisan app:smoke-test

echo "[OK] Baota deployment draft completed"
