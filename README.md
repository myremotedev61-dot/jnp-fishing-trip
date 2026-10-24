# FISHING TRIP - SISTEM MANAJEMEN USAHA KAPAL PEMANCINGAN
**Platform Terintegrasi Aplikasi Mobile Android (Flutter) & Web Dashboard Administrator (CodeIgniter 4)**

---

## 1. Ringkasan Proyek

**Fishing Trip** adalah platform terintegrasi untuk pengelolaan operasional usaha kapal pemancingan laut. Sistem ini terdiri dari:
1. **Aplikasi Android Pemancing (Flutter & Dart):** Digunakan oleh pelanggan/pemancing untuk mengecek kalender trip bulanan, kuota dan spesifikasi kapal, pendaftaran peserta mandiri & rombongan, pengunggahan bukti transfer panjar/pelunasan, pemantauan status persetujuan, notifikasi dalam aplikasi, dan hotline WhatsApp resmi.
2. **Web Dashboard Administrator (CodeIgniter 4 & Bootstrap 5):** Digunakan oleh pemilik kapal/administrator untuk mengelola armada kapal, jadwal keberangkatan, antrean persetujuan peserta (dengan alasan penolakan wajib), verifikasi bukti transfer pembayaran, pencatatan pembatalan & pengembalian dana (*refund*), buku besar kas riil, dan laporan pendapatan.
3. **Database Relasional & REST API (MySQL / MariaDB):** Skema 12 tabel InnoDB dengan isolasi transaksi anti-overbooking (*pessimistic locking* `FOR UPDATE`), proteksi IDOR, dan autentikasi token.

---

## 2. Struktur Direktori Proyek

```
c:\xampp\htdocs\AnglerSquadTuratea\
├── app/                            # Backend CodeIgniter 4 Framework
│   ├── Commands/
│   │   └── ExpireUnpaid.php        # CLI Spark: Pembatalan otomatis panjar kadaluarsa 24 jam
│   ├── Config/
│   │   ├── Routes.php              # Rute Web Dashboard & REST API
│   │   ├── Filters.php             # Registrasi ApiAuthFilter & AdminAuthFilter
│   │   └── Database.php            # Konfigurasi koneksi MySQL
│   ├── Controllers/
│   │   ├── AdminController.php     # Controller Admin Web Dashboard (11 modul operasional)
│   │   └── Api/
│   │       ├── BaseApiController.php   # Standarisasi JSON Response
│   │       ├── AuthController.php      # Login, Register, Profile, FCM Token
│   │       ├── TripController.php      # Kalender trip bulanan & detail kuota
│   │       ├── PendaftaranController.php # Pendaftaran mandiri & rombongan
│   │       ├── PembayaranController.php  # Pengajuan pembayaran & upload bukti
│   │       └── NotifikasiController.php  # Notifikasi in-app pemancing
│   ├── Filters/
│   │   ├── ApiAuthFilter.php       # Proteksi Bearer Token REST API
│   │   └── AdminAuthFilter.php     # Proteksi Sesi & Token Administrator
│   ├── Models/                     # 12 Model Database ORM (Kapal, Trip, Peserta, dll)
│   └── Views/
│       └── admin/                  # Antarmuka Responsif Web Bootstrap 5
│           ├── layout.php          # Master layout Navbar & Sidebar
│           ├── login.php           # Form login aman administrator
│           ├── dashboard.php       # Metrik ringkasan, kuota, pendapatan, grafik
│           ├── kapal.php           # Manajemen kapal armada
│           ├── trips.php           # Manajemen jadwal trip pemancingan
│           ├── peserta.php         # Tabel wajib 7 kolom seluruh peserta
│           ├── persetujuan.php     # Antrean persetujuan & tolak dengan alasan
│           ├── pembayaran.php      # Verifikasi bukti transfer privat & tolak
│           ├── pembatalan.php      # Manajemen pembatalan & pencatatan refund
│           ├── laporan.php         # Laporan keuangan kas riil & piutang
│           └── pengaturan.php      # Pengaturan info usaha, rekening & WA
├── database/
│   └── schema.sql                  # Skema 12 tabel MariaDB, indeks, relasi, seeds
├── deployment/                     # Berkas Konfigurasi Server Produksi
│   ├── .env.production             # Variabel environment produksi
│   ├── nginx.conf                  # Server block Nginx produksi + SSL Let's Encrypt
│   ├── apache-vhost.conf           # VirtualHost Apache 2.4 + SSL Let's Encrypt
│   └── cron_expire_unpaid.php      # Skrip crontab pembatalan panjar kadaluarsa
├── mobile_app/                     # Aplikasi Mobile Android (Flutter & Dart)
│   ├── android/
│   │   ├── app/
│   │   │   ├── src/main/AndroidManifest.xml # Izin kamera, internet, storage, nama aplikasi
│   │   │   └── build.gradle        # ApplicationId, minSdk 21, targetSdk 34, release signing
│   │   └── key.properties          # Konfigurasi release signing keystore
│   ├── lib/
│   │   ├── constants/              # Warna bahari (AppColors) & URL (AppConstants)
│   │   ├── models/                 # Model Dart (User, Trip, Pendaftaran, Pembayaran)
│   │   ├── services/               # ApiService (HTTP client, offline exception handling)
│   │   ├── screens/                # 10 Halaman antarmuka lengkap aplikasi pemancing
│   │   └── main.dart               # Entry point aplikasi Flutter
│   ├── generate_keystore.bat       # Skrip pembuat release keystore (.bat)
│   ├── generate_keystore.ps1       # Skrip pembuat release keystore (.ps1)
│   ├── build_release_apk.bat       # Skrip build release APK bertanda tangan (.bat)
│   └── build_release_apk.ps1       # Skrip build release APK bertanda tangan (.ps1)
└── tests/
    └── test_25_scenarios.php       # Skrip pengujian otomatis 25 skenario wajib (100% PASS)
```

---

## 3. Kredensial Akun Bawaan Sistem

### A. Administrator / Pemilik Usaha (Web Dashboard)
- **URL Dashboard:** `http://localhost:8080/admin/login`
- **Nomor Telepon / Login:** `081234567899`
- **Password:** `admin123`
- **Hak Akses:** Penuh (Owner / Admin)

### B. Pemancing Percobaan (Aplikasi Mobile Android)
- **Nomor Telepon:** `081298765432`
- **Password:** `angler123`
- **Nama:** Pemancing Percobaan

---

## 4. Cara Menjalankan Backend & Web Dashboard

1. **Jalankan Apache & MySQL di XAMPP:**
   Pastikan service MariaDB/MySQL berjalan pada port 3306.
2. **Jalankan CodeIgniter 4 Dev Server:**
   ```powershell
   & D:\php8\php.exe spark serve --port 8080
   ```
3. **Buka Web Dashboard Admin:**
   Buka peramban (*browser*) dan akses `http://localhost:8080/admin/login`.

---

## 5. Cara Menjalankan Pengujian 25 Skenario Wajib

Eksekusi skrip pengujian komprehensif:
```powershell
& D:\php8\php.exe tests/test_25_scenarios.php
```
*Hasil Uji: 25 dari 25 Skenario LULUS (100% PASS).*

---

## 6. Cara Menjalankan & Membangun Aplikasi Android (Flutter)

### Menjalankan di Emulator / Perangkat Nyata:
```bash
cd mobile_app
flutter pub get
flutter run
```

### Mengompilasi Signed Release APK:
Cukup jalankan berkas batch:
```cmd
cd mobile_app
build_release_apk.bat
```
Atau via perintah Flutter:
```bash
flutter build apk --release --split-per-abi --dart-define=API_URL=https://fishingtrip.yourdomain.com/api
```
Berkas APK siap pasang akan tersedia di:
`mobile_app/build/app/outputs/flutter-apk/app-arm64-v8a-release.apk`
