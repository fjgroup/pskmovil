@echo off
title Detener Sistema Comandas Moviles
echo ========================================================
echo   DETENIENDO SERVICIOS EN SEGUNDO PLANO
echo ========================================================
echo.

powershell -NoProfile -Command ^
    "$procs = Get-WmiObject Win32_Process | Where-Object { $_.Name -in 'php.exe', 'cmd.exe' };" ^
    "foreach ($p in $procs) {" ^
    "    if ($p.CommandLine -match ':2010|sync_daemon\.php|servidor_impresora_virtual|iniciar_daemon\.bat') {" ^
    "        Stop-Process -Id $p.ProcessId -Force -ErrorAction SilentlyContinue;" ^
    "        Write-Host ('  [OK] Proceso detenido (PID ' + $p.ProcessId + ')' ) -ForegroundColor Yellow;" ^
    "    }" ^
    "}"

taskkill /F /IM cmonitor.exe >nul 2>&1
echo.
echo [OK] Todos los servicios de Comandas Moviles fueron detenidos.
timeout /t 2 >nul
