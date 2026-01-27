Set sh = CreateObject("WScript.Shell")

Function EsperarPuerto(puerto)
    Dim result
    Do
        result = sh.Run("cmd /c netstat -ano | find """ & puerto & """", 0, True)
        If result = 0 Then Exit Do
        WScript.Sleep 1000
    Loop
End Function

' --- Esperar a que Apache y MySQL estén activos ---
EsperarPuerto(":80")     ' Apache
EsperarPuerto(":3306")   ' MySQL

' --- Iniciar Laravel ---
sh.Run "C:\Admin_Pv\start_AdminPv.bat", 0, False

' --- Esperar a que Laravel habilite el puerto 8000 ---
EsperarPuerto(":8000")

' --- Abrir Edge ---
sh.Run """C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe"" http://127.0.0.1:8000"


