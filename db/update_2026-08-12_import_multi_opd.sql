-- =====================================================================
-- IMPORT PROGRAM/KEGIATAN/SUB KEGIATAN — CAKUPAN "SELURUH OPD"
-- Tanggal : 2026-08-12
-- Sifat   : IDEMPOTEN - aman dijalankan ulang. ADDITIVE — tidak menyentuh
--           program_pk / kegiatan_pk / sub_kegiatan_pk sama sekali.
--
-- Tujuan  : Menampung hasil parsing Lampiran 8 untuk SEMUA unit dalam satu
--           file, supaya unit yang OPD-nya belum bisa ditentukan dapat
--           dipetakan manual TANPA mengunggah ulang Excel.
--
-- Tabel produksi baru diisi saat sebuah unit selesai dipetakan, sehingga
-- tidak ada Program/Kegiatan/Sub berstatus pending yang bocor ke produksi.
--
-- Jalankan: mysql -u root NAMA_DB < db/update_2026-08-12_import_multi_opd.sql
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1) BATCH IMPORT — satu baris per unggahan file
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `import_batch` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tahun`          YEAR NOT NULL,
  `jenis_anggaran` VARCHAR(20) NOT NULL DEFAULT 'murni',
  `mode_import`    VARCHAR(20) NOT NULL DEFAULT 'seluruh' COMMENT 'per_opd | seluruh',
  `nama_file`      VARCHAR(255) NULL,
  `status`         VARCHAR(20) NOT NULL DEFAULT 'pending_mapping'
                   COMMENT 'pending_mapping | selesai | dibatalkan',
  `jumlah_unit`    INT UNSIGNED NOT NULL DEFAULT 0,
  `jumlah_pending` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_by`     INT UNSIGNED NULL,
  `created_at`     DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_batch_status` (`status`, `tahun`),
  KEY `idx_batch_pembuat` (`created_by`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Batch unggahan Lampiran 8 (staging import multi-OPD)';

-- ---------------------------------------------------------------------
-- 2) UNIT DALAM BATCH — satu baris per blok unit di Excel
--
-- `opd_id` sengaja TIDAK diberi FK NOT NULL: unit yang belum dipetakan
-- bernilai NULL, dan NULL berarti "belum ada OPD", bukan OPD default.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `import_batch_unit` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `batch_id`         INT UNSIGNED NOT NULL,
  `kode_unit_excel`  VARCHAR(50) NULL,
  `nama_unit_excel`  VARCHAR(255) NOT NULL,
  `opd_id`           INT UNSIGNED NULL COMMENT 'NULL = belum dipetakan',
  `mapping_status`   VARCHAR(20) NOT NULL DEFAULT 'pending_mapping'
                     COMMENT 'resolved | pending_mapping | imported | dilewati',
  `mapping_method`   VARCHAR(20) NULL COMMENT 'exact | alias | parent_rule | manual',
  `saran_opd_id`     INT UNSIGNED NULL COMMENT 'hasil fuzzy — SARAN saja, tidak pernah auto-commit',
  `saran_skor`       DECIMAL(5,2) NULL,
  `jumlah_program`   INT UNSIGNED NOT NULL DEFAULT 0,
  `jumlah_kegiatan`  INT UNSIGNED NOT NULL DEFAULT 0,
  `jumlah_sub`       INT UNSIGNED NOT NULL DEFAULT 0,
  `total_anggaran`   DECIMAL(20,2) NOT NULL DEFAULT 0,
  `urutan`           INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at`       DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_bu_batch_status` (`batch_id`, `mapping_status`),
  KEY `idx_bu_opd` (`opd_id`),
  CONSTRAINT `fk_bu_batch` FOREIGN KEY (`batch_id`)
    REFERENCES `import_batch` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Blok unit hasil parsing Lampiran 8 beserta status pemetaan OPD';

-- ---------------------------------------------------------------------
-- 3) BARIS HASIL PARSING — Program/Kegiatan/Sub milik satu unit
--
-- Kode disimpan sebagai JALUR PENUH yang sudah dinormalisasi
-- (A+B+C+D, +E, +F) supaya finalisasi cukup menyalin apa adanya dan
-- hierarki tidak mungkin berubah antara parsing dan finalisasi.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `import_batch_row` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `batch_unit_id`  INT UNSIGNED NOT NULL,
  `level`          VARCHAR(10) NOT NULL COMMENT 'program | kegiatan | sub',
  `kode_program`   VARCHAR(80) NOT NULL,
  `kode_kegiatan`  VARCHAR(80) NULL,
  `kode_sub`       VARCHAR(80) NULL,
  `nomenklatur`    VARCHAR(40) NULL COMMENT 'B.C.D(.E) — untuk menamai induk yang dibuat otomatis',
  `uraian`         TEXT NOT NULL,
  `anggaran`       DECIMAL(20,2) NULL COMMENT 'NULL = sel kolom K kosong, bukan 0',
  `punya_baris`    TINYINT(1) NOT NULL DEFAULT 1 COMMENT '0 = induk yang tidak punya barisnya sendiri di Excel',
  `baris_excel`    INT UNSIGNED NULL,
  `urutan`         INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_br_unit_urut` (`batch_unit_id`, `urutan`),
  KEY `idx_br_unit_level` (`batch_unit_id`, `level`),
  CONSTRAINT `fk_br_unit` FOREIGN KEY (`batch_unit_id`)
    REFERENCES `import_batch_unit` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Staging Program/Kegiatan/Sub sebelum unit-nya selesai dipetakan';

-- ---------------------------------------------------------------------
-- 4) ALIAS UNIT -> OPD — supaya mapping manual dapat dipakai ulang
--
-- Dicoba SEBELUM fuzzy matching pada import berikutnya.
-- `nama_unit_normal` = nama unit Excel yang sudah dinormalisasi
-- (huruf besar, spasi tunggal, tanpa tanda baca).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `opd_import_alias` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kode_unit`        VARCHAR(50) NULL COMMENT 'kode unit SIPD bila tersedia',
  `nama_unit_normal` VARCHAR(255) NOT NULL,
  `nama_unit_asli`   VARCHAR(255) NULL,
  `opd_id`           INT UNSIGNED NOT NULL,
  `created_by`       INT UNSIGNED NULL,
  `created_at`       DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_alias_nama` (`nama_unit_normal`),
  KEY `idx_alias_kode` (`kode_unit`),
  KEY `idx_alias_opd` (`opd_id`),
  CONSTRAINT `fk_alias_opd` FOREIGN KEY (`opd_id`)
    REFERENCES `opd` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Alias nama unit Excel -> master OPD, hasil mapping manual';

-- ---------------------------------------------------------------------
-- 5) PERMISSION — pemetaan unit import memakai modul program_pk yang sudah
--    ada (halaman mapping berada di bawah adminkab/program_pk/...), jadi
--    tidak ada permission baru. Bagian ini hanya memastikan admin_kab
--    benar-benar punya izin create/update pada modul tersebut.
-- ---------------------------------------------------------------------
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `roles` r
JOIN `permissions` p ON p.name IN ('program_pk.create', 'program_pk.update')
WHERE r.name = 'admin_kab'
  AND NOT EXISTS (
    SELECT 1 FROM `role_permissions` rp
    WHERE rp.role_id = r.id AND rp.permission_id = p.id
  );

-- ---------------------------------------------------------------------
-- 6) ALIAS AWAL (opsional) — hanya dibuat bila master OPD-nya memang ada.
--    Nama di Excel SIPD kerap berbeda dengan nomenklatur master, contoh
--    "RSUD Pringsewu" vs "RUMAH SAKIT UMUM DAERAH PRINGSEWU".
-- ---------------------------------------------------------------------
INSERT INTO `opd_import_alias` (`nama_unit_normal`, `nama_unit_asli`, `opd_id`, `created_at`, `updated_at`)
SELECT 'RSUD PRINGSEWU', 'RSUD Pringsewu', o.id, NOW(), NOW()
FROM `opd` o
WHERE UPPER(o.nama_opd) LIKE 'RUMAH SAKIT UMUM DAERAH%'
  AND NOT EXISTS (SELECT 1 FROM `opd_import_alias` a WHERE a.nama_unit_normal = 'RSUD PRINGSEWU')
LIMIT 1;
