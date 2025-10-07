# Dockerfile for Laravel on Render (PHP built-in server)

FROM php:8.2-cli AS app
WORKDIR /app

# Install system dependencies and PHP extensions required by the app
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
       git unzip libpq-dev libzip-dev \
       libjpeg62-turbo-dev libpng-dev libwebp-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql zip gd exif sockets \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy application source first so autoloaded files exist
COPY . /app

# Install PHP dependencies (disable scripts during build)
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-scripts

# Ensure writable directories
RUN chmod -R 777 storage bootstrap/cache || true

# Entrypoint
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Render provides PORT; default locally
ENV PORT=8000
EXPOSE 8000

ENTRYPOINT ["/entrypoint.sh"]
