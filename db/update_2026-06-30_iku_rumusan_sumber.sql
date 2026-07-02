-- =====================================================================
-- IKU: tambah kolom Formula/Rumusan Perhitungan & Sumber Data
-- Tanggal : 2026-06-30
-- Sifat   : IDEMPOTEN - aman dijalankan ulang. ADDITIVE (tidak menghapus data).
-- Tujuan  : Menyimpan "Formula/Rumusan Perhitungan" dan "Sumber Data" per IKU,
--           menggantikan tampilan kolom "Program Pendukung" yang dinonaktifkan.
-- Engine  : InnoDB. Kolom NULL-only -> aman terhadap baris lama.
-- Jalankan: mysql -u root test_sakip < db/update_2026-06-30_iku_rumusan_sumber.sql
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

CALL _add_col_if_absent('iku','rumusan_perhitungan',
  'ALTER TABLE iku ADD COLUMN rumusan_perhitungan TEXT NULL DEFAULT NULL AFTER definisi');

CALL _add_col_if_absent('iku','sumber_data',
  'ALTER TABLE iku ADD COLUMN sumber_data TEXT NULL DEFAULT NULL AFTER rumusan_perhitungan');

DROP PROCEDURE IF EXISTS _add_col_if_absent;

-- Selesai. Kolom baru: iku.rumusan_perhitungan, iku.sumber_data (keduanya NULL).
