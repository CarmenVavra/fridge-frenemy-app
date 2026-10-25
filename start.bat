@echo off
REM ---------------------------------------------------------------------------
REM  Fridge Frenemy - lokale Entwicklung starten
REM  Backend : Laravel (php artisan serve)  -> http://localhost:8550
REM  Frontend: Vite Dev-Server (Hot Reload) -> http://localhost:5173
REM ---------------------------------------------------------------------------
setlocal
cd /d "%~dp0"

where php >nul 2>nul || (echo [FEHLER] PHP wurde nicht gefunden. & pause & exit /b 1)
where npm >nul 2>nul || (echo [FEHLER] npm/Node.js wurde nicht gefunden. & pause & exit /b 1)

if not exist vendor\autoload.php (
    echo Installiere PHP-Abhaengigkeiten ...
    call composer install --no-interaction || (pause & exit /b 1)
)
if not exist node_modules (
    echo Installiere Node-Abhaengigkeiten ...
    call npm install || (pause & exit /b 1)
)
if not exist .env (
    copy .env.example .env >nul
    php artisan key:generate --ansi
)
if not exist database\database.sqlite (
    type nul > database\database.sqlite
    php artisan migrate --seed --force
) else (
    php artisan migrate --force
)

echo Starte Backend auf http://localhost:8550 ...
start "FridgeFrenemy-Backend" cmd /k php artisan serve --host=127.0.0.1 --port=8550

echo Starte Frontend (Vite) ...
start "FridgeFrenemy-Frontend" cmd /k npm run dev

timeout /t 3 /nobreak >nul
start "" http://localhost:8550

echo.
echo Fridge Frenemy laeuft: http://localhost:8550
echo Demo-Login steht in database\seeders\DemoSeeder.php
echo Stoppen mit stop.bat
endlocal
