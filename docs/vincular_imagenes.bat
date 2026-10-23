@echo off
setlocal
title Vincular Carpeta Imagenes - PSK Movil
cd /d "%~dp0.."
set "ROOT_DIR=%CD%"
set "IMG_DIR=%ROOT_DIR%\imagenes"
set "TARGET_DIR=C:\Premium\Imagenes"

echo ========================================================
echo        VINCULAR CARPETA DE IMAGENES (PSK MOVIL)
echo ========================================================
echo Directorio del sistema: %ROOT_DIR%
echo Carpeta destino:        %TARGET_DIR%
echo.

if not exist "%TARGET_DIR%" (
    echo [ADVERTENCIA] No se encontro la carpeta destino:
    echo "%TARGET_DIR%"
    echo.
    echo Asegurate de que Premium Restaurant este instalado en C:\Premium
    echo o crea la carpeta C:\Premium\Imagenes antes de continuar.
    echo ========================================================
    pause
    exit /b 1
)

if exist "%IMG_DIR%" (
    fsutil reparsepoint query "%IMG_DIR%" >nul 2>&1
    if errorlevel 1 (
        echo La carpeta 'imagenes' existe como carpeta estatica.
        echo Reemplazandola por el enlace directo a C:\Premium\Imagenes...
        rmdir /s /q "%IMG_DIR%"
    ) else (
        echo Ya existia un enlace previo en 'imagenes'. Actualizando...
        rmdir "%IMG_DIR%"
    )
)

echo Creando enlace simbolico / junction...
mklink /J "%IMG_DIR%" "%TARGET_DIR%"

if errorlevel 1 (
    echo.
    echo [ERROR] No se pudo crear el enlace.
    echo Si estas en una unidad de red o disco sin permisos NTFS, verifica tus permisos.
) else (
    echo.
    echo [EXITO] Enlace creado correctamente!
    echo La carpeta '%IMG_DIR%'
    echo ahora apunta directamente a '%TARGET_DIR%'.
)

echo ========================================================
pause
