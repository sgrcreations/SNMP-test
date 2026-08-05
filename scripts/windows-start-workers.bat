@echo off
setlocal
cd /d "%~dp0.."

echo.
echo ========================================
echo  SGR SNMP Test Kit - Windows Workers
echo ========================================
echo  Starts:
echo   1) Queue worker  (processes SNMP poll jobs)
echo   2) Scheduler     (runs devices:poll every minute)
echo.
echo  Keep this window OPEN while using the app.
echo  Press Ctrl+C to stop.
echo ========================================
echo.

where php >nul 2>nul
if errorlevel 1 (
  echo ERROR: PHP not found in PATH.
  echo Install PHP and add it to PATH, then try again.
  pause
  exit /b 1
)

php artisan migrate --force
if errorlevel 1 (
  echo ERROR: migrate failed.
  pause
  exit /b 1
)

echo Starting queue worker + scheduler...
call composer run workers
pause
