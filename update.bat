@echo off
title Update POS Stock dari Git
color 0E
cd /D "%~dp0"

echo ================================================================
echo               UPDATE APLIKASI POS STOCK
echo ================================================================
echo.
echo Sedang memeriksa pembaruan dari Git...
echo.

:: 1. Deteksi PHP & Git
where git >nul 2>nul
if %errorlevel% neq 0 (
    echo [ERROR] Git tidak ditemukan! Pastikan Git sudah terpasang.
    pause
    exit /b 1
)

where php >nul 2>nul
if %errorlevel% neq 0 (
    if exist "C:\xampp\php\php.exe" set "PATH=C:\xampp\php;%PATH%"
    if exist "D:\xampp\php\php.exe" set "PATH=D:\xampp\php;%PATH%"
)

:: 2. Tarik update terbaru dari Git
echo [1/4] Mengunduh pembaruan (git pull)...
git pull origin main
if %errorlevel% neq 0 (
    echo.
    echo [INFO] Mencoba branch master...
    git pull origin master
    if %errorlevel% neq 0 (
        echo [ERROR] Gagal mengunduh pembaruan dari Git. Periksa koneksi internet Anda!
        pause
        exit /b 1
    )
)
echo [OK] Kode program berhasil diperbarui.
echo.

:: 3. Update composer jika ada package baru
echo [2/4] Memperbarui dependensi library...
where composer >nul 2>nul
if %errorlevel% equ 0 (
    call composer install --no-interaction
)
echo.

:: 4. Jalankan migrasi database jika ada tabel/kolom baru
echo [3/4] Memperbarui struktur database (migrasi)...
php artisan migrate --force
echo.

:: 5. Bersihkan cache aplikasi
echo [4/4] Membersihkan cache aplikasi...
php artisan config:clear
php artisan cache:clear
php artisan view:clear
echo.

echo ================================================================
echo              UPDATE SELESAI & BERHASIL 100%!
echo ================================================================
echo.
echo Aplikasi sudah diperbarui ke versi terbaru.
echo Silakan jalankan kembali melalui start.bat
echo.
pause
