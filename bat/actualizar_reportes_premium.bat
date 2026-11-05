@echo off
title Actualizar Reportes y Consultas de Comandas en Premium Soft
cd /d "%~dp0\.."

echo ========================================================
echo   ACTUALIZADOR DE REPORTES Y CONSULTAS PARA PREMIUM SOFT
echo   - Sin ID de transaccion en las comandas
echo   - Soporte de notas por producto en cocina y barra
echo ========================================================
echo.

set "ORIGEN_REP=%CD%\fix_premium\reports"
set "ORIGEN_QRY=%CD%\fix_premium\querys"
set "DEST_BAHIAP_REP=C:\PREMIUM\ADMINISTRATIVO9X\bahiap\reports"
set "DEST_BAHIAP_QRY=C:\PREMIUM\ADMINISTRATIVO9X\bahiap\querys"
set "DEST_MAT_REP=C:\PREMIUM\ADMINISTRATIVO9X\Matrices\reports"
set "DEST_MAT_QRY=C:\PREMIUM\ADMINISTRATIVO9X\Matrices\querys"

echo [1/4] Copiando reportes corregidos a bahiap\reports...
if exist "%DEST_BAHIAP_REP%" (
    copy /y "%ORIGEN_REP%\*.*" "%DEST_BAHIAP_REP%\" >nul
    echo       [OK] Reportes actualizados en: %DEST_BAHIAP_REP%
)

echo [2/4] Copiando consultas SQL corregidas a bahiap\querys...
if exist "%DEST_BAHIAP_QRY%" (
    copy /y "%ORIGEN_QRY%\*.*" "%DEST_BAHIAP_QRY%\" >nul
    echo       [OK] Consultas actualizadas en: %DEST_BAHIAP_QRY%
)

echo [3/4] Copiando reportes a Matrices\reports...
if exist "%DEST_MAT_REP%" (
    copy /y "%ORIGEN_REP%\*.*" "%DEST_MAT_REP%\" >nul
    echo       [OK] Reportes actualizados en: %DEST_MAT_REP%
)

echo [4/4] Copiando consultas a Matrices\querys...
if exist "%DEST_MAT_QRY%" (
    copy /y "%ORIGEN_QRY%\*.*" "%DEST_MAT_QRY%\" >nul
    echo       [OK] Consultas actualizadas en: %DEST_MAT_QRY%
)

echo.
echo ========================================================
echo  Verificacion de archivos copiados:
echo ========================================================
powershell -NoProfile -Command "Get-ChildItem '%DEST_BAHIAP_REP%\cocina.*', '%DEST_BAHIAP_REP%\barra.*', '%DEST_BAHIAP_QRY%\cocina.qry', '%DEST_BAHIAP_QRY%\barra.qry' | Select-Object Name, LastWriteTime, Length | Format-Table -AutoSize"

echo ========================================================
echo  Reportes y Consultas sincronizados exitosamente!
echo ========================================================
pause
