-- ========================================================
-- DATABASE: fishing_trip
-- SISTEM MANAJEMEN USAHA KAPAL PEMANCINGAN "FISHING TRIP"
-- ========================================================

CREATE DATABASE IF NOT EXISTS `fishing_trip` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `fishing_trip`;

-- Disable Foreign Key checks during table creation
SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- 1. Tabel users
-- --------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nama` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NULL UNIQUE,
  `no_telepon` VARCHAR(25) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('pemancing', 'admin', 'owner') NOT NULL DEFAULT 'pemancing',
  `status_akun` ENUM('aktif', 'nonaktif', 'banned') NOT NULL DEFAULT 'aktif',
  `foto_profil` VARCHAR(255) NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  INDEX `idx_users_role` (`role`),
  INDEX `idx_users_phone` (`no_telepon`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 2. Tabel user_tokens (Autentikasi REST API Mobile)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `user_tokens`;
CREATE TABLE `user_tokens` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `token` VARCHAR(128) NOT NULL UNIQUE,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` TEXT NULL,
  `expires_at` DATETIME NOT NULL,
  `created_at` DATETIME NOT NULL,
  INDEX `idx_tokens_user` (`user_id`),
  INDEX `idx_tokens_val` (`token`),
  CONSTRAINT `fk_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 3. Tabel kapal
-- --------------------------------------------------------
DROP TABLE IF EXISTS `kapal`;
CREATE TABLE `kapal` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `kode_kapal` VARCHAR(30) NOT NULL UNIQUE,
  `nama_kapal` VARCHAR(100) NOT NULL,
  `foto` VARCHAR(255) NULL,
  `kapasitas` INT NOT NULL,
  `deskripsi` TEXT NULL,
  `fasilitas` TEXT NULL,
  `status` ENUM('aktif', 'nonaktif', 'pemeliharaan') NOT NULL DEFAULT 'aktif',
  `catatan_pemeliharaan` TEXT NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  INDEX `idx_kapal_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 4. Tabel trips
-- --------------------------------------------------------
DROP TABLE IF EXISTS `trips`;
CREATE TABLE `trips` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `kode_trip` VARCHAR(40) NOT NULL UNIQUE,
  `kapal_id` INT UNSIGNED NOT NULL,
  `tanggal_trip` DATE NOT NULL,
  `jam_kumpul` TIME NOT NULL,
  `jam_berangkat` TIME NOT NULL,
  `jam_kembali` TIME NOT NULL,
  `lokasi_kumpul` VARCHAR(255) NOT NULL,
  `tujuan` VARCHAR(255) NOT NULL,
  `harga_per_peserta` DECIMAL(12,2) NOT NULL,
  `nominal_panjar` DECIMAL(12,2) NOT NULL,
  `kuota` INT NOT NULL,
  `batas_pendaftaran` DATETIME NOT NULL,
  `batas_pembayaran` DATETIME NOT NULL,
  `fasilitas` TEXT NULL,
  `ketentuan` TEXT NULL,
  `deskripsi` TEXT NULL,
  `kebijakan_pembatalan` TEXT NULL,
  `status` ENUM('draft', 'dibuka', 'hampir_penuh', 'penuh', 'ditutup', 'selesai', 'dibatalkan') NOT NULL DEFAULT 'draft',
  `alasan_pembatalan` TEXT NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  INDEX `idx_trips_date` (`tanggal_trip`),
  INDEX `idx_trips_status` (`status`),
  CONSTRAINT `fk_trips_kapal` FOREIGN KEY (`kapal_id`) REFERENCES `kapal` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 5. Tabel pendaftaran
-- --------------------------------------------------------
DROP TABLE IF EXISTS `pendaftaran`;
CREATE TABLE `pendaftaran` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `kode_pendaftaran` VARCHAR(40) NOT NULL UNIQUE,
  `trip_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `tanggal_daftar` DATETIME NOT NULL,
  `jumlah_diajukan` INT NOT NULL,
  `status` ENUM('menunggu_persetujuan', 'disetujui_sebagian', 'disetujui', 'ditolak', 'dibatalkan', 'masuk_daftar_tunggu') NOT NULL DEFAULT 'menunggu_persetujuan',
  `catatan` TEXT NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  INDEX `idx_pendaftaran_trip` (`trip_id`),
  INDEX `idx_pendaftaran_user` (`user_id`),
  INDEX `idx_pendaftaran_status` (`status`),
  CONSTRAINT `fk_pendaftaran_trip` FOREIGN KEY (`trip_id`) REFERENCES `trips` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_pendaftaran_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 6. Tabel peserta_trip
-- --------------------------------------------------------
DROP TABLE IF EXISTS `peserta_trip`;
CREATE TABLE `peserta_trip` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `pendaftaran_id` INT UNSIGNED NOT NULL,
  `trip_id` INT UNSIGNED NOT NULL,
  `nama_pemancing` VARCHAR(100) NOT NULL,
  `no_telepon` VARCHAR(25) NOT NULL,
  `status_persetujuan` ENUM(
    'menunggu_persetujuan',
    'disetujui',
    'ditolak',
    'menunggu_pembayaran_panjar',
    'dikonfirmasi',
    'dibatalkan',
    'masuk_daftar_tunggu'
  ) NOT NULL DEFAULT 'menunggu_persetujuan',
  `alasan_penolakan` TEXT NULL,
  `harga_final` DECIMAL(12,2) NOT NULL,
  `disetujui_oleh` INT UNSIGNED NULL,
  `tanggal_persetujuan` DATETIME NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  INDEX `idx_peserta_trip` (`trip_id`),
  INDEX `idx_peserta_pendaftaran` (`pendaftaran_id`),
  INDEX `idx_peserta_status` (`status_persetujuan`),
  CONSTRAINT `fk_peserta_pendaftaran` FOREIGN KEY (`pendaftaran_id`) REFERENCES `pendaftaran` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_peserta_trip` FOREIGN KEY (`trip_id`) REFERENCES `trips` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_peserta_admin` FOREIGN KEY (`disetujui_oleh`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 7. Tabel pembayaran
-- --------------------------------------------------------
DROP TABLE IF EXISTS `pembayaran`;
CREATE TABLE `pembayaran` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `kode_pembayaran` VARCHAR(40) NOT NULL UNIQUE,
  `peserta_trip_id` INT UNSIGNED NOT NULL,
  `nominal` DECIMAL(12,2) NOT NULL,
  `metode_pembayaran` VARCHAR(50) NOT NULL,
  `tanggal_transaksi` DATE NOT NULL,
  `nomor_referensi` VARCHAR(100) NULL,
  `bukti_pembayaran` VARCHAR(255) NOT NULL,
  `status_verifikasi` ENUM(
    'menunggu_verifikasi',
    'terverifikasi',
    'ditolak',
    'dibatalkan',
    'dikembalikan'
  ) NOT NULL DEFAULT 'menunggu_verifikasi',
  `diverifikasi_oleh` INT UNSIGNED NULL,
  `tanggal_verifikasi` DATETIME NULL,
  `alasan_penolakan` TEXT NULL,
  `catatan_internal` TEXT NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  INDEX `idx_pembayaran_peserta` (`peserta_trip_id`),
  INDEX `idx_pembayaran_status` (`status_verifikasi`),
  CONSTRAINT `fk_pembayaran_peserta` FOREIGN KEY (`peserta_trip_id`) REFERENCES `peserta_trip` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_pembayaran_admin` FOREIGN KEY (`diverifikasi_oleh`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 8. Tabel pengembalian_dana (Refund)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `pengembalian_dana`;
CREATE TABLE `pengembalian_dana` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `pembayaran_id` INT UNSIGNED NOT NULL,
  `peserta_trip_id` INT UNSIGNED NOT NULL,
  `nominal_refund` DECIMAL(12,2) NOT NULL,
  `alasan_refund` TEXT NOT NULL,
  `diproses_oleh` INT UNSIGNED NOT NULL,
  `tanggal_refund` DATETIME NOT NULL,
  `bukti_refund` VARCHAR(255) NULL,
  `created_at` DATETIME NULL,
  INDEX `idx_refund_peserta` (`peserta_trip_id`),
  CONSTRAINT `fk_refund_pembayaran` FOREIGN KEY (`pembayaran_id`) REFERENCES `pembayaran` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_refund_peserta` FOREIGN KEY (`peserta_trip_id`) REFERENCES `peserta_trip` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_refund_admin` FOREIGN KEY (`diproses_oleh`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 9. Tabel approval_log
-- --------------------------------------------------------
DROP TABLE IF EXISTS `approval_log`;
CREATE TABLE `approval_log` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_admin_id` INT UNSIGNED NOT NULL,
  `entitas` VARCHAR(50) NOT NULL,
  `entitas_id` INT UNSIGNED NOT NULL,
  `aksi` VARCHAR(50) NOT NULL,
  `status_sebelumnya` VARCHAR(50) NULL,
  `status_sesudahnya` VARCHAR(50) NOT NULL,
  `alasan` TEXT NULL,
  `created_at` DATETIME NOT NULL,
  INDEX `idx_log_admin` (`user_admin_id`),
  INDEX `idx_log_entitas` (`entitas`, `entitas_id`),
  CONSTRAINT `fk_log_admin` FOREIGN KEY (`user_admin_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 10. Tabel notifikasi
-- --------------------------------------------------------
DROP TABLE IF EXISTS `notifikasi`;
CREATE TABLE `notifikasi` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `judul` VARCHAR(150) NOT NULL,
  `pesan` TEXT NOT NULL,
  `jenis` VARCHAR(50) NOT NULL,
  `data_referensi` TEXT NULL,
  `dibaca_pada` DATETIME NULL,
  `created_at` DATETIME NOT NULL,
  INDEX `idx_notif_user` (`user_id`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 11. Tabel fcm_tokens
-- --------------------------------------------------------
DROP TABLE IF EXISTS `fcm_tokens`;
CREATE TABLE `fcm_tokens` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `token` VARCHAR(191) NOT NULL UNIQUE,
  `device_info` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NULL,
  INDEX `idx_fcm_user` (`user_id`),
  CONSTRAINT `fk_fcm_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 12. Tabel pengaturan_usaha
-- --------------------------------------------------------
DROP TABLE IF EXISTS `pengaturan_usaha`;
CREATE TABLE `pengaturan_usaha` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nama_usaha` VARCHAR(100) NOT NULL DEFAULT 'Fishing Trip Turatea',
  `logo` VARCHAR(255) NULL,
  `no_whatsapp` VARCHAR(25) NOT NULL DEFAULT '6281234567890',
  `alamat` TEXT NULL,
  `rekening_pembayaran` TEXT NOT NULL,
  `pengaturan_panjar` TEXT NOT NULL,
  `zona_waktu` VARCHAR(50) NOT NULL DEFAULT 'Asia/Makassar',
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------
-- SEED DATA AWAL (Administrator & Pengaturan)
-- Default admin: no_telepon 081234567899 / password: admin123 (hashed)
-- --------------------------------------------------------
INSERT INTO `users` (`id`, `nama`, `email`, `no_telepon`, `password_hash`, `role`, `status_akun`, `created_at`, `updated_at`)
VALUES
(1, 'Administrator Fishing Trip', 'admin@fishingtrip.com', '081234567899', '$2y$10$n4qG3fCsqt.EwhrE8eWjyeE7.1W7tQ51bK8P1X6nJ7mU4n0f2F6yK', 'admin', 'aktif', NOW(), NOW()),
(2, 'Pemancing Percobaan', 'angler@test.com', '081298765432', '$2y$10$n4qG3fCsqt.EwhrE8eWjyeE7.1W7tQ51bK8P1X6nJ7mU4n0f2F6yK', 'pemancing', 'aktif', NOW(), NOW());

-- Seed Kapal Awal
INSERT INTO `kapal` (`id`, `kode_kapal`, `nama_kapal`, `foto`, `kapasitas`, `deskripsi`, `fasilitas`, `status`, `created_at`, `updated_at`)
VALUES
(1, 'KPL-001', 'KM Samudra Turatea I', 'kapal1.jpg', 12, 'Kapal pemancingan modern dengan 2 mesin ganda, navigasi GPS Fishfinder Garmin, kabin istirahat ber-AC, dan dek luas.', 'GPS Fishfinder, AC Cabin, Toilet Bersih, Dapur Mini & Dispenser, Alat Pancing Cadangan, Life Jacket & Pelampung Standar SOLAS, Coolbox Es 500 Liter', 'aktif', NOW(), NOW()),
(2, 'KPL-002', 'KM Bintang Bahari II', 'kapal2.jpg', 15, 'Kapal tangguh untuk ekspedisi pemancingan laut dalam (Deep Sea Fishing & Jigging) dengan perlengkapan keselamatan lengkap.', 'Fishfinder Sonar, Kabin Tidur 10 Bunk, Kamar Mandi Air Tawar, Generator Listrik 220V, Lampu Sorot Malam, Umpan Hidup Livebait Tank', 'aktif', NOW(), NOW());

-- Seed Pengaturan Usaha
INSERT INTO `pengaturan_usaha` (`id`, `nama_usaha`, `logo`, `no_whatsapp`, `alamat`, `rekening_pembayaran`, `pengaturan_panjar`, `zona_waktu`, `created_at`, `updated_at`)
VALUES
(1, 'Fishing Trip Turatea', 'logo.png', '6281234567890', 'Pelabuhan Jeneponto - Turatea, Sulawesi Selatan', 
 '[{"bank":"BCA","nomor":"1234567890","pemilik":"FISHING TRIP TURATEA"},{"bank":"BRI","nomor":"098765432112345","pemilik":"FISHING TRIP TURATEA"},{"bank":"Mandiri","nomor":"1520098765432","pemilik":"FISHING TRIP TURATEA"}]',
 '{"minimal_panjar_persen":30,"batas_waktu_jam":24,"kebijakan_refund":"Panjar tidak dapat dikembalikan jika pembatalan dilakukan kurang dari H-3 keberangkatan"}',
 'Asia/Makassar', NOW(), NOW());
