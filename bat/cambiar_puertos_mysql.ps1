# Script de reconfiguracion de puertos MySQL
# 1. Detener servicios
Write-Host "Deteniendo servicios MySQL..." -ForegroundColor Cyan
Stop-Service MYSQL84 -Force -ErrorAction SilentlyContinue
Stop-Service MySQL55 -Force -ErrorAction SilentlyContinue

# 2. Modificar my.ini de MySQL 8.4 -> Puerto 3300
$ini84 = "C:\ProgramData\MySQL\MySQL Server 8.4\my.ini"
if (Test-Path $ini84) {
    Write-Host "Modificando MySQL 8.4 my.ini (Puerto 3300)..." -ForegroundColor Yellow
    $content84 = Get-Content $ini84 -Raw
    $content84 = $content84 -replace "(?m)^port\s*=\s*3306\b", "port=3300"
    [System.IO.File]::WriteAllText($ini84, $content84)
}

# 3. Modificar my.ini de MySQL 5.5 -> Puerto 3306
$ini55 = "C:\Program Files (x86)\MySQL\MySQL Server 5.5\my.ini"
if (Test-Path $ini55) {
    Write-Host "Modificando MySQL 5.5 my.ini (Puerto 3306)..." -ForegroundColor Yellow
    $content55 = Get-Content $ini55 -Raw
    $content55 = $content55 -replace "(?m)^port\s*=\s*3307\b", "port=3306"
    [System.IO.File]::WriteAllText($ini55, $content55)
}

# 4. Iniciar servicios
Write-Host "Iniciando servicios MySQL..." -ForegroundColor Cyan
Start-Service MYSQL84
Start-Service MySQL55

# 5. Modificar ODBC en HKCU y HKLM
Write-Host "Actualizando puertos ODBC para Premium Soft..." -ForegroundColor Yellow
$odbcKeys = @(
    "HKCU:\SOFTWARE\ODBC\ODBC.INI\admin403247",
    "HKCU:\SOFTWARE\ODBC\ODBC.INI\adminmatriz",
    "HKCU:\SOFTWARE\ODBC\ODBC.INI\adminbahiap"
)
foreach ($key in $odbcKeys) {
    if (Test-Path $key) {
        Set-ItemProperty -Path $key -Name "PORT" -Value "3306"
    }
}

Write-Host "Completado exitosamente!" -ForegroundColor Green
Start-Sleep -Seconds 2

