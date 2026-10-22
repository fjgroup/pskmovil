@echo off
title Sistema Mesoneros - PSK Movil
cd /d "%~dp0"
echo ========================================================
echo       SISTEMA PSK MOVIL PARA MESONEROS (WIFI LOCAL)
echo ========================================================
echo.
echo Servidor iniciado correctamente.
echo Deja esta ventana abierta mientras el restaurante este operando.
echo.
echo Los mesoneros deben entrar desde sus celulares o tablets a:
echo http://%COMPUTERNAME%:8080  o la IP de esta maquina (ej. http://192.168.0.102:8080)
echo.
echo ========================================================
php -S 0.0.0.0:8080
pause
