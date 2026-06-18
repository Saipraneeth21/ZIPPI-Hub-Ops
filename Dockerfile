# syntax=docker/dockerfile:1

# ---------- Stage 1: build front-end assets with Vite ----------
FROM node:20-slim AS assets
WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm install
COPY resources/ resources/
COPY vite.config.js ./
RUN npm run build

# ---------- Stage 2: PHP application runtime ----------
FROM php:8.3-cli AS app

# System packages + PHP extensions (pdo_pgsql for Render's Postgres).
RUN apt-get update && apt-get install -y --no-install-recommends git unzip \
 && rm -rf /var/lib/apt/lists/*
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions \
 && install-php-extensions pdo_pgsql zip bcmath intl gd exif

# Composer (copied from the official image).
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Install PHP dependencies first for better layer caching.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

# Copy the application code, then the compiled assets from stage 1.
COPY . .
COPY --from=assets /app/public/build ./public/build

# Generate the optimized autoloader (artisan scripts run later, at boot,
# once the environment variables are available).
RUN composer dump-autoload --optimize --no-dev --no-scripts \
 && chmod -R 775 storage bootstrap/cache

# Container start script.
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 10000
CMD ["/usr/local/bin/entrypoint.sh"]
