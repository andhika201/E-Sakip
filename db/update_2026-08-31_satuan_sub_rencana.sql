-- =====================================================================
-- SATUAN PADA SUB RENCANA AKSI
-- Tanggal : 2026-08-31
-- Sifat   : ADDITIVE. Satu kolom nullable pada `target_sub_rencana`.
--           Tidak ada DROP, tidak ada DELETE, tidak ada nilai yang berubah.
--
-- =====================================================================
-- MENGAPA DIPERLUKAN
--
-- Tiap sub rencana aksi punya target per triwulan (`target_triwulan_1..4`)
-- yang selama ini berupa ANGKA TELANJANG: "12", "3", "100". Tanpa satuan,
-- pembacanya harus menebak — 12 dokumen? 12 persen? 12 kegiatan?
--
-- Satuan pada tingkat INDIKATOR sudah ada (lewat `pk_indikator.id_satuan`),
-- tetapi itu satuan indikatornya, bukan satuan sub rencana aksinya. Satu
-- indikator bersatuan "Persen" lazim dirinci jadi sub-sub yang targetnya
-- dihitung dalam "Dokumen" atau "Kegiatan".
--
-- =====================================================================
-- MENGAPA TEKS, BUKAN FOREIGN KEY KE `satuan`
--
-- Nilainya disimpan sebagai NAMA satuan, bukan id-nya. Dua alasan:
--
--   1. Baris ini adalah CATATAN RENCANA yang dibaca bertahun kemudian.
--      Bila master satuan kelak diubah namanya atau dihapus, rencana lama
--      tidak boleh ikut berubah bunyinya — pola yang sama dengan
--      `iku_revisi_indikator.satuan_nama` yang sengaja dibekukan.
--   2. Tidak ada join yang perlu ditambahkan pada tabel index maupun
--      cetakan, yang keduanya sudah lebar.
--
-- Konsistensi tetap dijaga di sisi ISIAN: form menyodorkan daftar dari
-- master `satuan`, jadi operator memilih, bukan mengetik bebas.
-- =====================================================================

SELECT '========== SEBELUM ==========' AS laporan;
SHOW COLUMNS FROM `target_sub_rencana` LIKE 'satuan';


-- ---------------------------------------------------------------------
-- KOLOMNYA
--
-- MySQL tidak mengenal `ADD COLUMN IF NOT EXISTS`. JALANKAN SEKALI.
-- Galat #1060 (kolom duplikat) berarti bagian ini memang sudah ada —
-- abaikan. Galat LAIN jangan diabaikan.
-- ---------------------------------------------------------------------
ALTER TABLE `target_sub_rencana`
  ADD COLUMN `satuan` VARCHAR(50) NULL
      COMMENT 'nama satuan target triwulan sub ini; NULL = mengikuti satuan indikator'
      AFTER `sub_rencana_aksi`;


SELECT '========== SESUDAH ==========' AS laporan;
SHOW COLUMNS FROM `target_sub_rencana` LIKE 'satuan';

SELECT '-- sub rencana yang sudah bersatuan --' AS laporan;
SELECT COUNT(*) AS bersatuan FROM `target_sub_rencana` WHERE `satuan` IS NOT NULL AND `satuan` <> '';
