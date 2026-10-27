@echo off
title Sistema Mesoneros y Sincronizador Cloud - Mi Jardin Restaurant
cd /d "%~dp0"

:: 1. Detectar ejecutable real de PHP (Laravel Herd o PATH del sistema)
set "PHP_CMD="
if exist "%USERPROFILE%\.config\herd\bin\php84\php.exe" (
    set "PHP_CMD=%USERPROFILE%\.config\herd\bin\php84\php.exe"
) else if exist "C:\Users\Principal\.config\herd\bin\php84\php.exe" (
    set "PHP_CMD=C:\Users\Principal\.config\herd\bin\php84\php.exe"
) else (
    for /f "delims=" %%I in ('where php.exe 2^>nul') do (
        if not defined PHP_CMD set "PHP_CMD=%%I"
    )
)
if not defined PHP_CMD set "PHP_CMD=php"

:: 2. Detectar la IP asignada por el router activamente (Ruta por defecto 0.0.0.0)
set "LOCAL_IP=127.0.0.1"
for /f "tokens=4" %%a in ('route print 0.0.0.0 ^| findstr "\<0.0.0.0\>" 2^>nul') do (
    set "LOCAL_IP=%%a"
)

:: 3. Puerto web para comandas moviles (2010 por defecto en PSK Movil)
set "WEB_PORT=2010"

:: 4. Detectar puerto MySQL: primero 3307 (DESARROLLO), si no responde 3306 (PRODUCCION)
::    En desarrollo ambos puertos funcionan, por eso 3307 tiene prioridad.
set "DB_PORT=0"
set "ENTORNO=SIN MYSQL"
netstat -an | findstr /R /C:":3307 .*LISTENING" >nul 2>&1
if not errorlevel 1 (
    set "DB_PORT=3307"
    set "ENTORNO=DESARROLLO"
) else (
    netstat -an | findstr /R /C:":3306 .*LISTENING" >nul 2>&1
    if not errorlevel 1 (
        set "DB_PORT=3306"
        set "ENTORNO=PRODUCCION"
    )
)

echo ========================================================
echo   SISTEMA RESTAURANTE MI JARDIN (LOCAL + NUBE UNIFICADO)
echo ========================================================
echo.
echo [INFO] Base de Datos MySQL: Puerto %DB_PORT% ^>^> Entorno %ENTORNO%
if "%DB_PORT%"=="3307" (
    echo        Credenciales activas: Usuario 'root' ^| Clave 'root' (Desarrollo)
) else if "%DB_PORT%"=="3306" (
    echo        Credenciales activas: Usuario 'root' ^| Clave '1234' (Produccion)
)
echo        Destino Cloud: DIRECTO al Hosting (https://mijardinrestaurant.com)
echo.
echo [1/2] Iniciando Sincronizador Cloud en segundo plano...
start "Sincronizador Cloud - Mi Jardin" /min "%PHP_CMD%" sync_daemon.php
echo       Sincronizador activo (Premium Soft -^> Nube HTTPS mijardinrestaurant.com).
echo.
echo [2/2] Iniciando Servidor Web Local para Mesoneros (Puerto %WEB_PORT%)...
start "Servidor Web Mesoneros - Mi Jardin" /min "%PHP_CMD%" -S 0.0.0.0:%WEB_PORT%
echo       Servidor web local activo en puerto %WEB_PORT%.
echo.
echo ========================================================
echo ACCESO PARA MESONEROS Y SUPERVISORES (WIFI RESTAURANTE):
echo Desde celulares o tablets:  http://%LOCAL_IP%:%WEB_PORT%
echo Desde esta computadora:    http://localhost:%WEB_PORT%
echo ========================================================
echo.
echo [INFO] Ambos servicios estan corriendo en segundo plano.
echo Si cierra esta ventana por error, los servicios SEGUIRAN
echo funcionando sin interrupciones hasta que apague la PC.
echo ========================================================
echo.
echo Presione cualquier tecla o cierre esta ventana cuando desee...
pause > nul