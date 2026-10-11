@echo off
title [1/2] Installer POS Stock - Setup Laptop Baru
color 0A
cd /D "%~dp0"

echo ================================================================
echo           SETUP APLIKASI POS STOCK (LAPTOP BARU)
echo ================================================================
echo.
echo Lokasi Folder : %CD%
echo.

:: 1. Deteksi PHP
echo [1/5] Memeriksa PHP...
where php >nul 2>nul
if %errorlevel% neq 0 (
    if exist "C:\xampp\php\php.exe" set "PATH=C:\xampp\php;%PATH%"
    if exist "D:\xampp\php\php.exe" set "PATH=D:\xampp\php;%PATH%"
)
where php >nul 2>nul
if %errorlevel% neq 0 (
    echo [ERROR] PHP tidak ditemukan! Pastikan XAMPP sudah terpasang di komputer ini.
    pause
    exit /b 1
)
php -v | findstr /R "^PHP"
echo.

:: 2. Jalankan Composer Install
echo [2/5] Menjalankan composer install...
where composer >nul 2>nul
if %errorlevel% neq 0 (
    echo [ERROR] Composer tidak ditemukan di PATH! Pastikan Composer sudah terinstall.
    pause
    exit /b 1
)
call composer install --no-interaction
if %errorlevel% neq 0 (
    echo [WARNING] Composer install selesai dengan peringatan. Melanjutkan proses...
)
echo.

:: 3. Setup file .env
echo [3/5] Menyiapkan file .env...
if not exist ".env" (
    copy ".env.example" ".env" >nul
    echo [OK] File .env berhasil dibuat.
    php artisan key:generate --force
) else (
    echo [OK] File .env sudah ada.
)
echo.

:: 4. Deteksi MySQL & Import Database Kosongan
echo [4/5] Menyiapkan Database di MySQL XAMPP...
set "MYSQL_CMD=mysql"
where mysql >nul 2>nul
if %errorlevel% neq 0 (
    if exist "C:\xampp\mysql\bin\mysql.exe" set "MYSQL_CMD=C:\xampp\mysql\bin\mysql.exe"
    if exist "D:\xampp\mysql\bin\mysql.exe" set "MYSQL_CMD=D:\xampp\mysql\bin\mysql.exe"
)

echo Menguji koneksi ke MySQL (127.0.0.1:3306)...
"%MYSQL_CMD%" -h 127.0.0.1 -u root -e "SELECT 1;" >nul 2>nul
if %errorlevel% neq 0 (
    echo.
    echo [PERINGATAN] MySQL belum aktif!
    echo Silakan buka XAMPP Control Panel dan klik tombol "Start" pada MySQL.
    echo.
    pause
    "%MYSQL_CMD%" -h 127.0.0.1 -u root -e "SELECT 1;" >nul 2>nul
    if %errorlevel% neq 0 (
        echo [ERROR] Masih belum bisa terhubung ke MySQL.
        echo Silakan nyalakan MySQL lalu jalankan installer ini kembali.
        pause
        exit /b 1
    )
)

echo Membuat database 'project_pos'...
"%MYSQL_CMD%" -h 127.0.0.1 -u root -e "CREATE DATABASE IF NOT EXISTS project_pos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

echo Mengimpor database_kosong.sql...
if exist "database_kosong.sql" (
    "%MYSQL_CMD%" -h 127.0.0.1 -u root project_pos < "database_kosong.sql"
    echo [OK] Database kosongan berhasil diimpor!
) else (
    echo [WARNING] database_kosong.sql tidak ditemukan. Menjalankan migrasi bawaan...
    php artisan migrate --force
)
echo.

:: 5. Storage link & Clear cache
echo [5/5] Finalisasi sistem...
php artisan storage:link >nul 2>nul
php artisan config:clear
php artisan cache:clear
php artisan view:clear
echo.

echo ================================================================
echo             INSTALASI BERHASIL & SELESAI!
echo ================================================================
echo.
echo Akun Login Default:
echo   - Owner  : owner@example.com  ^| Password: akunPOS!2026
echo   - Admin  : admin@example.com  ^| Password: akunPOS!2026
echo   - Gudang : gudang@example.com ^| Password: akunPOS!2026
echo   - Kasir  : kasir@example.com  ^| Password: akunPOS!2026
echo.
echo Untuk menjalankan aplikasi, klik 2x file:
echo   --^> start.bat
echo ================================================================
echo.
pause
