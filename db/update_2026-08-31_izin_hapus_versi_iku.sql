-- =====================================================================
-- PERMOHONAN HAPUS VERSI IKU
-- Tanggal : 2026-08-31
-- Sifat   : ADDITIVE. Satu kolom pada `dokumen_izin_sunting`.
--           Tidak ada DROP, tidak ada DELETE. Baris lama diberi nilai
--           'sunting' sehingga perilakunya persis seperti sebelumnya.
--
-- =====================================================================
-- APA YANG DIBANGUN
--
-- Menghapus sebuah versi IKU adalah tindakan yang tidak bisa dibatalkan,
-- jadi ia tidak boleh berada di tangan penyusunnya sendiri. Alurnya
-- disamakan dengan izin sunting yang sudah berjalan:
--
--   1. OPD menekan "Ajukan Penghapusan" + alasan   -> pending
--   2. Admin Kabupaten memeriksa                    -> setujui / tolak
--   3. Bila DISETUJUI, versinya dihapus SAAT ITU JUGA
--
-- Langkah 3 sengaja tidak dipecah menjadi "disetujui lalu OPD menekan
-- hapus". Keadaan "sudah disetujui tapi belum dihapus" hanya menambah satu
-- pintu yang bisa basi, dan yang memikul akibat penghapusan sebaiknya
-- adalah orang yang memutuskannya.
--
-- =====================================================================
-- MENGAPA MENUMPANG TABEL YANG SAMA
--
-- `dokumen_izin_sunting` sudah punya seluruh yang dibutuhkan: lingkup
-- (modul/scope/opd/periode), sasaran (`version_id`), alasan, jejak siapa
-- meminta dan siapa memutuskan, serta antrean Admin Kabupaten yang sudah
-- dirender. Membuat tabel kedua yang isinya sama berarti dua antrean, dua
-- layar, dan dua tempat aturan kewenangan bisa menyimpang.
--
-- Yang membedakan keduanya cukup satu kolom: `jenis`.
--
-- PENTING: `aktif_key` (kolom generated yang menjaga "hanya satu permohonan
-- berjalan per dokumen") SENGAJA TIDAK diubah. Satu dokumen tetap hanya
-- boleh punya SATU permohonan menggantung, apa pun jenisnya — memohon hapus
-- sementara permohonan sunting masih menggantung berarti dua keputusan yang
-- saling meniadakan atas dokumen yang sama.
-- =====================================================================

SELECT '========== SEBELUM ==========' AS laporan;
SHOW COLUMNS FROM `dokumen_izin_sunting` LIKE 'jenis';


-- ---------------------------------------------------------------------
-- KOLOMNYA
--
-- MySQL tidak mengenal `ADD COLUMN IF NOT EXISTS`. JALANKAN SEKALI.
-- Galat #1060 (kolom duplikat) / #1061 (indeks duplikat) berarti bagian itu
-- memang sudah ada — abaikan, lanjutkan. Galat LAIN jangan diabaikan.
-- ---------------------------------------------------------------------
ALTER TABLE `dokumen_izin_sunting`
  ADD COLUMN `jenis` VARCHAR(12) NOT NULL DEFAULT 'sunting'
      COMMENT 'sunting = buka kunci; hapus = permohonan menghapus versi'
      AFTER `version_id`;

ALTER TABLE `dokumen_izin_sunting`
  ADD KEY `idx_izin_jenis` (`jenis`);


-- ---------------------------------------------------------------------
-- Baris lama seluruhnya permohonan SUNTING. DEFAULT sudah mengisinya, tetapi
-- ditegaskan supaya basis data yang kolomnya telanjur dibuat NULL ikut rapi.
-- ---------------------------------------------------------------------
UPDATE `dokumen_izin_sunting`
   SET `jenis` = 'sunting'
 WHERE `jenis` IS NULL OR `jenis` = '';


SELECT '========== SESUDAH ==========' AS laporan;
SHOW COLUMNS FROM `dokumen_izin_sunting` LIKE 'jenis';

SELECT '-- sebaran jenis permohonan --' AS laporan;
SELECT `jenis`, COUNT(*) AS n FROM `dokumen_izin_sunting` GROUP BY `jenis`;
