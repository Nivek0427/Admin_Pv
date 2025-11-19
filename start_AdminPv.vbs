Set WshShell = CreateObject("WScript.Shell")
' Inicia Laravel invisible
WshShell.Run "C:\Users\Alexis\Desktop\Admin_PV\start_AdminPv.bat", 0, False

' Espera 3 segundos para que Laravel arranque
WScript.Sleep 3000

' Abre Microsoft Edge con tu aplicación
WshShell.Run """C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe"" http://127.0.0.1:8000"
