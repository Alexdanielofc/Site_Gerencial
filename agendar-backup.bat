@echo off
setlocal EnableExtensions
chcp 65001 > nul
cd /d "%~dp0"

echo ===================================================
echo        Agendamento de Backup Automatico
echo                 Site Gerencial
echo ===================================================
echo.

powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\agendar-backup.ps1"
set "EXIT_CODE=%ERRORLEVEL%"

if not "%EXIT_CODE%"=="0" (
    echo.
    echo [ERRO] Ocorreu uma falha ao registrar a tarefa agendada.
    echo Se necessario, execute este arquivo como Administrador.
)

echo.
pause
exit /b %EXIT_CODE%
