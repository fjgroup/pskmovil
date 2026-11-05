@echo off
title Historial de Tickets de Comandas - Mi Jardin Restaurant
set "LOG_DIR=%~dp0..\storage"
set "LOG_FILE=%LOG_DIR%\tickets_virtuales.txt"

if not exist "%LOG_DIR%" mkdir "%LOG_DIR%"

if not exist "%LOG_FILE%" (
    (
        echo ========================================================
        echo   MI JARDIN RESTAURANT - HISTORIAL DE TICKETS           
        echo ========================================================
        echo   Aun no se han generado pedidos en esta sesion.        
        echo   Al enviar cualquier comanda desde el sistema web,     
        echo   aparecera registrada aqui automaticamente.            
        echo ========================================================
    ) > "%LOG_FILE%"
)

start notepad.exe "%LOG_FILE%"
exit
