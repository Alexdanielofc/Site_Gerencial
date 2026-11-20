[CmdletBinding()]
param(
    [string]$DestinationDir = '',
    [string]$ShortcutName = 'Site Gerencial.lnk',
    [string]$Url = 'http://localhost:8000',
    [string]$ProjectDir = ''
)

$ErrorActionPreference = 'Stop'

if (-not $ProjectDir) {
    $ProjectDir = Split-Path -Path $PSScriptRoot -Parent
}

if (-not $DestinationDir) {
    $DestinationDir = [System.Environment]::GetFolderPath('Desktop')
}

if (-not (Test-Path -LiteralPath $DestinationDir)) {
    New-Item -ItemType Directory -Path $DestinationDir -Force | Out-Null
}

$iconPath = Join-Path $ProjectDir 'public\icons\site-gerencial.ico'
if (-not (Test-Path -LiteralPath $iconPath)) {
    $genScript = Join-Path $PSScriptRoot 'gerar-icone.ps1'
    if (Test-Path -LiteralPath $genScript) {
        & $genScript -ProjectDir $ProjectDir
    }
}

$shortcutPath = Join-Path $DestinationDir $ShortcutName

$wscript = New-Object -ComObject WScript.Shell
$shortcut = $wscript.CreateShortcut($shortcutPath)
$shortcut.TargetPath = 'cmd.exe'
$shortcut.Arguments = "/c start `"`" `"$Url`""
$shortcut.WorkingDirectory = $ProjectDir
if (Test-Path -LiteralPath $iconPath) {
    $shortcut.IconLocation = "$iconPath, 0"
}
$shortcut.Description = 'Site Gerencial - Sistema de Gestao Integrado'
$shortcut.Save()

Write-Host "[SUCESSO] Atalho criado com sucesso!" -ForegroundColor Green
Write-Host "  - Arquivo: $shortcutPath"
Write-Host "  - Destino: $($shortcut.TargetPath) $($shortcut.Arguments)"
Write-Host "  - Icone: $($shortcut.IconLocation)"
Write-Host "  - Pasta de trabalho: $($shortcut.WorkingDirectory)"
