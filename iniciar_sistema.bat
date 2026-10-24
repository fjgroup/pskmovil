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
echo.
echo ========================================================
echo ACCESO PARA MESONEROS Y SUPERVISORES (WIFI PRIVADA):
echo http://%COMPUTERNAME%:8080 o la IP del servidor (ej: http://192.168.0.102:8080)
echo ========================================================
echo.
echo Mantenga esta ventana abierta durante la jornada del restaurante.
echo Presione Ctrl + C para detener el sistema.
echo.
php -S 0.0.0.0:8080
pause
