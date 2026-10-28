@echo off
REM ---------------------------------------------------------------------------
REM  Fridge Frenemy - lokale Entwicklung stoppen (Backend 8550 + Vite 5173)
REM ---------------------------------------------------------------------------
setlocal
cd /d "%~dp0"

echo Stoppe Fridge Frenemy ...

REM Konsolenfenster aus start.bat samt Kindprozessen schliessen
taskkill /FI "WINDOWTITLE eq FridgeFrenemy-Backend*" /T /F >nul 2>nul
taskkill /FI "WINDOWTITLE eq FridgeFrenemy-Frontend*" /T /F >nul 2>nul

REM Falls noch etwas auf den Ports lauscht: Prozess beenden
for %%P in (8550 5173) do (
    for /f "tokens=5" %%I in ('netstat -ano ^| findstr /R /C:":%%P  *0\.0\.0\.0:0 " /C:":%%P  *\[::\]:0 "') do (
        taskkill /PID %%I /T /F >nul 2>nul
    )
)

REM Vite legt public\hot an; ohne laufenden Dev-Server wuerde Laravel ins Leere zeigen
if exist public\hot del /q public\hot

echo Backend und Frontend wurden gestoppt.
endlocal
