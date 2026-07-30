-- =====================================================================
-- TARGET TRIWULAN per SUB RENCANA AKSI
-- Tanggal : 2026-07-27
-- Sifat   : IDEMPOTEN - aman dijalankan ulang. ADDITIVE (kolom NULL-only).
--
-- Tujuan  : Target triwulan I-IV diisi per BUTIR SUB RENCANA AKSI, bukan lagi
--           satu nilai untuk seluruh indikator.
--
-- Catatan : Kolom `target_rencana.target_triwulan_1..4` SENGAJA DIPERTAHANKAN.
--           Halaman MONEV memakainya sebagai target tingkat indikator untuk
--           dibandingkan dengan capaian triwulan — kalau dihapus, MONEV rusak.
--
-- Prasyarat: db/update_2026-07-27_sub_rencana_aksi.sql sudah dijalankan.
-- Jalankan : mysql -u root test_sakip < db/update_2026-07-27_sub_rencana_triwulan.sql
-- =====================================================================

DROP PROCEDURE IF EXISTS _add_col_if_absent;
DELIMITER $$
CREATE PROCEDURE _add_col_if_absent(IN p_table VARCHAR(64), IN p_col VARCHAR(64), IN p_ddl TEXT)
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = p_table
      AND COLUMN_NAME  = p_col
  ) THEN
    SET @sql = p_ddl; PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
  END IF;
END$$
DELIMITER ;

CALL _add_col_if_absent('target_sub_rencana', 'target_triwulan_1',
  'ALTER TABLE `target_sub_rencana` ADD COLUMN `target_triwulan_1` VARCHAR(255) NULL AFTER `sub_rencana_aksi`');
CALL _add_col_if_absent('target_sub_rencana', 'target_triwulan_2',
  'ALTER TABLE `target_sub_rencana` ADD COLUMN `target_triwulan_2` VARCHAR(255) NULL AFTER `target_triwulan_1`');
CALL _add_col_if_absent('target_sub_rencana', 'target_triwulan_3',
  'ALTER TABLE `target_sub_rencana` ADD COLUMN `target_triwulan_3` VARCHAR(255) NULL AFTER `target_triwulan_2`');
CALL _add_col_if_absent('target_sub_rencana', 'target_triwulan_4',
  'ALTER TABLE `target_sub_rencana` ADD COLUMN `target_triwulan_4` VARCHAR(255) NULL AFTER `target_triwulan_3`');

DROP PROCEDURE IF EXISTS _add_col_if_absent;
