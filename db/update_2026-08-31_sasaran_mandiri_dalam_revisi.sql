-- =====================================================================
-- SASARAN MANDIRI LAHIR DI DALAM REVISI IKU
-- Tanggal : 2026-08-31
-- Sifat   : ADDITIVE. Satu kolom nullable pada `iku_revisi_sasaran`.
--           Tidak ada DROP, tidak ada DELETE, tidak ada nilai yang berubah.
--
-- =====================================================================
-- MASALAH YANG DISELESAIKAN
--
-- "Tambah Sasaran Mandiri" selama ini adalah pintu TERSENDIRI yang menulis
-- LANGSUNG ke tabel live (`IkuController::save()` -> `createComplete()`),
-- di luar alur revisi. Dua akibatnya nyata:
--
--   1. IKU berjalan berubah TANPA revisi. Padahal seluruh modul ini dibangun
--      di atas janji sebaliknya: LAKIP tahun lampau membaca arsip beku, dan
--      arsip itu hanya bertambah lewat revisi yang disahkan. Sasaran yang
--      lahir di luar revisi tidak pernah masuk arsip mana pun, sehingga ia
--      muncul di IKU berjalan tetapi TIDAK ADA di dokumen penilaian tahun
--      mana pun.
--   2. Tidak ada pengesahan. Sasaran baru langsung berlaku begitu disimpan,
--      tanpa lewat meja Admin Kabupaten.
--
-- Setelah perubahan ini, sasaran baru ditambahkan DI DALAM layar sunting
-- draft revisi, bersebelahan dengan "Tambah Indikator", dan ikut alur
-- ajukan -> sahkan seperti perubahan lainnya.
--
-- =====================================================================
-- MENGAPA BUTUH KOLOM BARU
--
-- `iku_sasaran` (live) sudah punya `renstra_tujuan_id` sejak
-- db/update_2026-08-29_sasaran_iku_mandiri.sql — sasaran mandiri WAJIB
-- menyebut tujuan Renstra tempatnya bernaung, sebab tanpa itu barisnya
-- muncul di Cascading dengan kolom-kolom kosong, di luar blok Tujuan mana pun.
--
-- `iku_revisi_sasaran` (arsip) belum punya padanannya. Tanpa kolom ini,
-- sasaran mandiri yang dibuat di dalam revisi akan kehilangan tujuannya
-- begitu revisi disahkan dan arsipnya diterapkan ke live — dan hilangnya
-- tidak bergejala sampai ada yang membuka Cascading.
--
-- Kolom ini juga membuat sasaran mandiri yang SUDAH ADA di live tidak
-- kehilangan tujuannya saat dibekukan ke revisi berikutnya.
-- =====================================================================

SELECT '========== SEBELUM ==========' AS laporan;
SHOW COLUMNS FROM `iku_revisi_sasaran` LIKE 'renstra_tujuan_id';


-- ---------------------------------------------------------------------
-- KOLOMNYA
--
-- MySQL tidak mengenal `ADD COLUMN IF NOT EXISTS` (itu MariaDB), dan
-- pengguna basis data di server tidak diizinkan membaca `information_schema`
-- sehingga penjaga aman-diulang tidak bisa ditulis. JALANKAN SEKALI.
--
-- Bila muncul galat DUPLIKAT, artinya bagian itu memang sudah ada — abaikan
-- dan lanjutkan:
--     #1060 Duplicate column name 'renstra_tujuan_id'
--     #1061 Duplicate key name 'idx_iku_revisi_sasaran_tujuan'
--
-- Galat LAIN jangan diabaikan.
-- ---------------------------------------------------------------------
ALTER TABLE `iku_revisi_sasaran`
  ADD COLUMN `renstra_tujuan_id` INT UNSIGNED NULL
      COMMENT 'tujuan Renstra bagi sasaran yang LAHIR di IKU; NULL = ikut source_ref_id'
      AFTER `source_ref_id`;

ALTER TABLE `iku_revisi_sasaran`
  ADD KEY `idx_iku_revisi_sasaran_tujuan` (`renstra_tujuan_id`);


-- ---------------------------------------------------------------------
-- SENGAJA TANPA FOREIGN KEY
--
-- Mengikuti `iku_sasaran.renstra_tujuan_id` yang juga longgar: arsip revisi
-- adalah CATATAN SEJARAH. Bila kelak tujuan Renstra-nya dihapus, arsip tahun
-- lampau tidak boleh ikut berubah bunyinya — dan ON DELETE SET NULL justru
-- akan mengubahnya. Nilai yatim di sini lebih jujur daripada nilai yang
-- diam-diam dikosongkan.
-- ---------------------------------------------------------------------


-- ---------------------------------------------------------------------
-- MENURUNKAN TUJUAN UNTUK ARSIP YANG SUDAH TERLANJUR DIBEKUKAN
--
-- Arsip yang dibuat sebelum kolom ini ada tidak menyimpan tujuannya. Yang
-- masih bisa dipulihkan diambil dari baris live yang dirujuknya. Baris yang
-- live-nya pun kosong dibiarkan NULL — tidak ada yang bisa ditebak.
-- ---------------------------------------------------------------------
UPDATE `iku_revisi_sasaran` ars
  JOIN `iku_sasaran` liv ON liv.id = ars.sumber_sasaran_id
   SET ars.renstra_tujuan_id = liv.renstra_tujuan_id
 WHERE ars.renstra_tujuan_id IS NULL
   AND liv.renstra_tujuan_id IS NOT NULL;


SELECT '========== SESUDAH ==========' AS laporan;
SHOW COLUMNS FROM `iku_revisi_sasaran` LIKE 'renstra_tujuan_id';

SELECT '-- arsip sasaran yang kini bertujuan --' AS laporan;
SELECT COUNT(*) AS bertujuan FROM `iku_revisi_sasaran` WHERE `renstra_tujuan_id` IS NOT NULL;
