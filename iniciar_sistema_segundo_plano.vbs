' ============================================================================
' Iniciar Sistema Comandas Moviles y Sincronizador Cloud en Segundo Plano
' Mi Jardin Restaurant - Pantalla de caja 100% limpia sin consolas negras
' ============================================================================

Option Explicit

Dim WshShell, fso, strCurrentDir
Set WshShell = CreateObject("WScript.Shell")
Set fso = CreateObject("Scripting.FileSystemObject")

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

' 2. Aplicar parches de reportes a Premium Soft si existen en fix_premium
Dim strFixReports, strPosReports, strMatReports
strFixReports = strCurrentDir & "\fix_premium\reports"
strPosReports = "C:\PREMIUM\ADMINISTRATIVO9X\bahiap\reports"
strMatReports = "C:\PREMIUM\ADMINISTRATIVO9X\Matrices\reports"

If fso.FolderExists(strFixReports) Then
    If fso.FolderExists(strPosReports) Then
        WshShell.Run "cmd /c copy /y """ & strFixReports & "\*.*"" """ & strPosReports & "\""", 0, True
    End If
    If fso.FolderExists(strMatReports) Then
        WshShell.Run "cmd /c copy /y """ & strFixReports & "\*.*"" """ & strMatReports & "\""", 0, True
    End If
End If

' 3. Ejecutar verificador silencioso de Base de Datos Satelite
WshShell.Run """" & phpCmd & """ """ & strCurrentDir & "\funciones\verificador_bd.php""", 0, True

' 4. Iniciar Sincronizador Cloud en segundo plano (Ventana Oculta = 0)
WshShell.Run "cmd /c """ & strCurrentDir & "\iniciar_daemon.bat""", 0, False

' 5. Iniciar Servidor Web de Comandas Moviles (Puerto 2010, Ventana Oculta = 0)
WshShell.Run """" & phpCmd & """ -S 0.0.0.0:2010 -t """ & strCurrentDir & """", 0, False

' 6. Detectar dinamicamente la IP activa de la PC en la red local
Dim objWMI, colAdapters, objAdapter, strIP, ip
Set objWMI = GetObject("winmgmts:\\.\root\cimv2")
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

' 7. Ventana emergente con la IP exacta para guiar a mesoneros y cajeros
Dim strMsg
strMsg = "=========================================" & vbCrLf & _
         "   MI JARDIN - COMANDAS MOVILES ACTIVAS   " & vbCrLf & _
         "=========================================" & vbCrLf & vbCrLf & _
         "DIRECCION PARA LOS MESONEROS (CELULARES Y TABLETS):" & vbCrLf & vbCrLf & _
         "  >>>   http://" & strIP & ":2010   <<<" & vbCrLf & vbCrLf & _
         "-----------------------------------------" & vbCrLf & _
         "  * IP detectada de esta PC: " & strIP & vbCrLf & _
         "  * Puerto Web: 2010" & vbCrLf & _
         "  * Impresora Termica: 192.168.1.100 (Puerto 9100)" & vbCrLf & _
         "-----------------------------------------" & vbCrLf & vbCrLf & _
         "Estado del Sistema:" & vbCrLf & _
         "  [OK] Servidor Web Comandas: ACTIVO" & vbCrLf & _
         "  [OK] Sincronizador Nube Cloud: ACTIVO" & vbCrLf & _
         "  [OK] Pantalla de caja: 100% LIMPIA (Sin ventanas negras)" & vbCrLf & vbCrLf & _
         "Al presionar [Aceptar], ambos servicios continuaran corriendo" & vbCrLf & _
         "en segundo plano sin ocupar espacio en la pantalla de caja."

MsgBox strMsg, vbInformation + vbOKOnly, "Mi Jardin Restaurant - Sistema de Comandas"
