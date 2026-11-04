@echo off
title Crear Acceso Directo en Escritorio
cd /d "%~dp0\.."

echo ========================================================
echo   CREAR ACCESO DIRECTO DE INICIO EN ESCRITORIO
echo ========================================================
echo.

set "TARGET_BAT=%CD%\iniciar_sistema.bat"

if not exist "%TARGET_BAT%" (
    echo [ERROR] No se encontro %TARGET_BAT%
    pause
    exit /b 1
)

powershell -NoProfile -Command ^
    "$wsh = New-Object -ComObject WScript.Shell;" ^
    "$desktop = [Environment]::GetFolderPath('Desktop');" ^
    "$sc = $wsh.CreateShortcut($desktop + '\Iniciar Comandas Moviles.lnk');" ^
    "$sc.TargetPath = '%TARGET_BAT%';" ^
    "$sc.WorkingDirectory = '%CD%';" ^
    "$sc.Description = 'Iniciar Sistema de Comandas Moviles y Sincronizador';" ^
    "$sc.IconLocation = 'shell32.dll,138';" ^
    "$sc.Save();" ^
    "Write-Host '  [OK] Acceso directo creado en el Escritorio: Iniciar Comandas Moviles.lnk' -ForegroundColor Green;"

echo.
echo ========================================================
echo Con este acceso directo, cada vez que hagas 'git pull',
echo el acceso del escritorio siempre abrira la version
echo actualizada del repositorio, sin desincronizarse.
echo ========================================================
pause
