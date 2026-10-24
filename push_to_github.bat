@echo off
echo =======================================================================
echo    PUSH SOURCE CODE KE GITHUB (UNTUK MEMULAI BUILD APK OTOMATIS)
echo =======================================================================
echo.
echo Menghubungkan ke: https://github.com/myremotedev61-dot/jnp-fishing-trip.git
echo.

cd /d "%~dp0"

git remote set-url origin https://github.com/myremotedev61-dot/jnp-fishing-trip.git
git branch -M main

echo Melakukan push ke GitHub...
git push -u origin main

if %ERRORLEVEL% equ 0 (
    echo.
    echo =======================================================================
    echo [SUKSES] Seluruh kode berhasil di-push ke GitHub!
    echo Sekarang buka kembali tab browser GitHub Anda di:
    echo https://github.com/myremotedev61-dot/jnp-fishing-trip/actions
    echo.
    echo Anda akan melihat proses build APK sedang berjalan otomatis.
    echo =======================================================================
) else (
    echo.
    echo [PERHATIAN] Jika diminta login, silakan login dengan akun GitHub Anda
    echo di jendela browser yang muncul atau gunakan Personal Access Token.
)

echo.
pause
