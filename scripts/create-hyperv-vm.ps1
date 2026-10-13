<#
.SYNOPSIS
    Automates the creation and initial boot configuration of the BDS-WEB-GD01 Hyper-V Virtual Machine.

.DESCRIPTION
    Creates a Generation 2 VM configured for Ubuntu 22.04 Server with:
    - Name: BDS-WEB-GD01
    - Location: D:\VM\BDS-WEB-GD01
    - Virtual Disk: 60 GB Dynamic VHDX
    - RAM: 4 GB (Dynamic Memory: 2 GB - 8 GB)
    - CPU: 2 vCPUs
    - Switch: Default Switch (Internet & NAT connectivity)
    - Secure Boot: MicrosoftUEFICertificateAuthority (Linux Compatible)
    - ISO Attached: ubuntu-22.04.5-live-server-amd64.iso
#>

param (
    [string]$VmName = "BDS-WEB-GD01",
    [string]$VmPath = "D:\VM\BDS-WEB-GD01",
    [string]$IsoPath = "C:\Users\Administrator\Documents\Porto\WebPortal-Account Approvemnt\ISO\ubuntu-22.04.5-live-server-amd64.iso",
    [int64]$DiskSizeBytes = 64424509440, # 60 GB
    [int64]$MemoryStartupBytes = 2147483648, # 2 GB Startup
    [int]$ProcessorCount = 2,
    [string]$SwitchName = "Default Switch"
)

Write-Host "================================================================" -ForegroundColor Cyan
Write-Host "  HYPER-V VM PROVISIONING: $VmName" -ForegroundColor Cyan
Write-Host "================================================================" -ForegroundColor Cyan

# 1. Check if VM already exists
$existingVm = Get-VM -Name $VmName -ErrorAction SilentlyContinue
if ($existingVm) {
    Write-Host "[!] VM '$VmName' already exists. State: $($existingVm.State)" -ForegroundColor Yellow
    return
}

# 2. Check ISO path
if (-not (Test-Path $IsoPath)) {
    Write-Error "ISO file not found at: $IsoPath"
    return
}
Write-Host "[1/6] Verified ISO at: $IsoPath" -ForegroundColor Green

# 3. Create Storage Directory & VHDX
$vhdxDir = Join-Path $VmPath "Virtual Hard Disks"
if (-not (Test-Path $vhdxDir)) {
    New-Item -ItemType Directory -Path $vhdxDir -Force | Out-Null
}
$vhdxPath = Join-Path $vhdxDir "$VmName.vhdx"

Write-Host "[2/6] Creating 60GB Dynamic VHDX at: $vhdxPath" -ForegroundColor Yellow
New-VHD -Path $vhdxPath -SizeBytes $DiskSizeBytes -Dynamic | Out-Null

# 4. Create Virtual Machine (Generation 2)
Write-Host "[3/6] Creating Generation 2 Virtual Machine..." -ForegroundColor Yellow
$vm = New-VM -Name $VmName `
             -Path $VmPath `
             -Generation 2 `
             -MemoryStartupBytes $MemoryStartupBytes `
             -VHDPath $vhdxPath `
             -SwitchName $SwitchName

# 5. Configure Hardware (Processors, Memory, Secure Boot)
Write-Host "[4/6] Configuring vCPUs, Dynamic Memory & SecureBoot for Linux..." -ForegroundColor Yellow
Set-VMProcessor -VMName $VmName -Count $ProcessorCount
Set-VMMemory -VMName $VmName -DynamicMemoryEnabled $true -StartupBytes $MemoryStartupBytes -MinimumBytes 1073741824 -MaximumBytes 4294967296

# Linux SecureBoot template
Set-VMFirmware -VMName $VmName -EnableSecureBoot On -SecureBootTemplate "MicrosoftUEFICertificateAuthority"

# 6. Add DVD Drive with Ubuntu ISO & Set Boot Order
Write-Host "[5/6] Attaching Ubuntu ISO and configuring boot order..." -ForegroundColor Yellow
$dvd = Add-VMDvdDrive -VMName $VmName -Path $IsoPath
$hdd = Get-VMHardDiskDrive -VMName $VmName
$net = Get-VMNetworkAdapter -VMName $VmName
Set-VMFirmware -VMName $VmName -BootOrder $dvd, $hdd, $net

Write-Host "[6/6] Starting Virtual Machine '$VmName'..." -ForegroundColor Green
Start-VM -Name $VmName

Write-Host "`n================================================================" -ForegroundColor Green
Write-Host "  VM '$VmName' CREATED & STARTED SUCCESSFULLY!" -ForegroundColor Green
Write-Host "  To connect to the VM Console, run:" -ForegroundColor White
Write-Host "    vmconnect.exe localhost $VmName" -ForegroundColor Cyan
Write-Host "================================================================" -ForegroundColor Green
