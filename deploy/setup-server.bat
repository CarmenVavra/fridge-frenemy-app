@echo off
REM Einmalige Einrichtung des netcup-Servers. Details: DEPLOYMENT.md
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0setup-server.ps1"
pause
