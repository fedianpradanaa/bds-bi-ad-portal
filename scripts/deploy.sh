#!/usr/bin/env bash
# ==============================================================================
# Deployment Script on Target Server: BDS-WEB-GD01 (IP: 10.111.167.30)
# Air-Gapped / Offline Environment
# ==============================================================================

set -e

echo "==> [1/4] Loading Docker Images into local daemon..."
if [ -f "ad-portal-app.tar" ]; then
    docker load -i ad-portal-app.tar
fi

if [ -f "nginx-alpine.tar" ]; then
    docker load -i nginx-alpine.tar
fi

if [ -f "mysql-8.0.tar" ]; then
    docker load -i mysql-8.0.tar
fi

echo "==> [2/4] Verifying Environment Configuration (.env)..."
if [ ! -f ".env" ]; then
    echo "WARNING: .env not found. Copying from template..."
    cp .env.example .env
fi

# Ensure storage directories exist with proper permissions
mkdir -p storage/framework/{sessions,views,cache} storage/logs bootstrap/cache
chmod -R 775 storage bootstrap/cache || true

echo "==> [3/4] Starting Docker Containers..."
docker compose up -d

echo "==> [4/4] Running Database Migrations & Optimizations..."
docker compose exec -T app php artisan migrate --force
docker compose exec -T app php artisan config:cache
docker compose exec -T app php artisan route:cache
docker compose exec -T app php artisan view:cache

echo ""
echo "=============================================================================="
echo " DEPLOYMENT SUCCESSFUL!"
echo " Portal URL: http://10.111.167.30 (or https://10.111.167.30)"
echo " Admin Panel: http://10.111.167.30/admin"
echo "=============================================================================="
