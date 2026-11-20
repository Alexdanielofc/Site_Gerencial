@echo off
setlocal EnableExtensions EnableDelayedExpansion
chcp 65001 > nul
cd /d "%~dp0"

set "AUTO_MODE=0"
if /i "%~1"=="auto" set "AUTO_MODE=1"

if not exist "backups" mkdir "backups"
set "LOG_FILE=backups\backup.log"

if "%AUTO_MODE%"=="0" (
    echo ===================================================
    echo           Backup do Banco de Dados
    echo               Site Gerencial
    echo ===================================================
    echo.
)

REM 1) Timestamp via PowerShell (yyyy-MM-dd_HHmm)
set "TIMESTAMP="
for /f "usebackq delims=" %%T in (`powershell -NoProfile -Command "Get-Date -Format 'yyyy-MM-dd_HHmm'"`) do set "TIMESTAMP=%%T"
if not defined TIMESTAMP (
    call :Log "[ERRO] Nao foi possivel obter a data/hora via PowerShell."
    goto :Fail
)

set "BACKUP_FILE=backups\site_gerencial_%TIMESTAMP%.sql"
set "CONF_FILE=%USERPROFILE%\.site_gerencial_mysql.cnf"

REM 2) Verificar arquivo de credenciais fora do projeto (nunca criar com root)
if not exist "%CONF_FILE%" (
    (
        echo [client]
        echo user=site_gerencial
        echo password=COLOQUE_A_SENHA_AQUI
        echo host=127.0.0.1
        echo port=3306
    ) > "%CONF_FILE%"
    call :Log "[ACAO NECESSARIA] Arquivo modelo de credenciais criado em: %CONF_FILE%"
    call :Log "Edite esse arquivo e substitua COLOQUE_A_SENHA_AQUI pela senha real do usuario do MySQL antes de executar o backup novamente."
    goto :Fail
)

findstr /i /c:"COLOQUE_A_SENHA_AQUI" "%CONF_FILE%" >nul 2>&1
if %ERRORLEVEL% EQU 0 (
    call :Log "[ERRO] O arquivo de credenciais %CONF_FILE% ainda contem o marcador COLOQUE_A_SENHA_AQUI."
    call :Log "Abra o arquivo e informe a senha real do banco antes de gerar o backup."
    goto :Fail
)

REM 3) Localizar mysqldump no PATH ou em C:\Program Files\MySQL\MySQL Server *\bin
set "MYSQLDUMP_EXE="
for /f "usebackq delims=" %%M in (`powershell -NoProfile -Command ^
    "$cmd = Get-Command mysqldump -ErrorAction SilentlyContinue; " ^
    "if ($cmd) { Write-Output $cmd.Source; exit 0 }; " ^
    "$found = Get-ChildItem -Path 'C:\Program Files\MySQL\MySQL Server *\bin\mysqldump.exe' -ErrorAction SilentlyContinue | Sort-Object FullName -Descending | Select-Object -First 1; " ^
    "if ($found) { Write-Output $found.FullName; exit 0 }; " ^
    "exit 1"`) do set "MYSQLDUMP_EXE=%%M"

if not defined MYSQLDUMP_EXE (
    call :Log "[ERRO] O utilitario mysqldump.exe nao foi encontrado no PATH nem em C:\Program Files\MySQL\MySQL Server *\bin."
    goto :Fail
)

call :Log "Gerando dump do banco 'site_gerencial' em %BACKUP_FILE% ..."
"%MYSQLDUMP_EXE%" --defaults-extra-file="%CONF_FILE%" --single-transaction --routines --triggers --default-character-set=utf8mb4 site_gerencial > "%BACKUP_FILE%" 2>nul
set "DUMP_EXIT=%ERRORLEVEL%"

REM 4) Validar arquivo (> 0 bytes e contendo 'Dump completed')
powershell -NoProfile -Command ^
    "$f = '%BACKUP_FILE%'; " ^
    "if (-not (Test-Path $f)) { exit 1 }; " ^
    "$item = Get-Item $f; " ^
    "if ($item.Length -le 0) { exit 2 }; " ^
    "$tail = Get-Content $f -Tail 10 -ErrorAction SilentlyContinue | Out-String; " ^
    "if ($tail -notmatch 'Dump completed') { exit 3 }; " ^
    "exit 0"
set "VALID_STATUS=%ERRORLEVEL%"

if not "%DUMP_EXIT%"=="0" set "VALID_STATUS=9"

if not "%VALID_STATUS%"=="0" goto :InvalidDump

call :Log "[SUCESSO] Backup gravado e validado com sucesso em: %BACKUP_FILE%"

REM 5) Rotacao: manter apenas os 30 backups mais recentes
powershell -NoProfile -Command ^
    "Get-ChildItem -Path 'backups\site_gerencial_*.sql' -ErrorAction SilentlyContinue | " ^
    "Sort-Object LastWriteTime -Descending | " ^
    "Select-Object -Skip 30 | " ^
    "ForEach-Object { Remove-Item $_.FullName -Force }"

goto :Success

:InvalidDump
if exist "%BACKUP_FILE%" del /f /q "%BACKUP_FILE%" >nul 2>&1
call :Log "[ERRO] Falha ao gerar ou validar o backup - codigo %VALID_STATUS%. O arquivo parcial foi removido."
call :Log "Verifique se o servico MySQL esta rodando e se as credenciais em %CONF_FILE% estao corretas."
goto :Fail

:Success
if "%AUTO_MODE%"=="1" exit /b 0
echo.
pause
exit /b 0

:Fail
if "%AUTO_MODE%"=="1" exit /b 1
echo.
pause
exit /b 1

:Log
set "MSG=%~1"
if "%AUTO_MODE%"=="1" (
    echo [%DATE% %TIME%] !MSG!>> "%LOG_FILE%"
) else (
    echo !MSG!
)
exit /b 0
