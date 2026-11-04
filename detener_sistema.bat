@echo off
title Detener Sistema Comandas Moviles
echo ========================================================
echo   DETENIENDO SERVICIOS EN SEGUNDO PLANO
echo ========================================================
echo.
taskkill /F /IM php.exe /FI "WINDOWTITLE eq Servidor Web*" >nul 2>&1
taskkill /F /IM cmd.exe /FI "WINDOWTITLE eq Sincronizador Cloud*" >nul 2>&1
taskkill /F /IM cmonitor.exe >nul 2>&1
echo [OK] Servicios detenidos.
timeout /t 2 >nul

