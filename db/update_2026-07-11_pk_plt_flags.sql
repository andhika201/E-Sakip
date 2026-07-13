-- =====================================================================
-- Tambah flag PLT per dokumen PK
-- Tanggal : 2026-07-11
-- Sifat   : IDEMPOTEN (cek information_schema dulu). ADDITIVE.
-- Tujuan  : Simpan status Plt. di tabel `pk`, bukan lagi global dari
--           master `pegawai.is_plt`, supaya Plt. hanya muncul pada PK
--           yang dicentang.
-- Jalankan: mysql -u root test_sakip < db/update_2026-07-11_pk_plt_flags.sql
-- =====================================================================

DROP PROCEDURE IF EXISTS _add_col_if_absent;
DELIMITER $$
CREATE PROCEDURE _add_col_if_absent(IN p_table VARCHAR(64), IN p_col VARCHAR(64), IN p_ddl TEXT)
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND COLUMN_NAME = p_col
  ) THEN
    SET @sql = p_ddl;
    PREPARE st FROM @sql;
    EXECUTE st;
    DEALLOCATE PREPARE st;
  END IF;
END$$
DELIMITER ;

CALL _add_col_if_absent('pk', 'is_plt_pihak_1',
  'ALTER TABLE `pk` ADD COLUMN `is_plt_pihak_1` TINYINT(1) NOT NULL DEFAULT 0 AFTER `pihak_1`');

CALL _add_col_if_absent('pk', 'is_plt_pihak_2',
  'ALTER TABLE `pk` ADD COLUMN `is_plt_pihak_2` TINYINT(1) NOT NULL DEFAULT 0 AFTER `pihak_2`');

DROP PROCEDURE IF EXISTS _add_col_if_absent;

-- Selesai.
