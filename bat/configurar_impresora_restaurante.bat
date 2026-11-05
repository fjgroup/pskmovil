@echo off
setlocal enabledelayedexpansion
title Configurar Impresora Fisica Restaurante (POS-80C / Cocina)
echo ========================================================
echo   CONFIGURADOR DE IMPRESORA FISICA DEL RESTAURANTE       
echo   Mi Jardin Restaurant - IP Termica: 192.168.1.150       
echo ========================================================
echo.

:: Verificar privilegios de Administrador
net session >nul 2>&1
if %errorlevel% neq 0 (
    echo [AVISO] Se requieren permisos de Administrador para modificar puertos de Windows.
    echo Solicitando elevacion de permisos...
    powershell -NoProfile -Command "Start-Process '%~f0' -Verb RunAs"
    exit /b
)

echo [1/3] Verificando conectividad con la impresora fisica (192.168.1.150)...
ping -n 2 -w 1000 192.168.1.150 >nul 2>&1
if %errorlevel% equ 0 (
    echo       [OK] Impresora 192.168.1.150 RESPONDE al Ping (Encendida en red).
) else (
    echo       [AVISO] No se recibio respuesta de 192.168.1.150.
    echo               Si estas en Casa, esto es normal porque la impresora quedo en el restaurante.
    echo               En el restaurante: asegura que la ticketera este conectada al router y encendida.
)

echo.
echo [2/3] Configurando puerto TCP/IP en Windows a 192.168.1.150...
powershell -NoProfile -Command ^
    "$portName = '192.168.1.150';" ^
    "$exists = Get-PrinterPort -Name $portName -ErrorAction SilentlyContinue;" ^
    "if (-not $exists) {" ^
    "    Add-PrinterPort -Name $portName -PrinterHostAddress '192.168.1.150' -ErrorAction SilentlyContinue;" ^
    "    Write-Host '  Puerto TCP/IP 192.168.1.150 creado con exito.' -ForegroundColor Green;" ^
    "} else {" ^
    "    Write-Host '  Puerto TCP/IP 192.168.1.150 ya existe.' -ForegroundColor Yellow;" ^
    "};" ^
    "$printers = Get-Printer | Where-Object { $_.Name -match '(?i)POS-80|cocina' };" ^
    "foreach ($p in $printers) {" ^
    "    Set-Printer -Name $p.Name -PortName $portName -ErrorAction SilentlyContinue;" ^
    "    Write-Host ('  Impresora ' + $p.Name + ' asignada al puerto ' + $portName) -ForegroundColor Green;" ^
    "}"

echo.
echo [3/3] Estado actual de impresoras termicas en Windows:
echo --------------------------------------------------------
powershell -NoProfile -Command "Get-Printer | Where-Object { $_.Name -match '(?i)POS-80|cocina' } | Select-Object Name, PortName, DriverName | Format-Table -AutoSize"
echo --------------------------------------------------------
echo.
echo ========================================================
echo  Configuracion completada!
echo  En el restaurante, Comandas Moviles y Premium Soft
echo  enviaran las comandas directamente a la ticketera fisica.
echo ========================================================
echo.
pause
