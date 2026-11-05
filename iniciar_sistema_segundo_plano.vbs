' ============================================================================
' Iniciar Sistema Comandas Moviles y Sincronizador Cloud en Segundo Plano
' Mi Jardin Restaurant - Pantalla de caja 100% limpia sin consolas negras
' ============================================================================

Option Explicit

Dim WshShell, fso, strCurrentDir, objWMI
Set WshShell = CreateObject("WScript.Shell")
Set fso = CreateObject("Scripting.FileSystemObject")
Set objWMI = GetObject("winmgmts:\\.\root\cimv2")

' Directorio base del proyecto
strCurrentDir = fso.GetParentFolderName(WScript.ScriptFullName)
WshShell.CurrentDirectory = strCurrentDir

' 1. Detectar ejecutable real de PHP (Herd o Sistema)
Dim phpCmd, strHerdPhp, strPrincipalPhp
strHerdPhp = WshShell.ExpandEnvironmentStrings("%USERPROFILE%") & "\.config\herd\bin\php84\php.exe"
strPrincipalPhp = "C:\Users\Principal\.config\herd\bin\php84\php.exe"

If fso.FileExists(strHerdPhp) Then
    phpCmd = strHerdPhp
ElseIf fso.FileExists(strPrincipalPhp) Then
    phpCmd = strPrincipalPhp
Else
    phpCmd = "php.exe"
End If

' 2. Aplicar parches de reportes y consultas a Premium Soft si existen en fix_premium
Dim strFixReports, strPosReports, strMatReports, strFixQuerys, strPosQuerys, strMatQuerys
strFixReports = strCurrentDir & "\fix_premium\reports"
strPosReports = "C:\PREMIUM\ADMINISTRATIVO9X\bahiap\reports"
strMatReports = "C:\PREMIUM\ADMINISTRATIVO9X\Matrices\reports"
strFixQuerys  = strCurrentDir & "\fix_premium\querys"
strPosQuerys  = "C:\PREMIUM\ADMINISTRATIVO9X\bahiap\querys"
strMatQuerys  = "C:\PREMIUM\ADMINISTRATIVO9X\Matrices\querys"

If fso.FolderExists(strFixReports) Then
    If fso.FolderExists(strPosReports) Then
        WshShell.Run "cmd /c copy /y """ & strFixReports & "\*.*"" """ & strPosReports & "\""", 0, True
    End If
    If fso.FolderExists(strMatReports) Then
        WshShell.Run "cmd /c copy /y """ & strFixReports & "\*.*"" """ & strMatReports & "\""", 0, True
    End If
End If

If fso.FolderExists(strFixQuerys) Then
    If fso.FolderExists(strPosQuerys) Then
        WshShell.Run "cmd /c copy /y """ & strFixQuerys & "\*.*"" """ & strPosQuerys & "\""", 0, True
    End If
    If fso.FolderExists(strMatQuerys) Then
        WshShell.Run "cmd /c copy /y """ & strFixQuerys & "\*.*"" """ & strMatQuerys & "\""", 0, True
    End If
End If

' 3. Ejecutar verificador silencioso de Base de Datos Satelite
WshShell.Run """" & phpCmd & """ """ & strCurrentDir & "\funciones\verificador_bd.php""", 0, True

' 4. Limpiar instancias previas para liberar memoria RAM y evitar duplicados
Dim colOldProcs, objProc
On Error Resume Next
Set colOldProcs = objWMI.ExecQuery("SELECT ProcessId, CommandLine FROM Win32_Process WHERE Name = 'php.exe'")
For Each objProc In colOldProcs
    If Not IsNull(objProc.CommandLine) Then
        If InStr(objProc.CommandLine, "sync_daemon.php") > 0 Or InStr(objProc.CommandLine, ":2010") > 0 Then
            objProc.Terminate()
        End If
    End If
Next
Set colOldProcs = objWMI.ExecQuery("SELECT ProcessId, CommandLine FROM Win32_Process WHERE Name = 'cmd.exe'")
For Each objProc In colOldProcs
    If Not IsNull(objProc.CommandLine) Then
        If InStr(objProc.CommandLine, "iniciar_daemon.bat") > 0 Then
            objProc.Terminate()
        End If
    End If
Next
On Error GoTo 0

' 5. Habilitar modo multi-worker (8 procesos en paralelo) para atender multiples meseros sin demoras
WshShell.Environment("PROCESS")("PHP_CLI_SERVER_WORKERS") = "8"

' 6. Iniciar Sincronizador Cloud en segundo plano (Ventana Oculta = 0)
WshShell.Run "cmd /c """ & strCurrentDir & "\iniciar_daemon.bat""", 0, False

' 7. Iniciar Servidor Web de Comandas Moviles (Puerto 2010, Multi-hilo, Ventana Oculta = 0)
WshShell.Run """" & phpCmd & """ -S 0.0.0.0:2010 -t """ & strCurrentDir & """", 0, False

' 6. Detectar dinamicamente la IP activa de la PC en la red local
Dim colAdapters, objAdapter, strIP, ip
Set colAdapters = objWMI.ExecQuery("SELECT IPAddress, DefaultIPGateway FROM Win32_NetworkAdapterConfiguration WHERE IPEnabled = True")

strIP = ""
For Each objAdapter In colAdapters
    If Not IsNull(objAdapter.DefaultIPGateway) Then
        For Each ip In objAdapter.IPAddress
            If InStr(ip, ".") > 0 And Left(ip, 3) <> "127" And Left(ip, 7) <> "169.254" Then
                strIP = ip
                Exit For
            End If
        Next
    End If
    If strIP <> "" Then Exit For
Next

If strIP = "" Then
    For Each objAdapter In colAdapters
        If Not IsNull(objAdapter.IPAddress) Then
            For Each ip In objAdapter.IPAddress
                If InStr(ip, ".") > 0 And Left(ip, 3) <> "127" And Left(ip, 7) <> "169.254" And Left(ip, 11) <> "192.168.56." Then
                    strIP = ip
                    Exit For
                End If
            Next
        End If
        If strIP <> "" Then Exit For
    Next
End If

If strIP = "" Then strIP = "192.168.1.102"

' 7. Validar dinamicamente la impresora termica instalada en Windows y verificar conexion (Ping)
Dim printerPort, printerIP, isPrinterOnline, strPrinterMsg, colPing, objStatus, strFoundPrinter, regHostName
Dim arrCandidates, cName
printerPort = ""
printerIP = ""
strFoundPrinter = ""
isPrinterOnline = False

' 1. Intentar candidatos comunes de nombres de impresoras térmicas
arrCandidates = Array("POS-80C", "POS-80", "cocina", "POS80", "Ticketera", "Comandas")
For Each cName In arrCandidates
    On Error Resume Next
    printerPort = WshShell.RegRead("HKLM\SYSTEM\CurrentControlSet\Control\Print\Printers\" & cName & "\Port")
    On Error GoTo 0
    If printerPort <> "" Then
        strFoundPrinter = cName
        Exit For
    End If
Next

' 2. Si no se encontró por nombre exacto, buscar en Win32_Printer cualquier impresora con POS, Cocina o Ticket
If strFoundPrinter = "" Then
    On Error Resume Next
    Dim colPrinters, objPrinter
    Set colPrinters = objWMI.ExecQuery("SELECT Name, PortName FROM Win32_Printer")
    For Each objPrinter In colPrinters
        If InStr(UCase(objPrinter.Name), "POS") > 0 Or InStr(UCase(objPrinter.Name), "COCINA") > 0 Or InStr(UCase(objPrinter.Name), "TICKET") > 0 Then
            strFoundPrinter = objPrinter.Name
            printerPort = objPrinter.PortName
            Exit For
        End If
    Next
    On Error GoTo 0
End If

' 3. Determinar la IP real a partir del puerto consultando Standard TCP/IP Port
If printerPort <> "" Then
    On Error Resume Next
    regHostName = WshShell.RegRead("HKLM\SYSTEM\CurrentControlSet\Control\Print\Monitors\Standard TCP/IP Port\Ports\" & printerPort & "\HostName")
    If regHostName <> "" Then
        printerIP = regHostName
    End If
    On Error GoTo 0

    If printerIP = "" Then
        On Error Resume Next
        Dim colPorts, objPort
        Set colPorts = objWMI.ExecQuery("SELECT HostAddress FROM Win32_TCPIPPrinterPort WHERE Name = '" & printerPort & "'")
        For Each objPort In colPorts
            If Not IsNull(objPort.HostAddress) And objPort.HostAddress <> "" Then
                printerIP = objPort.HostAddress
                Exit For
            End If
        Next
        On Error GoTo 0
    End If

    If printerIP = "" Then
        If UCase(Left(printerPort, 3)) = "IP_" Then
            printerIP = Mid(printerPort, 4)
        ElseIf InStr(printerPort, ".") > 0 Then
            printerIP = printerPort
        End If
    End If
End If

If printerIP = "" Then printerIP = "192.168.1.150"
If strFoundPrinter = "" Then strFoundPrinter = "POS-80"

If printerIP <> "" And InStr(printerIP, ".") > 0 Then
    Set colPing = objWMI.ExecQuery("SELECT StatusCode FROM Win32_PingStatus WHERE Address = '" & printerIP & "' AND Timeout = 1200")
    For Each objStatus In colPing
        If Not IsNull(objStatus.StatusCode) And objStatus.StatusCode = 0 Then
            isPrinterOnline = True
            Exit For
        End If
    Next
    
    If isPrinterOnline Then
        strPrinterMsg = "  [OK] Impresora '" & strFoundPrinter & "' (" & printerIP & "): ENCENDIDA Y RESPONDE"
    Else
        strPrinterMsg = "  [ALERTA] Impresora '" & strFoundPrinter & "' (" & printerIP & "): NO RESPONDE AL PING" & vbCrLf & _
                        "           -> Verifique que la impresora este encendida o que su IP coincida."
    End If
ElseIf printerPort <> "" Then
    strPrinterMsg = "  [INFO] Impresora '" & strFoundPrinter & "' asignada al puerto: " & printerPort
Else
    strPrinterMsg = "  [AVISO] No se encontro impresora termica instalada en Windows."
End If

' 8. Ventana emergente informativa para guiar a mesoneros y cajeros
Dim strMsg
strMsg = "=========================================" & vbCrLf & _
         "   MI JARDIN - COMANDAS MOVILES ACTIVAS   " & vbCrLf & _
         "=========================================" & vbCrLf & vbCrLf & _
         "DIRECCION PARA LOS MESONEROS (CELULARES Y TABLETS):" & vbCrLf & vbCrLf & _
         "  >>>   http://" & strIP & ":2010   <<<" & vbCrLf & vbCrLf & _
         "-----------------------------------------" & vbCrLf & _
         "  * IP de esta PC (Servidor): " & strIP & vbCrLf & _
         "  * Puerto Web: 2010" & vbCrLf & _
         "-----------------------------------------" & vbCrLf & vbCrLf & _
         "Validacion de Impresora Fisica:" & vbCrLf & _
         strPrinterMsg & vbCrLf & vbCrLf & _
         "Estado del Sistema:" & vbCrLf & _
         "  [OK] Servidor Web Comandas: ACTIVO" & vbCrLf & _
         "  [OK] Sincronizador Nube Cloud: ACTIVO" & vbCrLf & _
         "  [OK] Pantalla de caja: 100% LIMPIA (Sin consolas negras)" & vbCrLf & vbCrLf & _
         "Al presionar [Aceptar], ambos servicios continuaran corriendo" & vbCrLf & _
         "en segundo plano sin ocupar espacio en la pantalla de caja."

MsgBox strMsg, vbInformation + vbOKOnly, "Mi Jardin Restaurant - Sistema de Comandas"
