@echo off
title Ultimos Tickets de Comandas - Mi Jardin Restaurant
cd /d "%~dp0\.."

set "LOG_DIR=%CD%\storage"
set "LOG_FILE=%LOG_DIR%\tickets_virtuales.txt"

if not exist "%LOG_DIR%" mkdir "%LOG_DIR%"

:: 1. Reanudar automaticamente cualquier trabajo trabado en la cola de POS-80C
powershell -NoProfile -Command "Get-PrintJob -PrinterName 'POS-80C' -ErrorAction SilentlyContinue | Resume-PrintJob -ErrorAction SilentlyContinue" >nul 2>&1

:: 2. Asegurar que tickets_virtuales.txt exista
if not exist "%LOG_FILE%" (
    echo ======================================================== > "%LOG_FILE%"
    echo   MI JARDIN RESTAURANT - HISTORIAL DE TICKETS           >> "%LOG_FILE%"
    echo ======================================================== >> "%LOG_FILE%"
    echo   Aun no se han generado pedidos en esta sesion.        >> "%LOG_FILE%"
    echo ======================================================== >> "%LOG_FILE%"
)

:: 3. Recortar el archivo para conservar y mostrar UNICAMENTE las ultimas 4 impresiones
php -r "$f='storage/tickets_virtuales.txt'; if(file_exists($f)){ $c=file_get_contents($f); if(preg_match_all('/={30,}\r?\n\[TICKET[^=]+={30,}\r?\n.*?(?=(?:={30,}\r?\n\[TICKET)|\z)/s', $c, $m)){ file_put_contents($f, trim(implode(\"\n\n\", array_slice($m[0], -4))).\"\n\"); }}" >nul 2>&1

:: 4. Abrir en el Bloc de Notas
start notepad.exe "%LOG_FILE%"
exit
