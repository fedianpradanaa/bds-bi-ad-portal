#!/usr/bin/env bash
set -e

echo "================================================================"
echo "  SETTING UP ACTIVE DIRECTORY APPROVAL PORTAL ON UBUNTU"
echo "================================================================"

# 1. Update and install prerequisites
echo "[1/5] Updating packages and installing prerequisites..."
sudo apt-get update -y
sudo apt-get install -y ca-certificates curl gnupg lsb-release unzip tar rsync

# 2. Install Docker & Docker Compose if not already installed
if ! command -v docker &> /dev/null; then
    echo "[2/5] Installing Docker Engine..."
    curl -fsSL https://get.docker.com -o get-docker.sh
    sudo sh get-docker.sh
    sudo usermod -aG docker "$USER"
    rm -f get-docker.sh
fi

echo "[3/5] Setting up Portal directory in /opt/ad-portal..."
sudo mkdir -p /opt/ad-portal
sudo chown -R "$USER:$USER" /opt/ad-portal

# Copy files from current directory to /opt/ad-portal
rsync -av --exclude='.git' --exclude='ISO' --exclude='tests' ./ /opt/ad-portal/
cd /opt/ad-portal

# Setup .env file
if [ ! -f ".env" ]; then
    cp .env.example .env
fi

# Ensure storage directories exist with write permissions
mkdir -p storage/framework/{sessions,views,cache} storage/logs bootstrap/cache
sudo chmod -R 777 storage bootstrap/cache

# 4. Build and run Docker containers
echo "[4/5] Building and starting Docker containers..."
sudo docker compose down --remove-orphans || true
sudo docker compose up -d --build

# Wait for MySQL to become ready
echo "Waiting for MySQL database container to initialize..."
sleep 15

# 5. Run database migrations & seeding
echo "[5/5] Running database migrations & seeding..."
sudo docker compose exec -T app php artisan migrate:fresh --seed --force
sudo docker compose exec -T app php artisan optimize:clear

echo ""
echo "================================================================"
echo "  PORTAL DEPLOYED SUCCESSFULLY!"
echo "  Web Portal URL: http://$(hostname -I | awk '{print $1}')"
echo "  Admin Login   : admin (Password: password)"
echo "  Supervisor    : supervisor (Password: password)"
echo "================================================================"
