@echo off
title BDS Portal - Instant Push Sync
echo ================================================================
echo   INSTANT CODE SYNC TO SERVER (10.111.167.30)
echo ================================================================
cd /d "%~dp0\..\.."
powershell -ExecutionPolicy Bypass -File "scripts\sync-to-server.ps1"
echo.
pause
