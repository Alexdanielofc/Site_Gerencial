@echo off
setlocal EnableExtensions EnableDelayedExpansion
chcp 65001 > nul
cd /d "%~dp0"

echo ===================================================
echo           Iniciando o Site Gerencial
echo ===================================================
echo.

REM 1) Confirmar que .env e vendor existem
if not exist ".env" (
    echo [ERRO] O arquivo .env nao foi encontrado na pasta do projeto.
    echo Copie .env.example para .env e configure o banco de dados antes de iniciar.
    echo.
    pause
    exit /b 1
)

if not exist "vendor\autoload.php" (
    echo [ERRO] A pasta vendor nao foi encontrada.
    echo Execute "composer install" no terminal antes de iniciar o sistema.
    echo.
    pause
    exit /b 1
)

REM 2) Localizar o servico MySQL e checar se esta RUNNING
echo Verificando servico MySQL...
powershell -NoProfile -Command ^
    "$svc = Get-Service -Name 'MySQL*' -ErrorAction SilentlyContinue | Select-Object -First 1; " ^
    "if (-not $svc) { exit 2 }; " ^
    "if ($svc.Status -eq 'Running') { Write-Host ('[OK] Servico ' + $svc.Name + ' esta em execucao.'); exit 0 }; " ^
    "Write-Host ('[AVISO] Servico ' + $svc.Name + ' esta parado. Tentando iniciar...'); " ^
    "try { Start-Service -InputObject $svc -ErrorAction Stop; Write-Host ('[OK] Servico ' + $svc.Name + ' iniciado com sucesso.'); exit 0 } catch { exit 3 }"
set MYSQL_STATUS=%ERRORLEVEL%

if %MYSQL_STATUS% EQU 2 (
    echo [ERRO] Nenhum servico MySQL foi encontrado no Windows.
    echo Verifique se o MySQL Server esta instalado e configurado como servico.
    echo.
    pause
    exit /b 1
)

if %MYSQL_STATUS% EQU 3 (
    echo [ERRO] O servico MySQL esta parado e nao foi possivel inicia-lo automaticamente.
    echo Execute este script como Administrador ou inicie o servico MySQL manualmente em Servicos do Windows ^(services.msc^).
    echo.
    pause
    exit /b 1
)

echo.

REM 3) Avisar se public/build/manifest.json nao existe
if not exist "public\build\manifest.json" (
    echo [AVISO] Os arquivos visuais compilados nao foram encontrados ^(public\build\manifest.json^).
    echo Execute "npm run build" para compilar o front-end.
    echo.
)

REM 4) Avisar, sem mostrar valores, se o .env tem APP_DEBUG=true ou APP_ENV=local
powershell -NoProfile -Command ^
    "$lines = Get-Content '.env' -ErrorAction SilentlyContinue; " ^
    "$isDebug = $lines | Where-Object { $_ -match '^\s*APP_DEBUG\s*=\s*(true|1|\"true\")\s*$' }; " ^
    "$isLocal = $lines | Where-Object { $_ -match '^\s*APP_ENV\s*=\s*(local|\"local\")\s*$' }; " ^
    "if ($isDebug -or $isLocal) { Write-Host '[AVISO] O arquivo .env esta configurado com APP_ENV=local e/ou APP_DEBUG=true. Para uso em producao local, recomenda-se APP_ENV=production e APP_DEBUG=false.' }"

echo.
echo Iniciando o servidor web local em janela propria ^(http://127.0.0.1:8000^)...
start "Site Gerencial - Servidor" cmd /k "cd /d "%~dp0" && php artisan serve --host=127.0.0.1 --port=8000"

REM 6) Esperar a porta 8000 responder (ate 20 s) antes de abrir o navegador
echo Aguardando o servidor responder ^(ate 20 segundos^)...
powershell -NoProfile -Command ^
    "$deadline = (Get-Date).AddSeconds(20); " ^
    "while ((Get-Date) -lt $deadline) { " ^
    "    try { " ^
    "        $c = New-Object System.Net.Sockets.TcpClient; " ^
    "        $iar = $c.BeginConnect('127.0.0.1', 8000, $null, $null); " ^
    "        if ($iar.AsyncWaitHandle.WaitOne(500) -and $c.Connected) { $c.Close(); exit 0 }; " ^
    "        $c.Close(); " ^
    "    } catch {} " ^
    "    Start-Sleep -Milliseconds 500; " ^
    "} " ^
    "exit 1"

if %ERRORLEVEL% EQU 0 (
    echo [OK] Servidor online! Abrindo navegador em http://127.0.0.1:8000 ...
    start "" "http://127.0.0.1:8000"
) else (
    echo [ERRO] O servidor nao respondeu em http://127.0.0.1:8000 apos 20 segundos.
    echo Verifique a janela "Site Gerencial - Servidor" para identificar a causa.
)

echo.
pause
