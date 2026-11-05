@echo off
setlocal
title Actualizar Mesas y Grupos en Premium Soft - Mi Jardin Restaurant
echo ========================================================
echo   ACTUALIZACION DE MESAS Y GRUPOS (BARRA / COCINA)      
echo   Mi Jardin Restaurant - Premium Soft                   
echo ========================================================
echo.
echo Este proceso configurara en Premium Soft:
echo   1. Respaldo automatico con fecha y hora de idconsumos.
echo   2. Mesas en MAYUSCULAS estandar de Premium Soft:
echo      - Tasca:  T01 (TASCA 01)
echo      - Llevar: M00 (PARA LLEVAR)
echo      - Salon:  M01 a M22 (MESA 01 a MESA 22)  [22 Mesas]
echo      - Jardin: J01 a J05 (JARDIN 01 a JARDIN 05)
echo   3. Sincronizacion de cuentas activas en operti (EMXX).
echo   4. Asignacion de Bebidas y Licores (Grupos 005 y 006) a BARRA.
echo.
pause

:: Detección de PHP
if not defined PHP_CMD (
    if exist "%USERPROFILE%\.config\herd\bin\php84\php.exe" (
        set "PHP_CMD=%USERPROFILE%\.config\herd\bin\php84\php.exe"
    ) else if exist "C:\Users\Principal\.config\herd\bin\php84\php.exe" (
        set "PHP_CMD=C:\Users\Principal\.config\herd\bin\php84\php.exe"
    ) else (
        for /f "delims=" %%I in ('where php.exe 2^>nul') do (
            if not defined PHP_CMD set "PHP_CMD=%%I"
        )
    )
)
if not defined PHP_CMD set "PHP_CMD=php"

"%PHP_CMD%" "%~dp0..\funciones\migrar_mesas_restaurante.php"

echo.
pause
