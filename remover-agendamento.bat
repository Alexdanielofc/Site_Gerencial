@echo off
setlocal EnableExtensions
chcp 65001 > nul
cd /d "%~dp0"

echo ===================================================
echo        Remover Agendamento de Backup
echo               Site Gerencial
echo ===================================================
echo.

powershell -NoProfile -Command ^
    "$task = Get-ScheduledTask -TaskName 'SiteGerencial-Backup' -ErrorAction SilentlyContinue; " ^
    "if (-not $task) { exit 2 }; " ^
    "try { Unregister-ScheduledTask -TaskName 'SiteGerencial-Backup' -Confirm:$false -ErrorAction Stop; exit 0 } catch { exit 1 }"
set STATUS=%ERRORLEVEL%

if %STATUS% EQU 0 (
    echo [SUCESSO] A tarefa agendada "SiteGerencial-Backup" foi removida com sucesso.
) else if %STATUS% EQU 2 (
    echo [AVISO] A tarefa "SiteGerencial-Backup" nao estava agendada neste computador.
) else (
    echo [ERRO] Nao foi possivel remover a tarefa agendada.
    echo Clique com o botao direito em "remover-agendamento.bat" e selecione "Executar como administrador".
)

echo.
pause
