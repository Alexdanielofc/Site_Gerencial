@echo off
setlocal EnableExtensions
chcp 65001 > nul
cd /d "%~dp0"

echo ===================================================
echo      Iniciando o Site Gerencial (Modo Demo)
echo ===================================================
echo.

if not exist ".env.demo" (
    echo [ERRO] O arquivo .env.demo nao foi encontrado na pasta do projeto.
    echo Copie .env.demo.example para .env.demo e configure o banco "site_gerencial_demo".
    echo.
    pause
    exit /b 1
)

if not exist "vendor\autoload.php" (
    echo [ERRO] A pasta vendor nao foi encontrada.
    echo Execute "composer install" no terminal antes de iniciar.
    echo.
    pause
    exit /b 1
)

set APP_ENV=demo

echo Iniciando o servidor de demonstracao em janela propria ^(http://127.0.0.1:8001^)...
start "Site Gerencial - Servidor Demo" cmd /k "cd /d "%~dp0" && set APP_ENV=demo && php artisan serve --host=127.0.0.1 --port=8001 --env=demo"

echo Aguardando o servidor responder ^(ate 20 segundos^)...
powershell -NoProfile -Command ^
    "$deadline = (Get-Date).AddSeconds(20); " ^
    "while ((Get-Date) -lt $deadline) { " ^
    "    try { " ^
    "        $c = New-Object System.Net.Sockets.TcpClient; " ^
    "        $iar = $c.BeginConnect('127.0.0.1', 8001, $null, $null); " ^
    "        if ($iar.AsyncWaitHandle.WaitOne(500) -and $c.Connected) { $c.Close(); exit 0 }; " ^
    "        $c.Close(); " ^
    "    } catch {} " ^
    "    Start-Sleep -Milliseconds 500; " ^
    "} " ^
    "exit 1"

if %ERRORLEVEL% EQU 0 (
    echo [OK] Servidor Demo online! Abrindo navegador em http://127.0.0.1:8001 ...
    start "" "http://127.0.0.1:8001"
) else (
    echo [ERRO] O servidor Demo nao respondeu em http://127.0.0.1:8001 apos 20 segundos.
    echo Verifique a janela "Site Gerencial - Servidor Demo" para identificar a causa.
)

echo.
pause
