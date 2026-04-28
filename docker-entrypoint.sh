#!/bin/bash
set -e

# Map Railway's MySQL service variables to Laravel's DB_ variables
# (Railway MySQL plugin exports MYSQLHOST, MYSQLUSER, etc.)
if [ -z "$DB_HOST" ] && [ -n "$MYSQLHOST" ]; then
    export DB_HOST="$MYSQLHOST"
    export DB_PORT="${MYSQLPORT:-3306}"
    export DB_DATABASE="$MYSQLDATABASE"
    export DB_USERNAME="$MYSQLUSER"
    export DB_PASSWORD="$MYSQLPASSWORD"
fi

# Also accept MYSQL_URL -> DATABASE_URL if only the former is set
if [ -z "$DATABASE_URL" ] && [ -n "$MYSQL_URL" ]; then
    export DATABASE_URL="$MYSQL_URL"
fi

# Remap Apache port to Railway's $PORT if not 80
if [ -n "$PORT" ] && [ "$PORT" != "80" ]; then
    sed -i "s/Listen 80/Listen $PORT/" /etc/apache2/ports.conf
    sed -i "s/*:80/*:$PORT/" /etc/apache2/sites-available/000-default.conf
fi

php artisan storage:link --force

# Wait up to 30 s for the database before running migrations
echo "Waiting for database..."
for i in $(seq 1 15); do
    php artisan migrate:status > /dev/null 2>&1 && break
    echo "  attempt $i/15 — retrying in 2 s"
    sleep 2
done

php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec apache2-foreground
