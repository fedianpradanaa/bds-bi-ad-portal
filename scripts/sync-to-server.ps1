param (
    [string]$ServerIp = "10.111.167.30",
    [string]$ServerUser = "gd",
    [string]$RemoteDir = "/opt/ad-portal",
    [switch]$Watch,
    [switch]$SetupKey
)

function Setup-PasswordlessSsh {
    Write-Host "`n[SSH KEY SETUP] Configuring passwordless SSH login to $ServerUser@$ServerIp..." -ForegroundColor Cyan
    
    $pubKeyPath = "$env:USERPROFILE\.ssh\id_ed25519.pub"
    if (-not (Test-Path $pubKeyPath)) {
        $pubKeyPath = "$env:USERPROFILE\.ssh\id_rsa.pub"
    }
    
    if (-not (Test-Path $pubKeyPath)) {
        Write-Host "Generating new ED25519 SSH key..." -ForegroundColor Yellow
        ssh-keygen -t ed25519 -N '""' -f "$env:USERPROFILE\.ssh\id_ed25519"
        $pubKeyPath = "$env:USERPROFILE\.ssh\id_ed25519.pub"
    }
    
    $pubKeyContent = (Get-Content $pubKeyPath -Raw).Trim()
    $remoteCommand = "mkdir -p ~/.ssh; chmod 700 ~/.ssh; echo '" + $pubKeyContent + "' >> ~/.ssh/authorized_keys; sort -u ~/.ssh/authorized_keys -o ~/.ssh/authorized_keys; chmod 600 ~/.ssh/authorized_keys"
    
    Write-Host "Copying public key to server (Enter password once)..." -ForegroundColor Yellow
    ssh -o StrictHostKeyChecking=no "$ServerUser@$ServerIp" $remoteCommand
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "Passwordless SSH key configured successfully!" -ForegroundColor Green
    } else {
        Write-Host "Password authentication will continue to be used." -ForegroundColor Yellow
    }
}

function Sync-CodeToServer {
    $timestamp = Get-Date -Format "HH:mm:ss"
    Write-Host "`n[$timestamp] Syncing project updates to http://$ServerIp ..." -ForegroundColor Cyan
    
    $projectRoot = Split-Path -Parent $PSScriptRoot
    if (-not (Test-Path "$projectRoot\artisan")) {
        $projectRoot = (Get-Location).Path
    }
    Set-Location $projectRoot
    [System.Environment]::CurrentDirectory = $projectRoot
    
    $tempArchive = "$env:TEMP\portal-sync-update.tar.gz"
    if (Test-Path $tempArchive) { Remove-Item $tempArchive -Force }
    if (Test-Path "$projectRoot\public\hot") { 
        Remove-Item "$projectRoot\public\hot" -Force 
        Write-Host "Removed temporary public\hot file before packaging." -ForegroundColor Yellow
    }
    
    if (-not (Test-Path "$projectRoot\public\build\manifest.json")) {
        Write-Host "Building production frontend assets..." -ForegroundColor Yellow
        npm run build
        Set-Location $projectRoot
        [System.Environment]::CurrentDirectory = $projectRoot
    }
    
    tar -C $projectRoot --exclude="ISO" --exclude=".git" --exclude="node_modules" --exclude="public/hot" --exclude="*.sqlite*" --exclude="database/*.sqlite*" --exclude="storage/logs/*" --exclude="storage/framework/sessions/*" --exclude="storage/framework/views/*" --exclude="storage/framework/cache/*" -czf $tempArchive app bootstrap/app.php bootstrap/providers.php artisan config database resources routes public scripts composer.json composer.lock docker docker-compose.yml mhs-reports "Report Data Strukture"
        
    if ($LASTEXITCODE -ne 0 -or -not (Test-Path $tempArchive)) {
        Write-Host "[ERROR] Failed to generate update package with tar." -ForegroundColor Red
        return
    }
    
    scp -q -o StrictHostKeyChecking=no $tempArchive "$ServerUser@$ServerIp`:/tmp/portal-sync-update.tar.gz"
    
    if ($LASTEXITCODE -ne 0) {
        Write-Host "Failed to connect to server $ServerIp. Ensure SSH is reachable." -ForegroundColor Red
        return
    }
    
    $remoteScript = "tar --no-overwrite-dir --no-same-owner --no-same-permissions -m -xzf /tmp/portal-sync-update.tar.gz -C $RemoteDir && rm -f /tmp/portal-sync-update.tar.gz && docker compose -f $RemoteDir/docker-compose.yml up -d && docker compose -f $RemoteDir/docker-compose.yml exec -T app chmod -R 777 /var/www/html/database /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/mhs-reports && docker compose -f $RemoteDir/docker-compose.yml exec -T app php artisan migrate --force && docker compose -f $RemoteDir/docker-compose.yml exec -T app php artisan ad:sync-users && docker compose -f $RemoteDir/docker-compose.yml exec -T app php artisan bds:sync-reports && docker compose -f $RemoteDir/docker-compose.yml exec -T app php artisan optimize:clear && docker compose -f $RemoteDir/docker-compose.yml restart app nginx"
    
    ssh -o StrictHostKeyChecking=no "$ServerUser@$ServerIp" $remoteScript
    
    Write-Host "[$timestamp] SYNC COMPLETE! Changes are live on http://$ServerIp" -ForegroundColor Green
}

Write-Host "================================================================" -ForegroundColor Cyan
Write-Host "  BDS ACCESS PORTAL - AUTO CODE SYNC TO SERVER ($ServerIp)" -ForegroundColor Cyan
Write-Host "================================================================" -ForegroundColor Cyan

if ($SetupKey) {
    Setup-PasswordlessSsh
    return
}

if ($Watch) {
    Write-Host "Auto-Watch Mode ACTIVE: Monitoring project files for changes..." -ForegroundColor Yellow
    Write-Host "   (Press Ctrl + C in this terminal to stop watching)`n" -ForegroundColor White
    
    Sync-CodeToServer
    
    $projectPath = (Get-Location).Path
    $watcher = New-Object System.IO.FileSystemWatcher
    $watcher.Path = $projectPath
    $watcher.IncludeSubdirectories = $true
    $watcher.EnableRaisingEvents = $true
    $watcher.NotifyFilter = [System.IO.NotifyFilters]::FileName -bor [System.IO.NotifyFilters]::LastWrite
    
    $lastSyncTime = [DateTime]::MinValue
    $debounceSeconds = 2
    
    while ($true) {
        $result = $watcher.WaitForChanged([System.IO.WatcherChangeTypes]::Created -bor [System.IO.WatcherChangeTypes]::Changed -bor [System.IO.WatcherChangeTypes]::Deleted, 1000)
        
        if ($result.TimedOut -eq $false) {
            $changedFile = $result.Name
            if ($changedFile -notmatch "\.git|storage\\logs|\.tmp|portal-sync") {
                $now = Get-Date
                if (($now - $lastSyncTime).TotalSeconds -ge $debounceSeconds) {
                    $lastSyncTime = $now
                    Write-Host "Detected change in: $changedFile" -ForegroundColor Yellow
                    Sync-CodeToServer
                }
            }
        }
    }
} else {
    Sync-CodeToServer
}
