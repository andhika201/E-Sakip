-- =====================================================================
-- FIX drift skema (hasil audit save/update): MONEV + Program PK
-- Tanggal : 2026-07-03
-- Sifat   : IDEMPOTEN (cek information_schema dulu) + AMAN (additive/pelebaran).
-- Konteks : Audit menemukan penyimpanan gagal / kolom hilang di beberapa modul.
--           Renstra/RPJMD/IKU/Target/Cascading/RKPD: TIDAK ada masalah.
--
--   1) monev.total : INT NOT NULL -> INT NULL. `total` bersifat opsional
--      (dihitung dari rata-rata capaian triwulan yang boleh kosong).
--      Controller Monev/PkRenaksi menulis null saat total kosong -> 1048.
--
--   2) program_pk / kegiatan_pk / sub_kegiatan_pk : TAMBAH kolom
--      `jenis_anggaran` (murni/perubahan). Kolom ditulis controller
--      (save/update/import) tetapi TIDAK ADA di DB -> "Unknown column".
--
--   3) kode_program / kode_kegiatan / kode_sub_kegiatan : INT -> VARCHAR(50).
--      Kode bersifat hierarkis berpola titik (mis. "1.01.2.01") & uniqid,
--      tidak muat di INT (terpotong / gagal). Nilai INT lama tetap valid
--      sebagai string.
--
-- Catatan : Perbaikan LAKIP (target_lalu/capaian_lalu/capaian_tahun_ini yang
--           salah jadi NULL) sudah diperbaiki di CONTROLLER (bukan skema),
--           jadi tidak ada perubahan tabel untuk LAKIP.
-- Jalankan: mysql -u root test_sakip < db/update_2026-07-03_schema_drift_fixes.sql
-- =====================================================================

-- ---------- Helper: tambah kolom bila belum ada ----------
DROP PROCEDURE IF EXISTS _add_col_if_absent;
DELIMITER $$
CREATE PROCEDURE _add_col_if_absent(IN p_table VARCHAR(64), IN p_col VARCHAR(64), IN p_ddl TEXT)
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND COLUMN_NAME = p_col
  ) THEN
    SET @sql = p_ddl; PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
  END IF;
END$$
DELIMITER ;

-- ---------- 1) monev.total -> NULLABLE ----------
ALTER TABLE `monev` MODIFY `total` INT NULL DEFAULT NULL;

-- ---------- 2) jenis_anggaran (murni/perubahan) ----------
CALL _add_col_if_absent('program_pk','jenis_anggaran',
  "ALTER TABLE `program_pk` ADD COLUMN `jenis_anggaran` VARCHAR(20) NULL DEFAULT 'murni' AFTER `tahun_anggaran`");
CALL _add_col_if_absent('kegiatan_pk','jenis_anggaran',
  "ALTER TABLE `kegiatan_pk` ADD COLUMN `jenis_anggaran` VARCHAR(20) NULL DEFAULT 'murni' AFTER `tahun_anggaran`");
CALL _add_col_if_absent('sub_kegiatan_pk','jenis_anggaran',
  "ALTER TABLE `sub_kegiatan_pk` ADD COLUMN `jenis_anggaran` VARCHAR(20) NULL DEFAULT 'murni' AFTER `tahun_anggaran`");

-- ---------- 3) kode_* : INT -> VARCHAR(50) ----------
-- ALTER MODIFY bersifat deklaratif (aman dijalankan berulang; INT lama -> string).
ALTER TABLE `program_pk`      MODIFY `kode_program`     VARCHAR(50) NOT NULL;
ALTER TABLE `kegiatan_pk`     MODIFY `kode_kegiatan`    VARCHAR(50) NOT NULL;
ALTER TABLE `sub_kegiatan_pk` MODIFY `kode_sub_kegiatan` VARCHAR(50) NOT NULL;

DROP PROCEDURE IF EXISTS _add_col_if_absent;

-- Selesai.
