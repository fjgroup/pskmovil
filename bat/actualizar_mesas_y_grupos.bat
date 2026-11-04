@echo off
setlocal
title Actualizar Mesas y Grupos en Premium Soft - Mi Jardin Restaurant
echo ========================================================
echo   ACTUALIZACION DE MESAS Y GRUPOS (BARRA / COCINA)      
echo   Mi Jardin Restaurant - Premium Soft                   
echo ========================================================
echo.
echo Este proceso realizara:
echo   1. Respaldo automatico de la tabla idconsumos.
echo   2. Normalizacion de codigos de mesas:
echo      - Tasca:  t01 (TASCA 01)
echo      - Llevar: m00 (LLEVAR)
echo      - Salon:  m01 a m22 (MESA 01 a MESA 22)
echo      - Jardin: j01 a j05 (JARDIN 01 a JARDIN 05)
echo   3. Enrutamiento de Cervezas y Licores (Grupo 006) a BARRA.
echo.
pause

set "PHP_CMD=php"
if exist "%USERPROFILE%\.config\herd\bin\php84\php.exe" (
    set "PHP_CMD=%USERPROFILE%\.config\herd\bin\php84\php.exe"
) else if exist "C:\Users\Principal\.config\herd\bin\php84\php.exe" (
    set "PHP_CMD=C:\Users\Principal\.config\herd\bin\php84\php.exe"
)

"%PHP_CMD%" "%~dp0..\funciones\migrar_mesas_restaurante.php"

echo.
pause
