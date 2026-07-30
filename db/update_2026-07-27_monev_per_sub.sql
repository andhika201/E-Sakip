-- =====================================================================
-- MONEV per SUB RENCANA AKSI
-- Tanggal : 2026-07-27
-- Sifat   : IDEMPOTEN - aman dijalankan ulang. Data lama DIPERTAHANKAN.
--
-- Tujuan  : Capaian triwulan diinput per SUB RENCANA AKSI, sejalan dengan
--           target triwulan yang sudah pindah ke `target_sub_rencana`.
--
-- Perubahan:
--   1. Tambah kolom `monev.target_sub_rencana_id` (0 = capaian tingkat
--      indikator/rencana aksi, yaitu bentuk lama).
--   2. UNIQUE lama `uq_monev_rencana_tahun` (hanya target_rencana_id) diganti
--      `uq_monev_target_sub` (target_rencana_id, target_sub_rencana_id),
--      supaya satu renaksi bisa punya banyak baris capaian — satu per sub.
--
-- Catatan : kolom sengaja NOT NULL DEFAULT 0 (bukan NULL) supaya UNIQUE-nya
--           benar-benar mengikat; MySQL menganggap NULL selalu berbeda,
--           sehingga (target, NULL) bisa dobel kalau memakai NULL.
--           Karena itu TIDAK ada FK ke target_sub_rencana — pembersihan baris
--           yatim dilakukan di aplikasi saat sub dihapus.
--
-- Jalankan: mysql -u root test_sakip < db/update_2026-07-27_monev_per_sub.sql
-- =====================================================================

DROP PROCEDURE IF EXISTS _monev_upgrade;
DELIMITER $$
CREATE PROCEDURE _monev_upgrade()
BEGIN
  -- 1) kolom penanda sub
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'monev'
      AND COLUMN_NAME = 'target_sub_rencana_id'
  ) THEN
    ALTER TABLE `monev`
      ADD COLUMN `target_sub_rencana_id` INT UNSIGNED NOT NULL DEFAULT 0
      COMMENT '0 = capaian tingkat rencana aksi (data lama); >0 = id target_sub_rencana'
      AFTER `target_rencana_id`;
  END IF;

  -- 2) UNIQUE baru DULU: 1 baris capaian per (renaksi, sub).
  --    Harus dibuat sebelum index lama dibuang, karena FK fk_monev_target
  --    membutuhkan index berawalan target_rencana_id — index baru ini
  --    memenuhinya (kolom pertamanya target_rencana_id).
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'monev'
      AND INDEX_NAME = 'uq_monev_target_sub'
  ) THEN
    ALTER TABLE `monev`
      ADD UNIQUE KEY `uq_monev_target_sub` (`target_rencana_id`, `target_sub_rencana_id`);
  END IF;

  -- 3) baru buang UNIQUE lama yang mengunci 1 baris per target_rencana
  IF EXISTS (
    SELECT 1 FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'monev'
      AND INDEX_NAME = 'uq_monev_rencana_tahun'
  ) THEN
    ALTER TABLE `monev` DROP INDEX `uq_monev_rencana_tahun`;
  END IF;
END$$
DELIMITER ;

CALL _monev_upgrade();
DROP PROCEDURE IF EXISTS _monev_upgrade;
