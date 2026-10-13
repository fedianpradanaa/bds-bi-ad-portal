# ==============================================================================
# Script Export Docker Bundle untuk Server Offline / Air-Gapped (BDS-WEB-GD01)
# ==============================================================================

Write-Host "==> [1/5] Mempersiapkan Bundle Rilis..." -ForegroundColor Cyan
$bundleDir = "release-bundle"
if (!(Test-Path $bundleDir)) {
    New-Item -ItemType Directory -Path $bundleDir | Out-Null
}

Write-Host "==> [2/5] Membangun Docker Image Aplikasi (ad-portal-app:latest)..." -ForegroundColor Cyan
docker build -t ad-portal-app:latest -f docker/php/Dockerfile .

Write-Host "==> [3/5] Mengunduh Base Images (Nginx & MySQL)..." -ForegroundColor Cyan
docker pull nginx:alpine
docker pull mysql:8.0

Write-Host "==> [4/5] Meng-export Docker Images ke format file arsip (.tar)..." -ForegroundColor Cyan
Write-Host "    - Menyimpan ad-portal-app.tar..."
docker save ad-portal-app:latest -o "$bundleDir\ad-portal-app.tar"

Write-Host "    - Menyimpan nginx-alpine.tar..."
docker save nginx:alpine -o "$bundleDir\nginx-alpine.tar"

Write-Host "    - Menyimpan mysql-8.0.tar..."
docker save mysql:8.0 -o "$bundleDir\mysql-8.0.tar"

Write-Host "==> [5/5] Menyalin konfigurasi deployment ke folder release-bundle..." -ForegroundColor Cyan
Copy-Item "docker-compose.yml" -Destination "$bundleDir\docker-compose.yml" -Force
Copy-Item ".env.example" -Destination "$bundleDir\.env" -Force
Copy-Item "scripts\deploy.sh" -Destination "$bundleDir\deploy.sh" -Force

Write-Host ""
Write-Host "==============================================================================" -ForegroundColor Green
Write-Host " SUCCESS! File rilis siap di folder: $bundleDir" -ForegroundColor Green
Write-Host "==============================================================================" -ForegroundColor Green
Write-Host "Langkah pengiriman ke server target BDS-WEB-GD01 (10.111.167.30):"
Write-Host " 1. Copy seluruh isi folder '$bundleDir' ke server BDS-WEB-GD01 (via SCP/WinSCP)."
Write-Host " 2. Di server BDS-WEB-GD01, beri izin eksekusi: chmod +x deploy.sh"
Write-Host " 3. Jalankan script deploy: ./deploy.sh"
Write-Host "=============================================================================="
