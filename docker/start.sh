#!/bin/sh

set -e

echo "Laravel starting..."

mkdir -p storage/framework/cache
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/views
mkdir -p storage/framework/sessions
mkdir -p storage/logs
mkdir -p bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache

chmod -R 775 storage bootstrap/cache

php artisan migrate

php artisan package:discover --ansi

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec /usr/bin/supervisord