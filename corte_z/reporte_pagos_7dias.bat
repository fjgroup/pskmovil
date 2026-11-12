@echo off
title Reporte de Pagos - Ultimos 7 Dias (BAHIAP)
cd /d "%~dp0"

echo ========================================================
echo   REPORTE DE INGRESOS POR METODO DE PAGO (ULTIMOS 7 DIAS)
echo ========================================================
echo.

set "PDF_PATH="
for /f "tokens=1,* delims==" %%A in ('php reporte_pagos_7dias.php') do (
    if "%%A"=="PDF_GENERADO" set "PDF_PATH=%%B"
    if "%%A"=="PERIODO" echo   Periodo: %%B
    if "%%A"=="ENTORNO" echo   Entorno: %%B
)

if "%PDF_PATH%"=="" (
    echo.
    echo [ERROR] No se pudo generar el reporte PDF.
    pause
    exit /b 1
)

echo.
echo   [OK] PDF generado en:
echo   %PDF_PATH%
echo.
start "" "%PDF_PATH%"

