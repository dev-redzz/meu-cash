@echo off
cd /d "%~dp0"
where php >nul 2>nul
if errorlevel 1 set "PATH=C:\xampp\php;%PATH%"
del /q "app\Http\Controllers\PayableController.php" 2>nul
del /q "app\Http\Controllers\ReceivableController.php" 2>nul
del /q "app\Services\PayableService.php" 2>nul
del /q "app\Http\Requests\PayableRequest.php" 2>nul
del /q "public\img\logo-mark.png" 2>nul
rmdir /s /q "resources\views\payables" 2>nul
rmdir /s /q "resources\views\receivables" 2>nul
call composer dump-autoload -o
php artisan optimize:clear
echo.
echo Atualizacao aplicada. Recarregue o navegador com Ctrl+F5.
pause
