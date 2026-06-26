-- ============================================================
-- Migrasi: Tambah kolom login ke tabel pelanggan
-- Jalankan sekali di phpMyAdmin atau terminal MySQL
-- ============================================================

ALTER TABLE `pelanggan`
ADD COLUMN `username` VARCHAR(100) UNIQUE NULL AFTER `no_wa`,
ADD COLUMN `password` VARCHAR(255) NULL AFTER `username`,
ADD COLUMN `otp_code` VARCHAR(6) NULL AFTER `password`,
ADD COLUMN `otp_expired_at` DATETIME NULL AFTER `otp_code`;

