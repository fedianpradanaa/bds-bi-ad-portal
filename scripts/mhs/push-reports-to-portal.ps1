# ==============================================================================
# SCRIPT PUSH DATA LAPORAN DARI SERVER MHS KE WEB PORTAL BDS
# Jalankan di Server MHS Windows (10.111.166.11)
# ==============================================================================

param (
    [string]$PortalUrl = "http://10.111.167.30",
    [string]$ApiKey = "BdsMhsSecret2026!",
    [string]$MhsSourceDir = "F:\Eckelmann\Compass Logistics MHS\Configuration\Reports",
    [switch]$ForceAll
)

# Tentukan folder tempat script ini berada
$scriptDir = if ($PSScriptRoot) { $PSScriptRoot } else { Split-Path -Parent $MyInvocation.MyCommand.Path }
if (-not $scriptDir) { $scriptDir = (Get-Location).Path }

$logFile = Join-Path $scriptDir "mhs_sync.log"
$stateFile = Join-Path $scriptDir "mhs_synced_state.json"

# Rotasi log jika ukuran > 5 MB agar tidak membebani storage
if (Test-Path $logFile) {
    try {
        if ((Get-Item $logFile).Length -gt 5MB) {
            $backupLog = Join-Path $scriptDir "mhs_sync.log.bak"
            if (Test-Path $backupLog) { Remove-Item $backupLog -Force -ErrorAction SilentlyContinue }
            Rename-Item -Path $logFile -NewName "mhs_sync.log.bak" -Force -ErrorAction SilentlyContinue
        }
    } catch {}
}

function Write-Log {
    param (
        [string]$Message,
        [string]$Level = "INFO",
        [System.ConsoleColor]$Color = [System.ConsoleColor]::White
    )
    $ts = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $logLine = "[$ts] [$Level] $Message"

    # Tampilkan ke layar (console)
    Write-Host $logLine -ForegroundColor $Color

    # Tulis ke file log di folder yang sama dengan script
    try {
        Add-Content -Path $logFile -Value $logLine -Encoding utf8 -ErrorAction SilentlyContinue
    } catch {}
}

Write-Log "Memulai sinkronisasi laporan BDS MHS -> $PortalUrl ..." "INFO" Cyan

# 1. Cari lokasi folder MHS jika path default tidak ditemukan
if ((-not (Test-Path $MhsSourceDir)) -or [string]::IsNullOrWhiteSpace($MhsSourceDir)) {
    $possibleDirs = @(
        "F:\Eckelmann\Compass Logistics MHS\Configuration\Reports",
        "C:\Eckelmann\Compass Logistics MHS\Configuration\Reports",
        "D:\Eckelmann\Compass Logistics MHS\Configuration\Reports",
        (Join-Path $scriptDir "..\..\mhs-reports"),
        (Join-Path $scriptDir "..\..\Report Data Strukture"),
        (Join-Path $scriptDir "..\..\BDS Output Report"),
        "C:\Reports",
        "D:\Reports"
    )

    foreach ($dir in $possibleDirs) {
        if (Test-Path $dir) {
            $MhsSourceDir = $dir
            Write-Log "Menggunakan folder MHS terdeteksi: $MhsSourceDir" "INFO" Cyan
            break
        }
    }
}

if (-not (Test-Path $MhsSourceDir)) {
    Write-Log "Folder sumber laporan MHS tidak ditemukan: $MhsSourceDir" "ERROR" Red
    Write-Log "Silakan jalankan script dengan parameter -MhsSourceDir 'F:\Path\Ke\Reports'" "WARN" Yellow
    exit 1
}

# Jika terdapat subfolder EventRecord, gunakan subfolder tersebut
if (Test-Path "$MhsSourceDir\EventRecord") {
    $EventRecordBase = "$MhsSourceDir\EventRecord"
} else {
    $EventRecordBase = $MhsSourceDir
}

Write-Log "Menggunakan direktori sumber: $EventRecordBase" "INFO" Gray

# 2. State file untuk delta sync (hanya kirim file baru/berubah)
$syncedState = @{}
if ((Test-Path $stateFile) -and (-not $ForceAll)) {
    try {
        $jsonContent = Get-Content $stateFile -Raw | ConvertFrom-Json
        foreach ($prop in $jsonContent.PSObject.Properties) {
            $syncedState[$prop.Name] = $prop.Value
        }
    } catch {
        $syncedState = @{}
    }
}

# 3. Cari tanggal yang aktif
$dateFolders = Get-ChildItem -Path $EventRecordBase -Directory | Where-Object {
    $_.Name -match '^\d{4}-\d{2}-\d{2}$'
} | Sort-Object Name

Write-Log "Ditemukan $($dateFolders.Count) folder tanggal operasional di MHS." "INFO" Cyan

$filesToPush = @()

foreach ($df in $dateFolders) {
    $eventDirs = Get-ChildItem -Path $df.FullName -Directory | Where-Object { $_.Name -match '_Event\d+$' }
    foreach ($ed in $eventDirs) {
        $csvFiles = Get-ChildItem -Path $ed.FullName -Filter "*.csv" -File
        foreach ($csv in $csvFiles) {
            $relPath = "$($df.Name)/$($ed.Name)/$($csv.Name)"
            $lastWrite = $csv.LastWriteTimeUtc.ToString("o")

            # Cek apakah file sudah pernah dikirim dengan timestamp sama
            if (-not $ForceAll -and $syncedState.ContainsKey($relPath) -and $syncedState[$relPath] -eq $lastWrite) {
                continue
            }

            try {
                $content = [System.IO.File]::ReadAllText($csv.FullName, [System.Text.Encoding]::UTF8)
                $filesToPush += @{
                    relative_path = $relPath
                    content = $content
                    last_write = $lastWrite
                }
            } catch {
                Write-Log "Gagal membaca $($csv.FullName) : $_" "WARN" Yellow
            }
        }
    }
}

# 3b. Cari file Output Report (BR_Reports JSON)
$brReportsBase = $null
$possibleBrDirs = @(
    (Join-Path $MhsSourceDir "BR_Reports"),
    "F:\Eckelmann\Compass Logistics MHS\Configuration\Reports\BR_Reports"
)
foreach ($bDir in $possibleBrDirs) {
    if (Test-Path $bDir) {
        $brReportsBase = $bDir
        break
    }
}

if ($brReportsBase) {
    Write-Log "Memindai BR_Reports (Output Reports) di: $brReportsBase" "INFO" Cyan
    $jsonFiles = Get-ChildItem -Path $brReportsBase -Filter "*.json" -Recurse -File
    foreach ($jsonFile in $jsonFiles) {
        $subRel = $jsonFile.FullName.Substring($brReportsBase.Length).TrimStart('\', '/')
        $relPath = "BR_Reports/" + ($subRel -replace '\\', '/')
        $lastWrite = $jsonFile.LastWriteTimeUtc.ToString("o")

        if (-not $ForceAll -and $syncedState.ContainsKey($relPath) -and $syncedState[$relPath] -eq $lastWrite) {
            continue
        }

        try {
            $content = [System.IO.File]::ReadAllText($jsonFile.FullName, [System.Text.Encoding]::UTF8)
            $filesToPush += @{
                relative_path = $relPath
                content = $content
                last_write = $lastWrite
            }
        } catch {
            Write-Log "Gagal membaca $($jsonFile.FullName) : $_" "WARN" Yellow
        }
    }
}

if ($filesToPush.Count -eq 0) {
    Write-Log "Tidak ada file laporan (CSV/JSON) baru atau perubahan terdeteksi. Server MHS up-to-date." "SUCCESS" Green
    exit 0
}

Write-Log "Ditemukan $($filesToPush.Count) file laporan baru/berubah. Mengirim ke Web Portal..." "INFO" Yellow

# 4. Kirim ke REST API Web Portal
$targetEndpoint = "$PortalUrl/api/bds/upload-reports"
$headers = @{
    "X-BDS-API-KEY" = $ApiKey
    "Accept" = "application/json"
}

$payload = @{
    files = @($filesToPush | ForEach-Object {
        @{
            relative_path = $_.relative_path
            content = $_.content
        }
    })
}

$jsonBody = $payload | ConvertTo-Json -Depth 5

try {
    $response = Invoke-RestMethod -Uri $targetEndpoint `
        -Method Post `
        -Body $jsonBody `
        -ContentType "application/json; charset=utf-8" `
        -Headers $headers `
        -TimeoutSec 120

    if ($response.success) {
        Write-Log "SUKSES! $($response.message)" "SUCCESS" Green
        
        # Simpan state berhasil
        foreach ($f in $filesToPush) {
            $syncedState[$f.relative_path] = $f.last_write
        }
        $syncedState | ConvertTo-Json | Set-Content -Path $stateFile -Encoding utf8
    } else {
        Write-Log "Portal merespons dengan gagal: $($response.message)" "ERROR" Red
    }
} catch {
    Write-Log "ERROR KONEKSI HTTP: $_" "ERROR" Red
    Write-Log "Pastikan server portal $PortalUrl dapat dijangkau dari server MHS (cek firewall/kabel)." "WARN" Yellow
}
