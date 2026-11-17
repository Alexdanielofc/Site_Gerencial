@echo off
chcp 65001 > nul
echo ===================================================
echo           Iniciando o Site Gerencial
echo ===================================================
echo.

echo Verificando serviço MySQL...
sc query MySQL >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    sc query MySQL80 >nul 2>&1
    if %ERRORLEVEL% NEQ 0 (
        sc query MySQL90 >nul 2>&1
        if %ERRORLEVEL% NEQ 0 (
            echo [AVISO] O serviço MySQL não foi detectado com nome padrão ou pode estar parado.
            echo Verifique se o MySQL está ativo no sistema antes de prosseguir.
            echo.
        )
    )
)

echo Abrindo navegador em http://127.0.0.1:8000 ...
start http://127.0.0.1:8000

echo Iniciando o servidor web local...
php artisan serve --host=127.0.0.1 --port=8000
pause
