@echo off
title Diagnostico y Tests de Sistema - Mi Jardin Restaurant
cd /d "%~dp0"

:: Detectar ejecutable de PHP (Herd o PATH)
set "PHP_CMD="
if exist "%USERPROFILE%\.config\herd\bin\php84\php.exe" (
    set "PHP_CMD=%USERPROFILE%\.config\herd\bin\php84\php.exe"
) else if exist "C:\Users\Principal\.config\herd\bin\php84\php.exe" (
    set "PHP_CMD=C:\Users\Principal\.config\herd\bin\php84\php.exe"
) else (
    for /f "delims=" %%I in ('where php.exe 2^>nul') do (
        if not defined PHP_CMD set "PHP_CMD=%%I"
    )
)
if not defined PHP_CMD set "PHP_CMD=php"

echo ========================================================
echo   EJECUTANDO SUITE DE TESTS Y DIAGNOSTICO INTEGRAL
echo ========================================================
echo.
"%PHP_CMD%" tests\test_suite.php
echo.
echo Presione cualquier tecla para salir...
pause > nul
