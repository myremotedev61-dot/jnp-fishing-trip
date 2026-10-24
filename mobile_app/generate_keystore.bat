@echo off
echo =======================================================================
echo    GENERATE SIGNING KEYSTORE RELEASE ANDROID UNTUK FISHING TRIP
echo =======================================================================
echo.
echo Pastikan JDK/Java keytool sudah terinstal pada PATH sistem Anda.
echo.

set KEYSTORE_FILE=fishingtrip-release.keystore
set ALIAS=fishingtrip
set VALIDITY_DAYS=10000

if exist "%KEYSTORE_FILE%" (
    echo [INFO] Keystore %KEYSTORE_FILE% sudah ada.
    goto end
)

keytool -genkey -v -keystore %KEYSTORE_FILE% -alias %ALIAS% -keyalg RSA -keysize 2048 -validity %VALIDITY_DAYS% -storepass fishingtrip2026 -keypass fishingtrip2026 -dname "CN=Fishing Trip Admin, OU=IT Mobile, O=Angler Squad Turatea, L=Jeneponto, ST=Sulawesi Selatan, C=ID"

if %ERRORLEVEL% equ 0 (
    echo.
    echo [SUKSES] Keystore release berhasil dibuat di: %KEYSTORE_FILE%
    echo Simpan keystore ini di tempat aman dan jangan sampai hilang!
) else (
    echo.
    echo [ERROR] Gagal membuat keystore. Pastikan Java JDK keytool tersedia di command line.
)

:end
pause
