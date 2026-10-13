@echo off
title BDS Portal - Live Auto-Watch Sync (Realtime)
echo ================================================================
echo   STARTING REALTIME AUTO-WATCH CODE SYNC TO SERVER
echo   (Changes will automatically sync on every Ctrl+S / Save)
echo ================================================================
cd /d "%~dp0\..\.."
powershell -ExecutionPolicy Bypass -File "scripts\sync-to-server.ps1" -Watch
echo.
pause
