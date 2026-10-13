<#
.SYNOPSIS
    Copies the portal source code and deployment scripts to the Ubuntu VM.
#>

param (
    [string]$VmIp = "172.30.54.204",
    [string]$VmUser = "gd"
)

Write-Host "================================================================" -ForegroundColor Cyan
Write-Host "  UPLOADING PORTAL TO UBUNTU VM ($VmIp)" -ForegroundColor Cyan
Write-Host "================================================================" -ForegroundColor Cyan

$zipPath = "$env:TEMP\portal-bundle.zip"
if (Test-Path $zipPath) { Remove-Item $zipPath -Force }

Write-Host "[1/3] Creating source archive..." -ForegroundColor Yellow
$excludePatterns = @("*.iso", "*.git*", "*.zip", "ISO", ".system_generated")

# Use tar to create archive cleanly
tar --exclude='ISO' --exclude='.git' --exclude='node_modules' -czf "$env:TEMP\portal-bundle.tar.gz" -C "." .

Write-Host "[2/3] Uploading portal-bundle.tar.gz to VM via SCP..." -ForegroundColor Yellow
scp -o StrictHostKeyChecking=no "$env:TEMP\portal-bundle.tar.gz" "${VmUser}@${VmIp}:~/portal-bundle.tar.gz"

Write-Host "[3/3] Uploading setup script..." -ForegroundColor Yellow
scp -o StrictHostKeyChecking=no "scripts/setup-portal-ubuntu.sh" "${VmUser}@${VmIp}:~/setup-portal-ubuntu.sh"

Write-Host "`n================================================================" -ForegroundColor Green
Write-Host "  UPLOAD COMPLETE!" -ForegroundColor Green
Write-Host "  Now log in to your Ubuntu VM and run:" -ForegroundColor White
Write-Host "    ssh ${VmUser}@${VmIp}" -ForegroundColor Cyan
Write-Host "    mkdir -p portal && tar -xzf portal-bundle.tar.gz -C portal" -ForegroundColor Cyan
Write-Host "    cd portal && chmod +x scripts/setup-portal-ubuntu.sh" -ForegroundColor Cyan
Write-Host "    ./scripts/setup-portal-ubuntu.sh" -ForegroundColor Cyan
Write-Host "================================================================" -ForegroundColor Green
