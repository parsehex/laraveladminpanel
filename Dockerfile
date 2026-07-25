# -----------------------------
# Stage 1 - Install PHP packages
# -----------------------------
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader

COPY . .

RUN composer dump-autoload --optimize

# -----------------------------
# Stage 2 - Build frontend
# -----------------------------
FROM node:22-alpine AS frontend

WORKDIR /app

COPY package*.json ./

RUN npm ci

COPY . .

RUN npm run build

# -----------------------------
# Stage 3 - Production Image
# -----------------------------
FROM php:8.3-fpm-bookworm

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
        pdo_pgsql \
        zip \
        gd \
        opcache \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www

COPY --from=vendor /app /var/www
COPY --from=frontend /app/public/build /var/www/public/build

COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/start.sh /start.sh

RUN chmod +x /start.sh

RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE 10000

CMD ["/start.sh"]