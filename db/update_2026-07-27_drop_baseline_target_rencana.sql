-- =====================================================================
-- HAPUS kolom "Baseline" (target_rencana.capaian)
-- Tanggal : 2026-07-27
-- Sifat   : IDEMPOTEN - aman dijalankan ulang. DESTRUKTIF pada 1 kolom,
--           tapi isinya DICADANGKAN dulu ke tabel terpisah.
--
-- Latar   : Kolom `capaian` menampung "Baseline (Capaian Awal)" pada modul
--           Target & Rencana Aksi (Renstra/RPJMD) dan PK Renaksi/MONEV.
--           Kolom ini dihapus atas permintaan; targetnya kini sepenuhnya
--           dipegang `target_sub_rencana.target_triwulan_1..4`.
--
-- JANGAN TERTUKAR — yang berikut BUKAN Baseline dan tetap ada:
--   monev.capaian_triwulan_1..4      (realisasi kinerja)
--   monev.total                      (total capaian)
--   monev_anggaran.realisasi_*       (realisasi anggaran)
--   target_sub_rencana.target_*      (target per sub rencana aksi)
--   renstra_indikator_sasaran.baseline / rpjmd_indikator_sasaran.baseline
--     (Baseline milik Renstra/RPJMD — dipakai modul Cascading, JANGAN disentuh)
--
-- URUTAN WAJIB: jalankan file ini SETELAH kode diperbarui. Kalau kolom
--   di-drop sementara kode masih SELECT tr.capaian, halaman Target & MONEV
--   akan error SQL 1054.
--
-- Jalankan: mysql -u root test_sakip < db/update_2026-07-27_drop_baseline_target_rencana.sql
-- =====================================================================

-- 1) Cadangkan isi kolom sebelum dibuang (hanya baris yang benar-benar terisi).
CREATE TABLE IF NOT EXISTS `_bak_target_rencana_capaian_20260727` (
  `target_rencana_id` INT NOT NULL,
  `capaian`           VARCHAR(255) NULL,
  `dicadangkan_pada`  DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`target_rencana_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP PROCEDURE IF EXISTS _drop_baseline_target_rencana;
DELIMITER $$
CREATE PROCEDURE _drop_baseline_target_rencana()
BEGIN
  IF EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'target_rencana'
      AND COLUMN_NAME  = 'capaian'
  ) THEN
    -- salin dulu (INSERT IGNORE supaya aman kalau file dijalankan ulang)
    INSERT IGNORE INTO `_bak_target_rencana_capaian_20260727` (`target_rencana_id`, `capaian`)
      SELECT `id`, `capaian` FROM `target_rencana`
      WHERE `capaian` IS NOT NULL AND TRIM(`capaian`) <> '';

    ALTER TABLE `target_rencana` DROP COLUMN `capaian`;
  END IF;
END$$
DELIMITER ;

CALL _drop_baseline_target_rencana();
DROP PROCEDURE IF EXISTS _drop_baseline_target_rencana;

-- Untuk mengembalikan (kalau suatu saat perlu):
--   ALTER TABLE target_rencana ADD COLUMN capaian VARCHAR(255) NULL AFTER rencana_aksi;
--   UPDATE target_rencana tr
--     JOIN _bak_target_rencana_capaian_20260727 b ON b.target_rencana_id = tr.id
--     SET tr.capaian = b.capaian;
