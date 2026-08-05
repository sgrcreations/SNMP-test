@echo off
setlocal
cd /d "%~dp0.."

echo.
echo Running one immediate SNMP poll (sync)...
php artisan devices:poll --sync -v
echo.
pause
