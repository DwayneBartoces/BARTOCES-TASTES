@echo off
title Bartoces Tastes Server
cd /d "%~dp0"
echo ========================================================
echo Starting Bartoces Tastes on http://localhost:8080
echo (Running without XAMPP using PHP built-in server)
echo ========================================================
php -S localhost:8080
pause
