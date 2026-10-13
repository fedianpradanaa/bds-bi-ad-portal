@echo off
title BDS Portal - Setup Passwordless SSH Key
echo ================================================================
echo   SETUP PASSWORDLESS SSH KEY TO SERVER (10.111.167.30)
echo ================================================================
cd /d "%~dp0\..\.."
powershell -ExecutionPolicy Bypass -File "scripts\sync-to-server.ps1" -SetupKey
echo.
pause
