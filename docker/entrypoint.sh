#!/usr/bin/env sh
set -e

# Ensure storage links (ignore if already exists)
php artisan storage:link || true

# Cache configs/routes/views for performance (ignore errors during first boot)
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Start PHP built-in server for Render
exec php -d variables_order=EGPCS -S 0.0.0.0:${PORT:-8000} -t public public/index.php
