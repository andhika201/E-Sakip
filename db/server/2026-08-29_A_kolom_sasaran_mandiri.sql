-- =====================================================================
-- [SERVER] KOLOM `iku_sasaran.renstra_tujuan_id`
-- Pengganti  : db/update_2026-08-29_sasaran_iku_mandiri.sql
-- Tanggal    : 2026-08-29
--
-- =====================================================================
-- MENGAPA ADA VERSI TERSENDIRI UNTUK SERVER
--
-- Skrip aslinya membuat dirinya aman-diulang dengan menanyakan
-- `information_schema` lebih dulu. Pengguna basis data di server Anda tidak
-- diizinkan membacanya:
--
--     #1044 - Access denied for user 'esakippringsewu'@'localhost'
--             to database 'information_schema'
--
-- MySQL juga tidak mengenal `ALTER TABLE ... ADD COLUMN IF NOT EXISTS`
-- (itu MariaDB), jadi tidak ada cara menulis penjaga yang setara. Maka
-- penjaganya dilepas, dan tugas "aman-diulang" berpindah ke Anda:
--
--     JALANKAN SEKALI. Bila muncul galat DUPLIKAT, artinya bagian itu
--     memang sudah ada — abaikan dan lanjutkan ke pernyataan berikutnya.
--
-- Galat yang AMAN diabaikan di sini:
--     #1060 Duplicate column name 'renstra_tujuan_id'
--     #1061 Duplicate key name 'idx_iku_sasaran_renstra_tujuan'
--     #1826 Duplicate foreign key constraint name
--
-- Galat LAIN jangan diabaikan — hentikan dan tanyakan.
--
-- =====================================================================
-- APA YANG DILAKUKAN
--
-- Menambah satu kolom nullable tempat SASARAN MANDIRI menyebut sendiri
-- tujuan Renstra tempatnya bernaung. Sasaran hasil sync tidak terpengaruh:
-- kolomnya tetap NULL, dan tujuannya tetap diturunkan lewat
-- `source_sasaran_id` seperti selama ini.
--
-- Tidak ada baris yang berubah nilainya. Tidak ada DROP, tidak ada DELETE.
-- =====================================================================


-- ---- 1. kolomnya -----------------------------------------------------
ALTER TABLE `iku_sasaran`
  ADD COLUMN `renstra_tujuan_id` INT UNSIGNED NULL
      COMMENT 'tujuan Renstra bagi sasaran yang LAHIR di IKU; NULL = ikut source_sasaran_id'
      AFTER `source_sasaran_id`;


-- ---- 2. indeksnya ----------------------------------------------------
ALTER TABLE `iku_sasaran`
  ADD KEY `idx_iku_sasaran_renstra_tujuan` (`renstra_tujuan_id`);


-- ---- 3. kunci asingnya ----------------------------------------------
-- ON DELETE SET NULL, bukan CASCADE: tujuan Renstra yang dihapus tidak boleh
-- ikut menghapus sasaran IKU beserta seluruh cascading di bawahnya. Barisnya
-- kembali tak bertujuan — kelihatan, bisa diperbaiki, tidak hilang.
ALTER TABLE `iku_sasaran`
  ADD CONSTRAINT `fk_iku_sasaran_renstra_tujuan`
      FOREIGN KEY (`renstra_tujuan_id`) REFERENCES `renstra_tujuan` (`id`)
      ON DELETE SET NULL ON UPDATE CASCADE;


-- ---- 4. periksa hasilnya --------------------------------------------
-- SHOW, bukan information_schema.
SHOW COLUMNS FROM `iku_sasaran` LIKE 'renstra_tujuan_id';
SHOW INDEX  FROM `iku_sasaran` WHERE Key_name = 'idx_iku_sasaran_renstra_tujuan';

-- Harus 0: kolom baru, belum ada yang mengisinya.
SELECT COUNT(*) AS sasaran_dengan_tujuan_mandiri
FROM iku_sasaran WHERE renstra_tujuan_id IS NOT NULL;
