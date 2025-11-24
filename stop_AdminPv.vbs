Set WshShell = CreateObject("WScript.Shell")
' Mata todos los procesos PHP (Laravel)
WshShell.Run "taskkill /IM php.exe /F", 0, True
