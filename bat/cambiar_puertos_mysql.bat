@echo off
setlocal enabledelayedexpansion
title Configurar Puertos MySQL (8.4 a 3300 / 5.5 a 3306)

:: Verificar permisos de Administrador
net session >nul 2>&1
if %errorlevel% neq 0 (
    echo.
    echo ========================================================
    echo  ERROR: Se requieren permisos de Administrador.
    echo ========================================================
    echo  Por favor haga clic DERECHO en este archivo y elija:
    echo  "Ejecutar como administrador"
    echo ========================================================
    echo.
    pause
    exit /b 1
)

echo.
echo ========================================================
echo  Reconfigurando Puertos MySQL
echo  - MySQL 8.4 (Web):         Puerto 3306 --^> 3300
echo  - MySQL 5.5 (Premium Soft): Puerto 3307 --^> 3306
echo ========================================================
echo.

echo [1/4] Deteniendo servicios MySQL...
net stop MYSQL84 /y >nul 2>&1
net stop MySQL55 /y >nul 2>&1

echo [2/4] Modificando configuracion my.ini de MySQL 8.4 (Puerto 3300)...
powershell -NoProfile -Command "$f = 'C:\ProgramData\MySQL\MySQL Server 8.4\my.ini'; if (Test-Path $f) { $t = Get-Content $f -Raw; $t = $t -replace '(?m)^port\s*=\s*3306\b', 'port=3300'; [System.IO.File]::WriteAllText($f, $t); Write-Host '  MySQL 8.4 configurado en puerto 3300.' -ForegroundColor Green } else { Write-Host '  No se encontro my.ini de MySQL 8.4' -ForegroundColor Red }"

echo [3/4] Modificando configuracion my.ini de MySQL 5.5 (Puerto 3306)...
powershell -NoProfile -Command "$f = 'C:\Program Files (x86)\MySQL\MySQL Server 5.5\my.ini'; if (Test-Path $f) { $t = Get-Content $f -Raw; $t = $t -replace '(?m)^port\s*=\s*3307\b', 'port=3306'; [System.IO.File]::WriteAllText($f, $t); Write-Host '  MySQL 5.5 configurado en puerto 3306.' -ForegroundColor Green } else { Write-Host '  No se encontro my.ini de MySQL 5.5' -ForegroundColor Red }"

echo [4/4] Iniciando servicios MySQL con los nuevos puertos...
net start MYSQL84
net start MySQL55

echo.
echo ========================================================
echo  Verificando puertos activos:
echo ========================================================
powershell -NoProfile -Command "Get-NetTCPConnection -State Listen | Where-Object { $_.LocalPort -in 3300, 3306, 3307 } | Select-Object LocalAddress, LocalPort, OwningProcess | Format-Table -AutoSize"

echo.
echo ========================================================
echo  Puertos configurados exitosamente.
echo ========================================================
pause
