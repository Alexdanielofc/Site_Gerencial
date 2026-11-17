@echo off
chcp 65001 > nul
echo ===================================================
echo           Backup do Banco de Dados
echo               Site Gerencial
echo ===================================================
echo.

REM Garante a pasta backups
if not exist "backups" mkdir backups

REM Gera timestamp no formato AAAA-MM-DD_HHMM
for /f "tokens=2 delims==" %%I in ('wmic os get localdatetime /value 2^>nul') do set datetime=%%I
if defined datetime (
    set ANO=%datetime:~0,4%
    set MES=%datetime:~4,2%
    set DIA=%datetime:~6,2%
    set HORA=%datetime:~8,2%
    set MIN=%datetime:~10,2%
) else (
    set ANO=%date:~6,4%
    set MES=%date:~3,2%
    set DIA=%date:~0,2%
    set HORA=%time:~0,2%
    set MIN=%time:~3,2%
)
set TIMESTAMP=%ANO%-%MES%-%DIA%_%HORA%%MIN%
set BACKUP_FILE=backups\site_gerencial_%TIMESTAMP%.sql

set CONF_FILE=%USERPROFILE%\.site_gerencial_mysql.cnf

if not exist "%CONF_FILE%" (
    echo [INFO] Arquivo de credenciais seguro não encontrado em %CONF_FILE%
    echo Criando modelo seguro de credenciais fora do repositório...
    (
        echo [client]
        echo user=root
        echo password=
        echo host=127.0.0.1
        echo port=3306
    ) > "%CONF_FILE%"
    echo Arquivo criado em: %CONF_FILE%
    echo Configure seu usuário e senha nele se necessário.
    echo.
)

echo Gerando dump do banco 'site_gerencial'...
mysqldump --defaults-extra-file="%CONF_FILE%" --single-transaction --default-character-set=utf8mb4 site_gerencial > "%BACKUP_FILE%"

if %ERRORLEVEL% EQU 0 (
    echo [SUCESSO] Backup gravado com sucesso em:
    echo %BACKUP_FILE%
) else (
    echo [ERRO] Ocorreu uma falha ao gerar o backup.
    echo Verifique se o MySQL está rodando e se as credenciais em %CONF_FILE% estão corretas.
)

echo.
pause
