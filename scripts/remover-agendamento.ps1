[CmdletBinding()]
param(
    [string]$TaskName = 'SiteGerencial-Backup'
)

$ErrorActionPreference = 'Stop'

Write-Host "Verificando tarefa agendada '$TaskName'..." -ForegroundColor Cyan

$task = Get-ScheduledTask -TaskPath '\' -TaskName $TaskName -ErrorAction SilentlyContinue
if (-not $task) {
    Write-Host "[AVISO] A tarefa '$TaskName' nao esta agendada neste computador." -ForegroundColor Yellow
    exit 0
}

Unregister-ScheduledTask -TaskName $TaskName -Confirm:$false
Write-Host "[SUCESSO] Tarefa '$TaskName' removida com sucesso do Agendador de Tarefas." -ForegroundColor Green
