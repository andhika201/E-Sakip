-- =====================================================================
-- CAPAIAN TOTAL OTOMATIS (persentase) — MONEV Rencana Aksi PK
-- Tanggal : 2026-07-27
-- Sifat   : IDEMPOTEN - aman dijalankan ulang.
--
-- Tujuan  : Kolom "Capaian Total" tidak lagi diketik manual, melainkan
--           dihitung otomatis sebagai PERSENTASE dari target & capaian
--           triwulan, mengikuti metode perhitungan yang dipilih.
--
-- Perubahan:
--   1. Tambah `monev.metode_perhitungan`
--      VARCHAR(20) NULL — 'sum' | 'trend_naik' | 'trend_turun' | 'trend_flat'.
--      Sengaja VARCHAR, bukan ENUM, supaya menambah metode baru nanti tidak
--      perlu ALTER TABLE (daftar sahnya dijaga aplikasi: capaianMetodeList()).
--   2. `monev.total` VARCHAR(255) -> DECIMAL(10,2) NULL.
--      Nilai disimpan TANPA tanda persen (mis. 86.67 untuk 86,67%).
--      Nama kolom `total` DIPERTAHANKAN (tidak dibuat kolom `capaian_total`
--      baru) supaya tidak ada kolom kembar; seluruh query lama tetap jalan.
--   3. Baris LAMA (metode_perhitungan masih NULL) totalnya dikosongkan.
--      Alasan: isi lama adalah ANGKA MUTLAK ketikan pengguna (mis. 222 =
--      131,518 + 90,821), bukan persentase — kalau dibiarkan akan tampil
--      sebagai "222%". Nilai lamanya DICADANGKAN utuh ke tabel
--      `_backup_monev_total_20260727` sebelum dikosongkan, dan akan terisi
--      lagi begitu capaiannya dibuka & disimpan ulang lewat form.
--
-- Jalankan: mysql -u root test_sakip < db/update_2026-07-27_monev_metode_perhitungan.sql
-- =====================================================================

DROP PROCEDURE IF EXISTS _monev_metode_upgrade;
DELIMITER $$
CREATE PROCEDURE _monev_metode_upgrade()
BEGIN
  -- 1) kolom metode perhitungan
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'monev'
      AND COLUMN_NAME = 'metode_perhitungan'
  ) THEN
    ALTER TABLE `monev`
      ADD COLUMN `metode_perhitungan` VARCHAR(20) NULL
      COMMENT 'sum | trend_naik | trend_turun | trend_flat'
      AFTER `capaian_triwulan_4`;
  END IF;

  -- 2) total jadi desimal (persentase tanpa simbol %)
  IF EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'monev'
      AND COLUMN_NAME = 'total' AND DATA_TYPE <> 'decimal'
  ) THEN
    -- Cadangkan dulu nilai lama (sekali saja).
    IF NOT EXISTS (
      SELECT 1 FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '_backup_monev_total_20260727'
    ) THEN
      CREATE TABLE `_backup_monev_total_20260727` AS
        SELECT `id`, `target_rencana_id`, `target_sub_rencana_id`, `total`
        FROM `monev`;
    END IF;

    -- Teks yang bukan angka tidak bisa dikonversi pada mode STRICT -> nolkan dulu.
    UPDATE `monev`
      SET `total` = NULL
      WHERE `total` IS NOT NULL
        AND (`total` = '' OR `total` NOT REGEXP '^-?[0-9]+(\\.[0-9]+)?$');

    ALTER TABLE `monev`
      MODIFY COLUMN `total` DECIMAL(10,2) NULL
      COMMENT 'Capaian Total dalam PERSEN, tanpa simbol % (mis. 86.67). Dihitung otomatis.';
  END IF;

  -- 3) kosongkan total warisan (angka mutlak, bukan persentase)
  UPDATE `monev` SET `total` = NULL WHERE `metode_perhitungan` IS NULL;
END$$
DELIMITER ;

CALL _monev_metode_upgrade();
DROP PROCEDURE IF EXISTS _monev_metode_upgrade;
