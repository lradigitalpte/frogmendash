#!/usr/bin/env bash
set -euo pipefail

cd /app

# Ensure runtime-writable directories exist.
mkdir -p storage/framework/{cache,sessions,views} bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Ensure public storage symlink exists.
php artisan storage:link || true

# Run idempotent bootstrapping (migrations + first-install logic).
php scripts/railway-bootstrap.php

# Render nginx config with Railway-provided PORT.
export PORT="${PORT:-8080}"
envsubst '${PORT}' < /etc/nginx/templates/default.conf.template > /etc/nginx/conf.d/default.conf

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
