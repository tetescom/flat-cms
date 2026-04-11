@echo off
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0build-dist.ps1"
pause
