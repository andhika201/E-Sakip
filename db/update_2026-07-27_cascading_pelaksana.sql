-- =====================================================================
-- CASCADING: JENJANG BARU "PELAKSANA" (setelah Eselon IV / JF)
-- Tanggal : 2026-07-27
-- Sifat   : IDEMPOTEN - aman dijalankan ulang. ADDITIVE.
--
-- ---------------------------------------------------------------------
-- KENAPA TIDAK ADA TABEL BARU
--
-- Struktur cascading OPD sudah self-similar dan bertingkat sendiri:
--
--   cascading_sasaran_opd (id, opd_id, renstra_indikator_sasaran_id,
--                          parent_id, es3_indikator_id, level, nama_sasaran, csf)
--   cascading_indikator_opd (id, cascading_sasaran_id, indikator, satuan)
--
-- Rantai yang sudah berjalan:
--   renstra_indikator_sasaran (indikator ES II)
--     -> cascading_sasaran_opd  level='es3'  (renstra_indikator_sasaran_id)
--       -> cascading_indikator_opd            (cascading_sasaran_id)
--         -> cascading_sasaran_opd level='es4' (es3_indikator_id = id indikator ES III)
--           -> cascading_indikator_opd
--
-- Jenjang Pelaksana mengikuti pola YANG SAMA PERSIS, cukup satu tingkat lagi:
--           -> cascading_sasaran_opd level='pelaksana' (es3_indikator_id = id indikator ES IV)
--             -> cascading_indikator_opd
--
-- Jadi yang diperlukan HANYA menambah nilai 'pelaksana' pada enum `level`.
-- Membuat tabel sasaran_pelaksana / indikator_pelaksana terpisah akan menjadi
-- duplikat struktur yang sudah ada.
--
-- CATATAN kolom `es3_indikator_id`: namanya warisan, artinya sebenarnya
-- "INDIKATOR INDUK". Untuk baris level='es4' isinya id indikator ES III,
-- untuk baris level='pelaksana' isinya id indikator ES IV. Keduanya sama-sama
-- menunjuk cascading_indikator_opd.id dan dibedakan oleh kolom `level`,
-- sehingga tidak ada ambiguitas. Kolom TIDAK diganti nama agar seluruh query,
-- FK, dan data lama tetap jalan.
--
-- Kepemilikan & periode ikut pola lama:
--   - OPD    : kolom `opd_id` (diisi dari session, bukan dari request)
--   - Periode: lewat renstra_indikator_sasaran -> renstra_sasaran.tahun_mulai/akhir
--              (kolom `renstra_indikator_sasaran_id` diwariskan dari induknya)
-- ---------------------------------------------------------------------
--
-- Jalankan: mysql -u root test_sakip < db/update_2026-07-27_cascading_pelaksana.sql
-- =====================================================================

DROP PROCEDURE IF EXISTS _cascading_pelaksana_upgrade;
DELIMITER $$
CREATE PROCEDURE _cascading_pelaksana_upgrade()
BEGIN
  -- Tambahkan 'pelaksana' pada enum level bila belum ada.
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'cascading_sasaran_opd'
      AND COLUMN_NAME  = 'level'
      AND COLUMN_TYPE LIKE '%pelaksana%'
  ) THEN
    ALTER TABLE `cascading_sasaran_opd`
      MODIFY COLUMN `level` ENUM('es2','es3','es4','pelaksana') NOT NULL
      COMMENT 'jenjang cascading; pelaksana = di bawah Eselon IV/JF';
  END IF;

  -- Index bantu: penelusuran anak per indikator induk + level.
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'cascading_sasaran_opd'
      AND INDEX_NAME   = 'idx_cascading_level_indikator'
  ) THEN
    ALTER TABLE `cascading_sasaran_opd`
      ADD INDEX `idx_cascading_level_indikator` (`level`, `es3_indikator_id`);
  END IF;
END$$
DELIMITER ;

CALL _cascading_pelaksana_upgrade();
DROP PROCEDURE IF EXISTS _cascading_pelaksana_upgrade;
