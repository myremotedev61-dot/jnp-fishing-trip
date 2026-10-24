# =====================================================================
# GENERATE KEYSTORE SIGNING KEY RELEASE ANDROID - FISHING TRIP
# =====================================================================

$ErrorActionPreference = "Stop"
Set-Location -Path $PSScriptRoot

$keystoreFile = "fishingtrip-release.keystore"
$alias = "fishingtrip"
$validityDays = 10000

if (Test-Path $keystoreFile) {
    Write-Host "[INFO] Berkas keystore $keystoreFile sudah ada di direktori ini." -ForegroundColor Yellow
    exit 0
}

if (-not (Get-Command "keytool" -ErrorAction SilentlyContinue)) {
    Write-Warning "Perintah 'keytool' (Java JDK) tidak ditemukan di PATH."
    Write-Host "Silakan jalankan dari Command Prompt dengan JDK terpasang." -ForegroundColor Yellow
    exit 1
}

keytool -genkey -v -keystore $keystoreFile -alias $alias -keyalg RSA -keysize 2048 -validity $validityDays -storepass fishingtrip2026 -keypass fishingtrip2026 -dname "CN=Fishing Trip Admin, OU=IT Mobile, O=Angler Squad Turatea, L=Jeneponto, ST=Sulawesi Selatan, C=ID"

if ($LASTEXITCODE -eq 0) {
    Write-Host "`n[SUKSES] Keystore release berhasil dibuat: $keystoreFile" -ForegroundColor Green
    Write-Host "Simpan keystore ini di tempat aman untuk seluruh update APK di masa mendatang." -ForegroundColor Green
}
