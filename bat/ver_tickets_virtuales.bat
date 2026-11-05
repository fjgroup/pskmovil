@echo off
title Ver Tickets Virtuales de Comandas
set "LOG_FILE=%~dp0..\storage\tickets_virtuales.txt"

if not exist "%LOG_FILE%" (
    echo ========================================================
    echo   No hay tickets registrados todavia.
    echo ========================================================
    echo   Para registrar tickets de prueba sin impresora fisica:
    echo   1. Ejecuta: bat\iniciar_impresora_virtual.bat
    echo   2. Envia un pedido desde Comandas Moviles o Premium Soft.
    echo ========================================================
    pause
    exit /b
)

echo Abriendo historial de tickets impresos en el Bloc de Notas...
start notepad.exe "%LOG_FILE%"
