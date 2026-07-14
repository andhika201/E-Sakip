-- ============================================================
-- Perbaikan schema drift (2026-07-14)
-- Menyamakan lebar kolom naratif dengan validasi controller agar
-- input normal tidak memicu error 1406 "Data too long" (STRICT mode).
--   * lakip.target_lalu / capaian_lalu / capaian_tahun_ini : varchar(50) -> varchar(255)
--     (validasi controller = max_length[255])
--   * opd.alamat_opd : varchar(50) -> varchar(255)
-- Semua statement idempoten: MODIFY ke tipe yang sama = no-op yang sukses.
-- ============================================================

ALTER TABLE `lakip` MODIFY COLUMN `target_lalu`       VARCHAR(255) NOT NULL;
ALTER TABLE `lakip` MODIFY COLUMN `capaian_lalu`      VARCHAR(255) NOT NULL;
ALTER TABLE `lakip` MODIFY COLUMN `capaian_tahun_ini` VARCHAR(255) NOT NULL;

-- Bersihkan zero-date agar rebuild tabel `opd` tidak ditolak STRICT mode (error 1292)
UPDATE `opd` SET `created_at` = NOW() WHERE CAST(`created_at` AS CHAR) = '0000-00-00 00:00:00';
UPDATE `opd` SET `updated_at` = NOW() WHERE CAST(`updated_at` AS CHAR) = '0000-00-00 00:00:00';

ALTER TABLE `opd` MODIFY COLUMN `alamat_opd` VARCHAR(255) NULL;
