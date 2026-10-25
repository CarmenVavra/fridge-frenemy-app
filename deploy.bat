@echo off
REM Stellt den aktuellen Stand auf https://fridgefrenemy.caryssa.at online.
REM Details: DEPLOYMENT.md  -  Optionen: deploy.bat -SkipTests
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0deploy\deploy.ps1" %*
if errorlevel 1 (
    echo.
    echo Deployment fehlgeschlagen.
    pause
    exit /b 1
)
pause
