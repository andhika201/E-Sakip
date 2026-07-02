-- =====================================================================
-- Perluasan Monitoring Realisasi PK -> Rencana Aksi (Bupati & Eselon III)
-- Tanggal : 2026-06-29
-- Sifat   : IDEMPOTEN - aman dijalankan ulang. ADDITIVE (tidak menghapus data).
-- Tujuan  : Memberi target_rencana (Rencana Aksi) jangkar KETIGA ke indikator
--           PK (pk_indikator), di samping renstra_target_id & rpjmd_target_id,
--           agar realisasi PK Bupati (pk.jenis='bupati') dan PK Administrator/
--           Eselon III (pk.jenis='administrator') bisa diukur lewat MONEV
--           memakai mesin Rencana Aksi + MONEV yang sudah ada.
-- Engine  : InnoDB.
-- Konvensi: ON UPDATE CASCADE; ON DELETE CASCADE (renaksi tak bermakna tanpa
--           indikator induknya), mengikuti gaya db/update_2026-06-28_fk.sql.
-- Jalankan: mysql -u root test_sakip < db/update_2026-06-29_pk_renaksi.sql
-- Catatan : Kolom baru NULL-only saat dibuat, jadi penambahan FK aman terhadap
--           data lama (tidak ada baris yang melanggar).
-- =====================================================================

-- ---------------------------------------------------------------------
-- HELPER 1: tambah KOLOM hanya bila belum ada (idempoten)
-- ---------------------------------------------------------------------
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

-- ---------------------------------------------------------------------
-- HELPER 2: tambah FOREIGN KEY hanya bila belum ada (idempoten)
-- ---------------------------------------------------------------------
DROP PROCEDURE IF EXISTS _add_fk_if_absent;
DELIMITER $$
CREATE PROCEDURE _add_fk_if_absent(IN p_table VARCHAR(64), IN p_name VARCHAR(64), IN p_ddl TEXT)
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME       = p_table
      AND CONSTRAINT_NAME  = p_name
      AND CONSTRAINT_TYPE  = 'FOREIGN KEY'
  ) THEN
    SET @sql = p_ddl; PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
  END IF;
END$$
DELIMITER ;

-- ---------------------------------------------------------------------
-- 1) Kolom jangkar baru: target_rencana.pk_indikator_id
--    (NULL = soft-pointer, sejajar renstra_target_id & rpjmd_target_id)
-- ---------------------------------------------------------------------
CALL _add_col_if_absent('target_rencana','pk_indikator_id',
  'ALTER TABLE target_rencana ADD COLUMN pk_indikator_id INT UNSIGNED NULL DEFAULT NULL AFTER rpjmd_target_id');

-- ---------------------------------------------------------------------
-- 2) Foreign Key target_rencana.pk_indikator_id -> pk_indikator.id
-- ---------------------------------------------------------------------
CALL _add_fk_if_absent('target_rencana','fk_target_rencana_pk_indikator',
  'ALTER TABLE target_rencana ADD CONSTRAINT fk_target_rencana_pk_indikator FOREIGN KEY (pk_indikator_id) REFERENCES pk_indikator(id) ON UPDATE CASCADE ON DELETE CASCADE');

-- ---------------------------------------------------------------------
-- 3) Bersihkan helper
-- ---------------------------------------------------------------------
DROP PROCEDURE IF EXISTS _add_col_if_absent;
DROP PROCEDURE IF EXISTS _add_fk_if_absent;

-- Selesai. Setelah ini, satu baris target_rencana boleh mengacu ke salah satu:
--   renstra_target_id  (Renstra / Eselon II / OPD)        -- sudah ada
--   rpjmd_target_id    (RPJMD / Kabupaten)                -- sudah ada
--   pk_indikator_id    (PK Bupati / PK Administrator-ES3) -- BARU
