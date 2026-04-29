#!/usr/bin/env bash
set -euo pipefail

PORT_VALUE=${PORT:-10000}

# Render injects DATABASE_URL for Postgres when using Blueprints.
# Laravel supports DATABASE_URL natively; ensure .env exists for artisan usage.
if [ ! -f "/var/www/html/.env" ]; then
  cp /var/www/html/.env.example /var/www/html/.env
fi

# Nginx config: set runtime port
sed "s/__PORT__/${PORT_VALUE}/g" /etc/nginx/http.d/default.conf.template > /etc/nginx/http.d/default.conf

# Ensure storage link exists (ignore if already exists)
php artisan storage:link || true

# Cache config/routes (best effort; don't hard-fail deployment)
php artisan config:cache || true
php artisan route:cache || true

# Run migrations automatically if enabled
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
  php artisan migrate --force

  if [ "${RUN_SEEDERS:-false}" = "true" ]; then
    php artisan db:seed --force
  fi
fi

exec /usr/bin/supervisord -c /etc/supervisord.conf
