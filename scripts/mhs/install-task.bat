@echo off
setlocal DisableDelayedExpansion
:: ==============================================================================
:: INSTALLER OTOMATIS SYNC LAPORAN BDS MHS KE WINDOWS TASK SCHEDULER
:: Klik kanan -> "Run as administrator" di Server MHS (10.111.166.11)
:: ==============================================================================

net session >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] Harap jalankan script ini sebagai Administrator [Run as Administrator].
    pause
    exit /b 1
)

echo ================================================================
echo   MEMASANG TUGAS OTOMATIS SINKRONISASI LAPORAN BDS [SETIAP 10 MENIT]
echo ================================================================

set "SCRIPT_PATH=%~dp0push-reports-to-portal.ps1"

if not exist "%SCRIPT_PATH%" (
    echo [ERROR] File push-reports-to-portal.ps1 tidak ditemukan di "%~dp0".
    pause
    exit /b 1
)

:: Buat Windows Scheduled Task bernama "BDS_MHS_Report_Sync" yang jalan tiap 10 menit
schtasks /create /tn "BDS_MHS_Report_Sync" ^
    /tr "powershell.exe -ExecutionPolicy Bypass -WindowStyle Hidden -File \"%SCRIPT_PATH%\"" ^
    /sc minute /mo 10 /ru "SYSTEM" /f

if %errorlevel% equ 0 (
    echo.
    echo ================================================================
    echo   BERHASIL: Task "BDS_MHS_Report_Sync" telah aktif.
    echo   Laporan MHS akan otomatis dikirim ke Web Portal setiap 10 menit.
    echo ================================================================
) else (
    echo.
    echo [GAGAL] Terjadi kesalahan saat mendaftarkan Task Scheduler.
)

echo.
pause

