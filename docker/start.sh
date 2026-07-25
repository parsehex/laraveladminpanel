#!/bin/sh

set -e

echo "Laravel starting..."

mkdir -p storage/framework/cache
mkdir -p storage/framework/views
mkdir -p storage/framework/sessions

chmod -R 775 storage
chmod -R 775 bootstrap/cache

php artisan config:cache

php artisan route:cache

php artisan view:cache

exec /usr/bin/supervisord