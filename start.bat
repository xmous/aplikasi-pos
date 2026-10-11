@echo off
title POS Stock - Server Lokal
color 0B
cd /D "%~dp0"

echo ================================================================
echo            MENJALANKAN POS STOCK (LOCAL SERVER)
echo ================================================================
echo.

:: 1. Deteksi PHP
where php >nul 2>nul
if %errorlevel% neq 0 (
    if exist "C:\xampp\php\php.exe" set "PATH=C:\xampp\php;%PATH%"
    if exist "D:\xampp\php\php.exe" set "PATH=D:\xampp\php;%PATH%"
)

:: 2. Cek & Otomatis Nyalakan MySQL XAMPP jika belum aktif
netstat -ano | findstr ":3306 " >nul 2>nul
if %errorlevel% neq 0 (
    echo [INFO] Menyalakan MySQL XAMPP di background...
    if exist "C:\xampp\mysql_start.bat" (
        start /min "" cmd /c "cd /D C:\xampp && mysql_start.bat"
        timeout /t 2 /nobreak >nul
    ) else if exist "D:\xampp\mysql_start.bat" (
        start /min "" cmd /c "cd /D D:\xampp && mysql_start.bat"
        timeout /t 2 /nobreak >nul
    ) else (
        echo [CATATAN] Pastikan MySQL di XAMPP Control Panel sudah aktif!
    )
)

echo [OK] Menyiapkan server dan membuka browser ke http://localhost:8000 ...
start "" cmd /c "timeout /t 2 /nobreak >nul && start http://localhost:8000"

echo.
echo Server aktif di http://127.0.0.1:8000
echo Tekan tombol Ctrl+C untuk mematikan server.
echo.

php artisan serve --host=127.0.0.1 --port=8000

pause
