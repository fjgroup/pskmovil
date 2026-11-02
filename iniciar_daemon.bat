@echo off
title Sincronizador Cloud Continuo - Mi Jardin Restaurant
cd /d "%~dp0"

:: 1. Detectar ejecutable real de PHP si no viene heredado
if not defined PHP_CMD (
    if exist "%USERPROFILE%\.config\herd\bin\php84\php.exe" (
        set "PHP_CMD=%USERPROFILE%\.config\herd\bin\php84\php.exe"
    ) else if exist "C:\Users\Principal\.config\herd\bin\php84\php.exe" (
        set "PHP_CMD=C:\Users\Principal\.config\herd\bin\php84\php.exe"
    ) else (
        for /f "delims=" %%I in ('where php.exe 2^>nul') do (
            if not defined PHP_CMD set "PHP_CMD=%%I"
        )
    )
)
if not defined PHP_CMD set "PHP_CMD=php"

:bucle
echo [%DATE% %TIME%] Iniciando demonio de sincronizacion cloud...
"%PHP_CMD%" sync_daemon.php
echo [%DATE% %TIME%] [AVISO] El sincronizador finalizo. Reiniciando automaticamente en 5 segundos...
timeout /t 5 /nobreak >nul
goto bucle

