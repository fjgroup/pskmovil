@echo off
title Impresora Termica Virtual (Puerto 9100)
cd /d "%~dp0\.."

php -v >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] PHP no esta en el PATH. Buscando php.exe localmente...
    if exist "%USERPROFILE%\.config\herd\bin\php84\php.exe" (
        "%USERPROFILE%\.config\herd\bin\php84\php.exe" servidor_impresora_virtual.php
        goto end
    )
    if exist "C:\Users\Principal\.config\herd\bin\php84\php.exe" (
        "C:\Users\Principal\.config\herd\bin\php84\php.exe" servidor_impresora_virtual.php
        goto end
    )
)
php servidor_impresora_virtual.php
:end
pause
