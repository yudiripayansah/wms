#!/bin/bash
set -e

# Railway injects $PORT — remap Apache if it differs from 80
if [ -n "$PORT" ] && [ "$PORT" != "80" ]; then
    sed -i "s/Listen 80/Listen $PORT/" /etc/apache2/ports.conf
    sed -i "s/*:80/*:$PORT/" /etc/apache2/sites-available/000-default.conf
fi

php artisan storage:link --force
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec apache2-foreground
