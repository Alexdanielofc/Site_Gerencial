@echo off
setlocal EnableExtensions
chcp 65001 > nul
cd /d "%~dp0"

echo ===================================================
echo        Agendamento de Backup Automatico
echo                 Site Gerencial
echo ===================================================
echo.

set PROJECT_DIR=%~dp0
if "%PROJECT_DIR:~-1%"=="\" set PROJECT_DIR=%PROJECT_DIR:~0,-1%
set BACKUP_SCRIPT=%PROJECT_DIR%\backup.bat

if not exist "%BACKUP_SCRIPT%" (
    echo [ERRO] O script backup.bat nao foi encontrado em %PROJECT_DIR%.
    echo.
    pause
    exit /b 1
)

echo Criando a tarefa agendada "SiteGerencial-Backup" ^(diariamente as 20:00 com StartWhenAvailable^)...
powershell -NoProfile -Command ^
    "try { " ^
    "    $action = New-ScheduledTaskAction -Execute 'cmd.exe' -Argument '/c \"\"%BACKUP_SCRIPT%\" auto\"' -WorkingDirectory '%PROJECT_DIR%'; " ^
    "    $trigger = New-ScheduledTaskTrigger -Daily -At '20:00'; " ^
    "    $settings = New-ScheduledTaskSettingsSet -StartWhenAvailable -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries; " ^
    "    Register-ScheduledTask -TaskName 'SiteGerencial-Backup' -Action $action -Trigger $trigger -Settings $settings -Description 'Backup diario automatico do banco de dados do Site Gerencial' -Force -ErrorAction Stop | Out-Null; " ^
    "    exit 0 " ^
    "} catch { " ^
    "    Write-Host ('[DETALHE] ' + $_.Exception.Message); " ^
    "    exit 1 " ^
    "}"

if %ERRORLEVEL% EQU 0 (
    echo [SUCESSO] A tarefa "SiteGerencial-Backup" foi agendada para rodar todos os dias as 20:00 ^(ou ao ligar o computador se perdeu o horario^).
) else (
    echo [ERRO] Nao foi possivel registrar a tarefa agendada.
    echo Clique com o botao direito em "agendar-backup.bat" e selecione "Executar como administrador".
)

echo.
pause
