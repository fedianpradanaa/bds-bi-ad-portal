<#
.SYNOPSIS
    Exports the configured BDS-WEB-GD01 Virtual Machine for deployment on the target host server.

.DESCRIPTION
    Stops the VM (if running) and exports the complete VM (VHDX, VM configuration, Snapshots)
    to a portable folder ready to be imported on the target Hyper-V server.
#>

param (
    [string]$VmName = "BDS-WEB-GD01",
    [string]$ExportPath = "D:\VM\Exports"
)

Write-Host "================================================================" -ForegroundColor Cyan
Write-Host "  EXPORTING HYPER-V VM: $VmName" -ForegroundColor Cyan
Write-Host "================================================================" -ForegroundColor Cyan

$vm = Get-VM -Name $VmName -ErrorAction Stop

if ($vm.State -eq 'Running') {
    Write-Host "[1/3] Gracefully shutting down '$VmName'..." -ForegroundColor Yellow
    Stop-VM -Name $VmName -Force
    Start-Sleep -Seconds 3
}

if (-not (Test-Path $ExportPath)) {
    New-Item -ItemType Directory -Path $ExportPath -Force | Out-Null
}

$destFolder = Join-Path $ExportPath $VmName
if (Test-Path $destFolder) {
    Write-Host "[!] Previous export exists at $destFolder, removing old export..." -ForegroundColor Yellow
    Remove-Item -Path $destFolder -Recurse -Force
}

Write-Host "[2/3] Exporting VM to: $ExportPath ..." -ForegroundColor Yellow
Export-VM -Name $VmName -Path $ExportPath

Write-Host "`n================================================================" -ForegroundColor Green
Write-Host "  EXPORT COMPLETED SUCCESSFULLY!" -ForegroundColor Green
Write-Host "  Export directory: $destFolder" -ForegroundColor White
Write-Host "  You can copy this folder to the target server and run:" -ForegroundColor White
Write-Host "    Import-VM -Path '$destFolder\Virtual Machines\<GUID>.vmcx'" -ForegroundColor Cyan
Write-Host "================================================================" -ForegroundColor Green
