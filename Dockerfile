# Multi-stage Dockerfile for Laravel on Render (no external web server; PHP built-in server)

# 1) Composer stage
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --optimize-autoloader --no-scripts

# 2) Runtime stage
FROM php:8.2-cli
WORKDIR /app

# Install system dependencies and PHP extensions
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev unzip \
    && docker-php-ext-install pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# Copy vendor from composer stage
COPY --from=vendor /app/vendor /app/vendor

# Copy application source
COPY . /app

# Ensure writable directories
RUN chmod -R 777 storage bootstrap/cache || true

# Entrypoint
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Render provides PORT; default locally
ENV PORT=8000
EXPOSE 8000

ENTRYPOINT ["/entrypoint.sh"]
