@echo off
:: ==============================================================================
:: TES MANUAL PUSH LAPORAN BDS MHS KE WEB PORTAL
:: Double-click di Server MHS untuk tes koneksi dan kirim data secara langsung
:: ==============================================================================

echo ================================================================
echo   TES SINKRONISASI MANUAL MHS -> WEB PORTAL BDS
echo ================================================================

powershell.exe -ExecutionPolicy Bypass -NoExit -File "%~dp0push-reports-to-portal.ps1" -ForceAll %*
