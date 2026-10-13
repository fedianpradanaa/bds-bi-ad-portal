#!/usr/bin/env bash
# ==============================================================================
# SCRIPT UPDATE OFFLINE / AIR-GAPPED PORTAL (TANPA KONEKSI INTERNET)
# ==============================================================================

set -e

echo "================================================================"
echo "  APPLYING OFFLINE UPDATE TO BDS ACCESS PORTAL"
echo "================================================================"

APP_DIR="/opt/ad-portal"

echo "[1/4] Syncing updated source files to ${APP_DIR}..."
sudo mkdir -p "${APP_DIR}"
sudo chown -R "$USER:$USER" "${APP_DIR}"

# Copy updated application code into /opt/ad-portal
rsync -av --exclude='.git' --exclude='ISO' --exclude='tests' ./ "${APP_DIR}/"
cd "${APP_DIR}"

# Ensure .env exists with correct settings
if [ ! -f ".env" ]; then
    cp .env.example .env
fi

# Configure Live LDAP & TLS in .env
sed -i "s|^APP_ENV=.*|APP_ENV=production|" .env 2>/dev/null || echo "APP_ENV=production" >> .env
sed -i "s|^APP_DEBUG=.*|APP_DEBUG=false|" .env 2>/dev/null || echo "APP_DEBUG=false" >> .env
sed -i "s|^APP_URL=.*|APP_URL=http://bds-web-gd01.cmc.mhe.local|" .env 2>/dev/null || echo "APP_URL=http://bds-web-gd01.cmc.mhe.local" >> .env
sed -i "s|^AD_MOCK_MODE=.*|AD_MOCK_MODE=false|" .env 2>/dev/null || echo "AD_MOCK_MODE=false" >> .env
sed -i "s|^LDAP_HOSTS=.*|LDAP_HOSTS=10.111.121.10,10.111.121.20|" .env 2>/dev/null || echo "LDAP_HOSTS=10.111.121.10,10.111.121.20" >> .env
sed -i "s|^LDAP_PORT=.*|LDAP_PORT=389|" .env 2>/dev/null || echo "LDAP_PORT=389" >> .env
sed -i "s|^LDAP_USERNAME=.*|LDAP_USERNAME=svc-ad-portal@cmc.mhe.local|" .env 2>/dev/null || echo "LDAP_USERNAME=svc-ad-portal@cmc.mhe.local" >> .env
sed -i "s|^LDAP_STARTTLS=.*|LDAP_STARTTLS=true|" .env 2>/dev/null || echo "LDAP_STARTTLS=true" >> .env
sed -i "s|^LDAP_ALLOW_INSECURE_PASSWORD_CHANGES=.*|LDAP_ALLOW_INSECURE_PASSWORD_CHANGES=true|" .env 2>/dev/null || echo "LDAP_ALLOW_INSECURE_PASSWORD_CHANGES=true" >> .env

# Configure OpenLDAP certificate ignore
echo "TLS_REQCERT never" | sudo tee -a /etc/ldap/ldap.conf > /dev/null 2>&1 || true

echo "[2/4] Setting folder and database permissions..."
mkdir -p storage/framework/{sessions,views,cache} storage/logs bootstrap/cache database
touch database/database.sqlite
sudo chmod -R 777 storage bootstrap/cache database

echo "[3/4] Restarting Docker container stack..."
sudo docker compose down --remove-orphans || true
sudo docker compose up -d

# Fix permissions inside container
sudo docker compose exec -T app chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database || true
sudo docker compose exec -T app chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database || true
sudo docker compose exec -T app sh -c "echo 'TLS_REQCERT never' >> /etc/openldap/ldap.conf 2>/dev/null || true; echo 'TLS_REQCERT never' >> /etc/ldap/ldap.conf 2>/dev/null || true"

echo "[4/4] Clearing application caches..."
sudo docker compose exec -T app php artisan optimize:clear

echo ""
echo "================================================================"
echo "  OFFLINE UPDATE COMPLETED SUCCESSFULLY!"
echo "  Web Portal URL: http://10.111.167.30"
echo "================================================================"
