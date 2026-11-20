[CmdletBinding()]
param(
    [string]$TaskName = 'SiteGerencial-Backup',
    [string]$Horario = '20:00',
    [string]$ProjectDir = ''
)

$ErrorActionPreference = 'Stop'

if (-not $ProjectDir) {
    $ProjectDir = Split-Path -Path $PSScriptRoot -Parent
}

Write-Host "Configurando tarefa agendada '$TaskName'..." -ForegroundColor Cyan

$backupScript = Join-Path $ProjectDir 'backup.bat'
if (-not (Test-Path -LiteralPath $backupScript)) {
    throw "O script backup.bat nao foi encontrado em: $ProjectDir"
}

$action = New-ScheduledTaskAction -Execute 'cmd.exe' -Argument "/c `"`"$backupScript`" auto`"" -WorkingDirectory $ProjectDir
$trigger = New-ScheduledTaskTrigger -Daily -At $Horario
$settings = New-ScheduledTaskSettingsSet -StartWhenAvailable -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries
$principal = New-ScheduledTaskPrincipal -UserId $env:USERNAME -LogonType Interactive -RunLevel Limited

Register-ScheduledTask `
    -TaskName $TaskName `
    -TaskPath '\' `
    -Action $action `
    -Trigger $trigger `
    -Settings $settings `
    -Principal $principal `
    -Description 'Backup diario automatico do banco de dados do Site Gerencial' `
    -Force | Out-Null

$task = Get-ScheduledTask -TaskPath '\' -TaskName $TaskName -ErrorAction SilentlyContinue
if (-not $task) {
    throw "A tarefa '$TaskName' nao foi encontrada apos a tentativa de registro."
}

$info = Get-ScheduledTaskInfo -TaskName $TaskName -ErrorAction SilentlyContinue

Write-Host "[SUCESSO] Tarefa '$TaskName' agendada com sucesso!" -ForegroundColor Green
Write-Host "  - Caminho (TaskPath): $($task.TaskPath)"
Write-Host "  - Estado: $($task.State)"
Write-Host "  - Horario diario: $Horario (com StartWhenAvailable ativo)"
Write-Host "  - Proxima execucao: $($info.NextRunTime)"
Write-Host "  - Usuario executor: $($env:USERNAME)"
