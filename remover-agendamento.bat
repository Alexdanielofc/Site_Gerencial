@echo off
setlocal EnableExtensions
chcp 65001 > nul
cd /d "%~dp0"

echo ===================================================
echo        Remover Agendamento de Backup
echo               Site Gerencial
echo ===================================================
echo.

powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\remover-agendamento.ps1"
set "EXIT_CODE=%ERRORLEVEL%"

if not "%EXIT_CODE%"=="0" (
    echo.
    echo [ERRO] Ocorreu uma falha ao remover a tarefa agendada.
)

echo.
pause
exit /b %EXIT_CODE%
