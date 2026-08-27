# ==================================================
# Stage 1 - Install PHP Dependencies
# ==================================================
FROM composer:2 AS vendor

WORKDIR /app

# Copy the entire project
COPY . .

# Create .env for artisan during composer install
RUN cp .env.example .env && composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader \
    --no-scripts \
    && rm .env

# ==================================================
# Stage 2 - Build Frontend Assets
# ==================================================
FROM node:22-alpine AS frontend

WORKDIR /app

COPY package*.json ./

RUN npm install --no-audit --no-fund

COPY . .

RUN npm run build

# ==================================================
# Stage 3 - Production Image
# ==================================================
FROM php:8.4-fpm-bookworm

ENV APP_ENV=production

RUN apt-get update && apt-get install -y \
    nginx \
    supervisor \
    git \
    unzip \
    zip \
    curl \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        zip \
        gd \
        opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www

# Copy application
COPY --from=vendor /app /var/www

# Copy compiled frontend assets
COPY --from=frontend /app/public/build /var/www/public/build

# Copy Docker configuration
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/start.sh /start.sh

RUN chmod +x /start.sh

# Ensure writable directories exist
RUN mkdir -p \
    storage/framework/cache \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

RUN chown -R www-data:www-data storage bootstrap/cache

RUN chmod -R 775 storage bootstrap/cache

EXPOSE 10000

CMD ["/start.sh"]