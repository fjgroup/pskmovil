@echo off
title Sistema Mesoneros y Sincronizador Cloud - Mi Jardin Restaurant
cd /d "%~dp0"

echo ========================================================
echo   SISTEMA RESTAURANTE MI JARDIN (LOCAL + NUBE UNIFICADO)
echo ========================================================
echo.
echo [1/2] Iniciando Sincronizador Cloud en segundo plano...
start "Sincronizador Cloud - Mi Jardin" /min php sync_daemon.php
echo       Sincronizador activo (MySQL 3307 -^> Nube HTTPS).
echo.
echo [2/2] Iniciando Servidor Web Local para Mesoneros (Puerto 8080)...
start "Servidor Web Mesoneros - Mi Jardin" /min php -S 0.0.0.0:8080
echo       Servidor web local activo en puerto 8080.
echo.
echo ========================================================
echo ACCESO PARA MESONEROS Y SUPERVISORES (WIFI PRIVADA):
echo http://%COMPUTERNAME%:8080 o la IP del servidor (ej: http://192.168.0.102:8080)
echo ========================================================
echo.
echo [INFO] Ambos servicios estan corriendo en segundo plano.
echo Si cierra esta ventana por error, los servicios SEGUIRAN
echo funcionando sin interrupciones hasta que apague la PC.
echo ========================================================
echo.
echo Presione cualquier tecla o cierre esta ventana cuando desee...
pause > nul
