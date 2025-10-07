#!/usr/bin/env sh
set -e

# Ensure required directories exist
mkdir -p public storage/app/public bootstrap/cache || true

# Create storage symlink if missing (ignore errors in containerized envs)
if [ ! -L public/storage ]; then
  php artisan storage:link || true
fi

# Cache config and views; avoid route:cache due to duplicate route names
php artisan config:cache || true
php artisan route:clear || true
php artisan view:cache || true

# Start PHP built-in server for Render
exec php -d variables_order=EGPCS -S 0.0.0.0:${PORT:-8000} -t public public/index.php
