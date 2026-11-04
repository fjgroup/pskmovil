@echo off
title Actualizar Reportes de Comandas en Premium Soft
cd /d "%~dp0\.."

echo ========================================================
echo   ACTUALIZADOR DE REPORTES PARA PREMIUM SOFT
echo   (Cocina, Barra y Bebidas sin ID de transaccion)
echo ========================================================
echo.

set "ORIGEN=%CD%\fix_premium\reports"
set "DEST_BAHIAP=C:\PREMIUM\ADMINISTRATIVO9X\bahiap\reports"
set "DEST_MATRICES=C:\PREMIUM\ADMINISTRATIVO9X\Matrices\reports"

if not exist "%ORIGEN%" (
    echo [ERROR] No se encontro la carpeta: %ORIGEN%
    pause
    exit /b 1
)

echo [1/2] Copiando reportes corregidos a bahiap\reports...
if exist "%DEST_BAHIAP%" (
    copy /y "%ORIGEN%\*.*" "%DEST_BAHIAP%\" >nul
    echo       [OK] Reportes actualizados en: %DEST_BAHIAP%
) else (
    echo       [AVISO] No existe la ruta: %DEST_BAHIAP%
)

echo.
echo [2/2] Copiando reportes corregidos a Matrices\reports...
if exist "%DEST_MATRICES%" (
    copy /y "%ORIGEN%\*.*" "%DEST_MATRICES%\" >nul
    echo       [OK] Reportes actualizados en: %DEST_MATRICES%
) else (
    echo       [AVISO] No existe la ruta: %DEST_MATRICES%
)

echo.
echo ========================================================
echo  Verificacion de archivos copiados:
echo ========================================================
powershell -NoProfile -Command "Get-ChildItem '%DEST_BAHIAP%\cocina.*', '%DEST_BAHIAP%\barra.*' | Select-Object Name, LastWriteTime, Length | Format-Table -AutoSize"

echo ========================================================
echo  Reportes sincronizados exitosamente!
echo ========================================================
pause
