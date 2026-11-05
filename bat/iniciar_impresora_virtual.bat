@echo off
title Impresora Termica Virtual (Puerto 9100) - Mi Jardin Restaurant
cd /d "%~dp0\.."

echo ========================================================
echo   IMPRESORA TERMICA VIRTUAL (SIMULADOR ESC/POS)          
echo   Puerto TCP 9100 - Mi Jardin Restaurant                 
echo ========================================================
echo.
echo Esta ventana simula la ticketera fisica de cocina y barra.
echo Cuando envies un pedido:
echo   1. El ticket aparecera impreso en texto aqui abajo.
echo   2. Se guardara automaticamente en:
echo      storage\tickets_virtuales.txt
echo.
echo TIP: Puedes abrir "bat\ver_tickets_virtuales.bat"
echo      para ver los tickets formateados en el Bloc de Notas.
echo ========================================================
echo.

php -v >nul 2>&1
if %errorlevel% neq 0 (
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
