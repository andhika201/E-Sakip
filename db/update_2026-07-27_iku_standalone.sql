-- =====================================================================
-- IKU STANDALONE (mandiri, tidak terikat RENSTRA / RPJMD)
-- Tanggal : 2026-07-27
-- Sifat   : IDEMPOTEN - aman dijalankan ulang. ADDITIVE (tidak menghapus
--           tabel/data lama; tabel `iku` & `iku_program_pendukung` dibiarkan
--           apa adanya sebagai cadangan/rollback).
--
-- Tujuan  : Sebelumnya IKU cuma tabel pelengkap (`iku`) yang menempel ke
--           `renstra_indikator_sasaran` / `rpjmd_indikator_sasaran`, sehingga
--           Sasaran, Indikator, Satuan, dan Target per tahun tidak bisa
--           diinput sendiri di modul IKU.
--           Skema baru ini membuat IKU berdiri sendiri:
--             iku_sasaran  -> iku_indikator -> iku_target
--                                           -> iku_program
--           Pemilik data ditentukan `iku_sasaran.opd_id`:
--             * opd_id IS NULL  = IKU tingkat Kabupaten  (admin_kab)
--             * opd_id terisi   = IKU OPD / Kecamatan    (admin_opd, admin_kecamatan)
--
-- Engine  : InnoDB, utf8mb4. Satu-satunya FK keluar adalah opd_id -> opd(id);
--           tidak ada FK ke renstra/rpjmd (itu inti perubahannya).
--
-- Jalankan: mysql -u root test_sakip < db/update_2026-07-27_iku_standalone.sql
-- Lanjutan: php db/migrasi_iku_standalone.php   (menyalin data IKU lama)
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1. SASARAN IKU
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `iku_sasaran` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `opd_id`      INT UNSIGNED NULL COMMENT 'NULL = IKU tingkat kabupaten; terisi = IKU OPD/Kecamatan',
  `sasaran`     TEXT NOT NULL,
  `tahun_mulai` INT NOT NULL,
  `tahun_akhir` INT NOT NULL,
  `urutan`      INT NOT NULL DEFAULT 0,
  `created_at`  DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_iku_sasaran_opd` (`opd_id`),
  KEY `idx_iku_sasaran_periode` (`tahun_mulai`, `tahun_akhir`),
  CONSTRAINT `fk_iku_sasaran_opd` FOREIGN KEY (`opd_id`) REFERENCES `opd` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
-- 2. INDIKATOR IKU
--    `satuan` mengikuti pola tabel lain: menyimpan id numerik ke `satuan.id`
--    bila dipilih dari dropdown, atau teks bebas bila diketik manual.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `iku_indikator` (
  `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `iku_sasaran_id`      INT UNSIGNED NOT NULL,
  `indikator`           TEXT NOT NULL,
  `definisi`            TEXT NULL COMMENT 'Definisi Operasional',
  `rumusan_perhitungan` TEXT NULL COMMENT 'Formula / Rumusan Perhitungan',
  `satuan`              VARCHAR(50) NULL COMMENT 'id satuan (numerik) atau teks bebas',
  `sumber_data`         TEXT NULL,
  `penanggung_jawab`    VARCHAR(255) NULL,
  `jenis_indikator`     VARCHAR(100) NULL COMMENT 'positif / negatif',
  `baseline`            VARCHAR(50) NULL COMMENT 'kondisi awal',
  `urutan`              INT NOT NULL DEFAULT 0,
  `status`              VARCHAR(20) NOT NULL DEFAULT 'draft' COMMENT 'draft | selesai',
  `created_at`          DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_iku_indikator_sasaran` (`iku_sasaran_id`),
  KEY `idx_iku_indikator_status` (`status`),
  CONSTRAINT `fk_iku_indikator_sasaran` FOREIGN KEY (`iku_sasaran_id`) REFERENCES `iku_sasaran` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
-- 3. TARGET TAHUNAN IKU
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `iku_target` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `iku_indikator_id` INT UNSIGNED NOT NULL,
  `tahun`            INT NOT NULL,
  `target`           VARCHAR(100) NULL,
  `created_at`       DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_iku_target` (`iku_indikator_id`, `tahun`),
  CONSTRAINT `fk_iku_target_indikator` FOREIGN KEY (`iku_indikator_id`) REFERENCES `iku_indikator` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
-- 4. PROGRAM PENDUKUNG IKU
--    (tampilannya masih dinonaktifkan di UI, tapi datanya tetap ditampung
--     supaya isi `iku_program_pendukung` lama tidak hilang)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `iku_program` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `iku_indikator_id` INT UNSIGNED NOT NULL,
  `program`          TEXT NOT NULL,
  `urutan`           INT NOT NULL DEFAULT 0,
  `created_at`       DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_iku_program_indikator` (`iku_indikator_id`),
  CONSTRAINT `fk_iku_program_indikator` FOREIGN KEY (`iku_indikator_id`) REFERENCES `iku_indikator` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
-- Catatan drift: tabel `iku_sasaran` sempat didefinisikan di migration
-- 2025-07-08-011656 dengan FK wajib ke `renstra_sasaran`, tapi tidak pernah
-- benar-benar dibuat di DB (tercatat di tabel `migrations` saja). Migration
-- tersebut sudah dinonaktifkan dan digantikan oleh
-- 2026-07-27-000001_CreateIkuStandaloneTables.
-- Kalau di suatu server tabel lama itu TERLANJUR ada dengan bentuk lama,
-- jalankan dulu (setelah backup):
--   DROP TABLE IF EXISTS `iku_target_tahunan`, `iku_indikator_kinerja`, `iku_sasaran`;
-- lalu jalankan ulang file ini.
-- ---------------------------------------------------------------------
