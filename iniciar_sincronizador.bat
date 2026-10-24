@echo off
title Sincronizador Cloud - Mi Jardin Restaurant
cd /d "%~dp0"
echo ========================================================
echo     SINCRONIZADOR CLOUD - MI JARDIN RESTAURANT
echo        (MYSQL PUERTO 3307 LOCAL -^> NUBE HTTPS)
echo ========================================================
echo.
echo Conectando a bases locales en puerto 3307...
echo Enviando mesas activas y consumos a la nube...
echo.
echo Presione Ctrl + C para detener el sincronizador.
echo ========================================================
echo.
php sync_daemon.php
pause
