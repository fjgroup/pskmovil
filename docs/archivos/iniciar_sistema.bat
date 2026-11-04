@echo off
:: Redireccion silenciosa al iniciador VBS en segundo plano
:: Evita ventanas negras en la pantalla de caja y muestra la IP a los mesoneros
start "" wscript.exe "%~dp0iniciar_sistema_segundo_plano.vbs"
exit
