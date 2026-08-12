-- =====================================================================
-- LAKIP BENCHMARK PROVINSI & NASIONAL
-- Tanggal : 2026-08-12
-- Sifat   : IDEMPOTEN - aman dijalankan ulang. ADDITIVE (tidak mengubah
--           satu pun tabel/kolom LAKIP yang sudah ada).
--
-- Tujuan  : Menyimpan nilai pembanding Provinsi Lampung & Nasional untuk
--           indikator RPJMD (tingkat kabupaten) atau Renstra (tingkat OPD)
--           pada tahun tertentu. Nilai Kabupaten/OPD TIDAK disimpan di sini
--           -- tetap dibaca dari `lakip.capaian_tahun_ini` seperti biasa.
--
-- Catatan bentuk data:
--   Persis pola `lakip_analisis_faktor`: salah satu dari kolom
--   `rpjmd_indikator_id` / `renstra_indikator_id` yang terisi, sisanya NULL.
--   Penambatnya INDIKATOR (bukan target) supaya tidak lahir indikator baru
--   khusus benchmark, dan satu indikator hanya punya 1 baris per tahun.
--
--   UNIQUE dengan kolom NULL: MySQL mengizinkan banyak baris NULL, jadi
--   uq_benchmark_rpjmd tidak mengganggu baris renstra (dan sebaliknya).
--
-- Jalankan: mysql -u root NAMA_DB < db/update_2026-08-12_lakip_benchmark.sql
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1) TABEL BENCHMARK
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lakip_benchmark` (
  `id`                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `rpjmd_indikator_id`   INT UNSIGNED NULL COMMENT 'mode Kabupaten (RPJMD); salah satu saja yang terisi',
  `renstra_indikator_id` INT UNSIGNED NULL COMMENT 'mode OPD (Renstra); salah satu saja yang terisi',
  `opd_id`               INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 = tingkat kabupaten (RPJMD)',
  `tahun`                YEAR NOT NULL,
  `nilai_provinsi`       DECIMAL(20,4) NULL COMMENT 'NULL = belum ada data, BUKAN 0',
  `nilai_nasional`       DECIMAL(20,4) NULL COMMENT 'NULL = belum ada data, BUKAN 0',
  `sumber_provinsi`      VARCHAR(255) NULL,
  `sumber_nasional`      VARCHAR(255) NULL,
  `catatan`              TEXT NULL,
  `created_by`           INT UNSIGNED NULL,
  `updated_by`           INT UNSIGNED NULL,
  `created_at`           DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`           DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_benchmark_rpjmd`   (`rpjmd_indikator_id`, `tahun`),
  UNIQUE KEY `uq_benchmark_renstra` (`renstra_indikator_id`, `tahun`),
  KEY `idx_benchmark_opd_tahun` (`opd_id`, `tahun`),
  CONSTRAINT `fk_benchmark_rpjmd_indikator` FOREIGN KEY (`rpjmd_indikator_id`)
    REFERENCES `rpjmd_indikator_sasaran` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_benchmark_renstra_indikator` FOREIGN KEY (`renstra_indikator_id`)
    REFERENCES `renstra_indikator_sasaran` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Pembanding Provinsi & Nasional untuk indikator LAKIP';

-- ---------------------------------------------------------------------
-- 2) PERMISSION
--
-- Benchmark dipisah dari lakip_kab.*/lakip_opd.* dengan sengaja: role OPD
-- boleh menulis LAKIP-nya sendiri, tetapi TIDAK boleh mengubah benchmark.
-- Karena itu dibuat modul izin tersendiri.
--   lakip_benchmark.view   -> semua role yang boleh membaca LAKIP
--   lakip_benchmark.manage -> hanya admin_kab (role 'admin' otomatis lolos
--                             lewat user_can(), tidak perlu baris izin)
-- ---------------------------------------------------------------------
INSERT INTO `permissions` (`name`, `label`, `grup`, `created_at`, `updated_at`)
SELECT 'lakip_benchmark.view', 'LAKIP Benchmark Provinsi/Nasional - Lihat', 'Kabupaten', NOW(), NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'lakip_benchmark.view');

INSERT INTO `permissions` (`name`, `label`, `grup`, `created_at`, `updated_at`)
SELECT 'lakip_benchmark.manage', 'LAKIP Benchmark Provinsi/Nasional - Input/Ubah', 'Kabupaten', NOW(), NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'lakip_benchmark.manage');

-- 2a) HAK LIHAT untuk semua role yang memang boleh membaca LAKIP.
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `roles` r
JOIN `permissions` p ON p.name = 'lakip_benchmark.view'
WHERE r.name IN ('admin_kab', 'admin_opd', 'admin_inspektorat', 'admin_kecamatan', 'bupati')
  AND NOT EXISTS (
    SELECT 1 FROM `role_permissions` rp
    WHERE rp.role_id = r.id AND rp.permission_id = p.id
  );

-- 2b) HAK UBAH hanya untuk admin_kab.
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `roles` r
JOIN `permissions` p ON p.name = 'lakip_benchmark.manage'
WHERE r.name = 'admin_kab'
  AND NOT EXISTS (
    SELECT 1 FROM `role_permissions` rp
    WHERE rp.role_id = r.id AND rp.permission_id = p.id
  );
