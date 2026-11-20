@echo off
setlocal EnableExtensions
chcp 65001 > nul
cd /d "%~dp0"

echo ===================================================
echo          Criar Atalho na Area de Trabalho
echo                  Site Gerencial
echo ===================================================
echo.

powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\criar-atalho.ps1" -DestinationDir "%~1"
set "EXIT_CODE=%ERRORLEVEL%"

if not "%EXIT_CODE%"=="0" (
    echo.
    echo [ERRO] Ocorreu uma falha ao criar o atalho.
)

echo.
pause
exit /b %EXIT_CODE%
