@echo off
echo =======================================================================
echo    BUILD SIGNED RELEASE APK ANDROID - FISHING TRIP
echo =======================================================================
echo.

cd /d "%~dp0"

echo 1. Membersihkan cache build lama...
call flutter clean

echo.
echo 2. Mengambil dependensi Flutter...
call flutter pub get

echo.
echo 3. Mengompilasi Signed Release APK...
call flutter build apk --release --split-per-abi

if %ERRORLEVEL% equ 0 (
    echo.
    echo =======================================================================
    echo [SUKSES] Signed Release APK berhasil dibangun!
    echo Berkas APK tersedia di direktori:
    echo %~dp0build\app\outputs\flutter-apk\
    echo =======================================================================
) else (
    echo.
    echo [INFO] Mencoba kompilasi Single Universal Release APK...
    call flutter build apk --release
    if %ERRORLEVEL% equ 0 (
        echo.
        echo [SUKSES] Universal Signed Release APK berhasil dibangun!
        echo Berkas APK: %~dp0build\app\outputs\flutter-apk\app-release.apk
    ) else (
        echo.
        echo [ERROR] Gagal mengompilasi APK. Pastikan Flutter dan Android SDK terkonfigurasi.
    )
)

pause
