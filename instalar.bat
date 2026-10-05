@echo off
chcp 65001 >nul
title Instalador Meu Cash
cd /d "%~dp0"

where php >nul 2>nul
if errorlevel 1 set "PATH=C:\xampp\php;%PATH%"
where php >nul 2>nul
if errorlevel 1 (
    echo [ERRO] PHP nao encontrado. Instale o XAMPP em C:\xampp ou adicione o PHP ao PATH.
    pause
    exit /b 1
)

where composer >nul 2>nul
if errorlevel 1 (
    echo [ERRO] Composer nao encontrado. Instale em https://getcomposer.org e abra este arquivo de novo.
    pause
    exit /b 1
)

echo.
echo === 1/3 Instalando dependencias (pode demorar alguns minutos) ===
call composer install --no-interaction --optimize-autoloader
if errorlevel 1 (
    echo [ERRO] Falha no composer install. Veja a mensagem acima.
    pause
    exit /b 1
)

echo.
echo === 2/3 Criando arquivo .env ===
if not exist ".env" copy ".env.example" ".env" >nul

echo.
echo === 3/3 Criando banco, tabelas e usuario administrador ===
echo Certifique-se de que o MySQL esta INICIADO no XAMPP Control Panel.
php artisan meucash:instalar
if errorlevel 1 (
    echo [ERRO] A instalacao nao terminou. Inicie o MySQL no XAMPP e rode este arquivo de novo.
    pause
    exit /b 1
)

echo.
echo Pronto! Configure o Virtual Host (veja LEIA-ME.md) e acesse http://meucash.test
pause
