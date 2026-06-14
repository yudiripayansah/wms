#!/bin/bash
set -e

echo "=== WMS Deployment Script ==="

# Run database migrations
echo "[1/5] Running migrations..."
php artisan migrate --force

# Seed demo data
echo "[2/5] Seeding demo users..."
php artisan db:seed --class=UserSeeder --force

# Create storage symlink
echo "[3/5] Creating storage symlink..."
php artisan storage:link --force

# Cache config and routes for production
echo "[4/5] Caching config, routes, views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Publish Filament assets
echo "[5/5] Publishing Filament assets..."
php artisan filament:assets

echo ""
echo "=== Deployment complete! ==="
