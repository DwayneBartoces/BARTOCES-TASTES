@echo off
title Start Docker & Project
echo ========================================================
echo Starting Docker Engine & MySQL Containers...
echo ========================================================

:: 1. Trigger Docker Linux Engine
if exist "%LOCALAPPDATA%\Programs\DockerDesktop\DockerCli.exe" (
    "%LOCALAPPDATA%\Programs\DockerDesktop\DockerCli.exe" -SwitchLinuxEngine >nul 2>&1
)

:: 2. Wait up to 30s for the named pipe
echo Waiting for Docker daemon to initialize...
for /L %%i in (1,1,15) do (
    docker ps >nul 2>&1
    if not errorlevel 1 goto DOCKER_READY
    timeout /t 2 /nobreak >nul
)

:DOCKER_READY
echo Docker daemon is ready!
echo.

:: 3. Run docker compose
cd /d "%~dp0"
docker compose up -d

echo.
echo ========================================================
echo All containers are UP and running!
echo MySQL Port: 3307
echo phpMyAdmin: http://localhost:8081
echo ========================================================
pause

