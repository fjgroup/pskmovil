' Iniciar Sistema Comandas Moviles y Sincronizador Cloud en Segundo Plano (Sin Ventanas Negras)
' Mi Jardin Restaurant - Desarrollado para mantener la pantalla de caja 100% limpia.

Set WshShell = CreateObject("WScript.Shell")
Set fso = CreateObject("Scripting.FileSystemObject")

' Directorio del proyecto
strCurrentDir = fso.GetParentFolderName(WScript.ScriptFullName)
WshShell.CurrentDirectory = strCurrentDir

' 1. Detectar ejecutable de PHP
Dim phpCmd
strHerdPhp = WshShell.ExpandEnvironmentStrings("%USERPROFILE%") & "\.config\herd\bin\php84\php.exe"
strPrincipalPhp = "C:\Users\Principal\.config\herd\bin\php84\php.exe"

If fso.FileExists(strHerdPhp) Then
    phpCmd = strHerdPhp
ElseIf fso.FileExists(strPrincipalPhp) Then
    phpCmd = strPrincipalPhp
Else
    phpCmd = "php.exe"
End If

' 2. Aplicar parches de reportes a Premium Soft si existen
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

' Notificacion sutil opcional al usuario
WshShell.Popup "Sistema de Comandas Moviles y Sincronizador Cloud iniciados en segundo plano." & vbCrLf & _
               "Pantalla de caja limpia. No requiere ejecutar cmonitor.exe.", 4, "Mi Jardin Restaurant", 64

