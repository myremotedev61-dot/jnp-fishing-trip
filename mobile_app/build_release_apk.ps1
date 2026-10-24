# =====================================================================
# BUILD SIGNED RELEASE APK ANDROID - FISHING TRIP (POWERSHELL)
# =====================================================================

$ErrorActionPreference = "Stop"
Set-Location -Path $PSScriptRoot

Write-Host "=======================================================================" -ForegroundColor Cyan
Write-Host "   BUILD SIGNED RELEASE APK ANDROID - FISHING TRIP" -ForegroundColor Cyan
Write-Host "=======================================================================" -ForegroundColor Cyan
Write-Host ""

# Cek Flutter SDK
if (-not (Get-Command "flutter" -ErrorAction SilentlyContinue)) {
    Write-Warning "Perintah 'flutter' tidak ditemukan di PATH sistem Anda."
    Write-Host "Pastikan Flutter SDK telah terpasang dan ditambahkan ke System Environment PATH." -ForegroundColor Yellow
    exit 1
}

Write-Host "1. Membersihkan cache build lama (flutter clean)..." -ForegroundColor Yellow
flutter clean

Write-Host "`n2. Mengambil paket dependensi (flutter pub get)..." -ForegroundColor Yellow
flutter pub get

Write-Host "`n3. Mengompilasi Release APK bertanda tangan digital..." -ForegroundColor Yellow
flutter build apk --release --split-per-abi

if ($LASTEXITCODE -eq 0) {
    Write-Host "`n=======================================================================" -ForegroundColor Green
    Write-Host "[SUKSES] Signed Release APK berhasil dibangun!" -ForegroundColor Green
    Write-Host "Lokasi Berkas APK: $PSScriptRoot\build\app\outputs\flutter-apk\" -ForegroundColor Green
    Write-Host "=======================================================================" -ForegroundColor Green
} else {
    Write-Host "`n[INFO] Mencoba kompilasi Universal Single APK..." -ForegroundColor Yellow
    flutter build apk --release
    if ($LASTEXITCODE -eq 0) {
        Write-Host "`n[SUKSES] Universal Release APK berhasil dibangun di build\app\outputs\flutter-apk\app-release.apk" -ForegroundColor Green
    } else {
        Write-Error "Build APK gagal. Periksa Android SDK dan Java JDK."
    }
}
