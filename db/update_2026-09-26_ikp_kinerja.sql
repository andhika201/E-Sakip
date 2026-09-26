-- =====================================================================
-- AKSARA+ : Indikator Kinerja Prioritas (IKP) + SAKIP sampai pelaksana
-- Kembar SQL dari migrasi:
--   2026-09-26-000001_CreateIkpTables
--   2026-09-26-000002_CreateCascadingPemilikTarget
--   2026-09-26-000003_SeedIkpPermissions
-- Untuk server tanpa CLI (phpMyAdmin): jalankan berkas ini utuh. IDEMPOTEN:
-- semua CREATE memakai IF NOT EXISTS dan semua INSERT memakai WHERE NOT EXISTS.
-- Referensi (9 Program Unggulan, 10 Sasaran Pembangunan, 319 Buku Saku) diisi
-- terpisah lewat `php spark db:seed IkpReferensiSeeder` — datanya ada di
-- app/Database/Seeds/data/ikp_*.json.
--
-- MENGAPA tabel-tabel ini TIDAK menyentuh tabel lama:
--   * target & realisasi bulanan IKP disimpan di ikp_bulanan yang TIDAK ber-FK ke
--     iku_target — IkuModel::updateIndikator()/timpaDariSumber() menghapus lalu
--     menyisipkan ulang iku_target setiap kali IKU disunting, sehingga FK CASCADE
--     ke sana akan menghapus data bulanan diam-diam.
--   * ikp_bulanan juga tidak ber-FK ke ikp_target_tahunan: mengosongkan target
--     tahunan tidak boleh ikut menghapus bulanannya (jebakan prototipe Prioritas).
--   * ikp memakai SOFT DELETE (dihapus_pada) karena eKin merujuk ikp.id.
-- =====================================================================

CREATE TABLE IF NOT EXISTS `ikp_program_unggulan` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(50) NOT NULL,
  `warna` VARCHAR(7) NULL,
  `ikon` VARCHAR(50) NULL,
  `urutan` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ikp_pu_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `ikp_sasaran_pembangunan` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nama` VARCHAR(255) NOT NULL,
  `urutan` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `ikp_buku_saku` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `rpjmd_misi_id` INT UNSIGNED NULL,
  `program_unggulan_id` INT UNSIGNED NULL,
  `program_unggulan_teks` VARCHAR(255) NULL,
  `sasaran_pembangunan_id` INT UNSIGNED NULL,
  `sasaran_pembangunan_teks` VARCHAR(255) NULL,
  `outcome` TEXT NULL,
  `indikator` TEXT NULL,
  `program_opd` TEXT NULL,
  `output_prioritas` TEXT NULL,
  `satuan` VARCHAR(255) NULL,
  `target_5_tahun` VARCHAR(255) NULL,
  `bidang_urusan` VARCHAR(255) NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ikp_bs_pu` (`program_unggulan_id`),
  CONSTRAINT `fk_ikp_bs_pu` FOREIGN KEY (`program_unggulan_id`) REFERENCES `ikp_program_unggulan` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_ikp_bs_sp` FOREIGN KEY (`sasaran_pembangunan_id`) REFERENCES `ikp_sasaran_pembangunan` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `ikp` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `opd_id` INT UNSIGNED NOT NULL,
  `periode_awal` SMALLINT UNSIGNED NOT NULL,
  `periode_akhir` SMALLINT UNSIGNED NOT NULL,
  `kategori` VARCHAR(30) NOT NULL COMMENT 'program_unggulan|program_prioritas|penugasan_khusus|penugasan_tambahan',
  `program_unggulan_id` INT UNSIGNED NULL,
  `rpjmd_misi_id` INT UNSIGNED NULL,
  `sasaran_pembangunan_id` INT UNSIGNED NULL,
  `outcome` TEXT NULL,
  `indikator_outcome` TEXT NULL COMMENT 'indikator outcome (kolom INDIKATOR Lampiran II)',
  `program_opd` TEXT NULL,
  `bidang_urusan` VARCHAR(255) NULL,
  `output_prioritas` TEXT NOT NULL COMMENT 'NAMA INDIKATOR IKP (KPI yang diukur bulanan)',
  `satuan_id` INT UNSIGNED NULL,
  `satuan_teks` VARCHAR(100) NULL,
  `metode` VARCHAR(20) NULL COMMENT 'sum|trend_naik|trend_turun|trend_flat (kosakata capaian_helper)',
  `baseline` DECIMAL(20,4) NULL,
  `target_5_tahun` DECIMAL(20,4) NULL,
  `target_5_tahun_teks` VARCHAR(255) NULL,
  `dasar_penugasan` VARCHAR(255) NULL COMMENT 'penugasan khusus/tambahan: arahan/surat',
  `buku_saku_id` INT UNSIGNED NULL,
  `cascading_sasaran_id` INT UNSIGNED NULL,
  `cascading_indikator_id` INT UNSIGNED NULL,
  `pj_pegawai_id` INT NULL COMMENT 'pegawai.id (INT signed, tanpa FK — pola pk.pihak_1)',
  `pj_jabatan_teks` VARCHAR(255) NULL,
  `urutan` INT NOT NULL DEFAULT 0,
  `legacy_prioritas_ikp_id` INT NULL COMMENT 'ikp.id di prototipe Prioritas (upsert impor)',
  `created_by` INT UNSIGNED NULL,
  `updated_by` INT UNSIGNED NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  `dihapus_pada` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ikp_opd_periode` (`opd_id`, `periode_awal`, `periode_akhir`),
  KEY `idx_ikp_pj` (`pj_pegawai_id`),
  KEY `idx_ikp_legacy` (`legacy_prioritas_ikp_id`),
  CONSTRAINT `fk_ikp_opd` FOREIGN KEY (`opd_id`) REFERENCES `opd` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ikp_pu` FOREIGN KEY (`program_unggulan_id`) REFERENCES `ikp_program_unggulan` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_ikp_sp` FOREIGN KEY (`sasaran_pembangunan_id`) REFERENCES `ikp_sasaran_pembangunan` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_ikp_satuan` FOREIGN KEY (`satuan_id`) REFERENCES `satuan` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_ikp_bs` FOREIGN KEY (`buku_saku_id`) REFERENCES `ikp_buku_saku` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_ikp_casc_sas` FOREIGN KEY (`cascading_sasaran_id`) REFERENCES `cascading_sasaran_opd` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_ikp_casc_ind` FOREIGN KEY (`cascading_indikator_id`) REFERENCES `cascading_indikator_opd` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `ikp_target_tahunan` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ikp_id` INT UNSIGNED NOT NULL,
  `tahun` SMALLINT UNSIGNED NOT NULL,
  `target` DECIMAL(20,4) NULL,
  `target_teks` VARCHAR(255) NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ikp_tahunan` (`ikp_id`, `tahun`),
  CONSTRAINT `fk_ikp_tahunan_ikp` FOREIGN KEY (`ikp_id`) REFERENCES `ikp` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `ikp_bulanan` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ikp_id` INT UNSIGNED NOT NULL,
  `tahun` SMALLINT UNSIGNED NOT NULL,
  `bulan` TINYINT UNSIGNED NOT NULL COMMENT '1..12 (dicek di PHP)',
  `target` DECIMAL(20,4) NULL,
  `target_teks` VARCHAR(255) NULL,
  `realisasi` DECIMAL(20,4) NULL COMMENT 'NULL = belum diisi; 0 = nol (terisi)',
  `realisasi_teks` VARCHAR(255) NULL,
  `keterangan` TEXT NULL,
  `bukti_url` VARCHAR(500) NULL,
  `realisasi_oleh` INT UNSIGNED NULL COMMENT 'users.user_id',
  `realisasi_pada` DATETIME NULL COMMENT 'UTC seperti seluruh AKSARA',
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ikp_bulan` (`ikp_id`, `tahun`, `bulan`),
  CONSTRAINT `fk_ikp_bulanan_ikp` FOREIGN KEY (`ikp_id`) REFERENCES `ikp` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `ikp_inovasi` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `opd_id` INT UNSIGNED NOT NULL,
  `tahun` SMALLINT UNSIGNED NOT NULL,
  `nama` VARCHAR(255) NOT NULL,
  `deskripsi` TEXT NULL,
  `ikp_id` INT UNSIGNED NULL,
  `urutan` INT NOT NULL DEFAULT 0,
  `legacy_prioritas_id` INT NULL,
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ikp_inovasi_opd` (`opd_id`, `tahun`),
  CONSTRAINT `fk_ikp_inovasi_opd` FOREIGN KEY (`opd_id`) REFERENCES `opd` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ikp_inovasi_ikp` FOREIGN KEY (`ikp_id`) REFERENCES `ikp` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
-- SAKIP sampai pelaksana: pemilik simpul pohon kinerja + target tahunan
-- MENGAPA hanya pemilik yang CASCADE: updateEs3/Es4/Pelaksana menghapus subpohon
-- indikator yang dibuang dari form. Pemilik adalah konfigurasi (boleh ikut hilang);
-- IKP yang menunjuk simpul memakai SET NULL + teks snapshot di eKin.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cascading_pemilik` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `cascading_sasaran_id` INT UNSIGNED NOT NULL,
  `opd_id` INT UNSIGNED NOT NULL COMMENT 'denormal dari simpul, untuk filter',
  `tahun` SMALLINT UNSIGNED NOT NULL,
  `pegawai_id` INT NOT NULL COMMENT 'pegawai.id (INT signed, tanpa FK)',
  `jabatan_teks` VARCHAR(255) NULL COMMENT 'snapshot jabatan saat ditetapkan',
  `peran` VARCHAR(20) NOT NULL DEFAULT 'penanggung_jawab' COMMENT 'penanggung_jawab|anggota',
  `is_plt` TINYINT(1) NOT NULL DEFAULT 0,
  `sumber` VARCHAR(10) NOT NULL DEFAULT 'manual' COMMENT 'manual|pk|seed',
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_casc_pemilik` (`cascading_sasaran_id`, `tahun`, `pegawai_id`),
  KEY `idx_casc_pemilik_pegawai` (`pegawai_id`, `tahun`),
  KEY `idx_casc_pemilik_opd` (`opd_id`, `tahun`),
  CONSTRAINT `fk_casc_pemilik_node` FOREIGN KEY (`cascading_sasaran_id`) REFERENCES `cascading_sasaran_opd` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `cascading_indikator_target` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `cascading_indikator_id` INT UNSIGNED NOT NULL,
  `tahun` SMALLINT UNSIGNED NOT NULL,
  `target` DECIMAL(20,4) NULL,
  `target_teks` VARCHAR(100) NULL,
  `metode` VARCHAR(20) NOT NULL DEFAULT 'sum' COMMENT 'sum|trend_naik|trend_turun|trend_flat',
  `ikp_id` INT UNSIGNED NULL COMMENT 'bila indikator ini turunan langsung IKP (target bulanan mengikuti IKP)',
  `created_at` DATETIME NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_casc_target` (`cascading_indikator_id`, `tahun`),
  CONSTRAINT `fk_casc_target_ind` FOREIGN KEY (`cascading_indikator_id`) REFERENCES `cascading_indikator_opd` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_casc_target_ikp` FOREIGN KEY (`ikp_id`) REFERENCES `ikp` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
-- Permission (kembar IkpPermissionSeeder::terapkan)
-- ---------------------------------------------------------------------
INSERT INTO `permissions` (`name`,`label`,`grup`,`created_at`,`updated_at`)
SELECT x.name, x.label, x.grup, NOW(), NOW() FROM (
  SELECT 'ikp_opd.view' AS name, 'Kinerja Prioritas (IKP) OPD - Lihat' AS label, 'OPD' AS grup UNION ALL
  SELECT 'ikp_opd.create', 'Kinerja Prioritas (IKP) OPD - Tambah', 'OPD' UNION ALL
  SELECT 'ikp_opd.update', 'Kinerja Prioritas (IKP) OPD - Ubah', 'OPD' UNION ALL
  SELECT 'ikp_opd.delete', 'Kinerja Prioritas (IKP) OPD - Hapus', 'OPD' UNION ALL
  SELECT 'pemilik_kinerja.view', 'Pemilik Kinerja (sampai Pelaksana) - Lihat', 'OPD' UNION ALL
  SELECT 'pemilik_kinerja.update', 'Pemilik Kinerja (sampai Pelaksana) - Ubah', 'OPD' UNION ALL
  SELECT 'pemilik_kinerja.delete', 'Pemilik Kinerja (sampai Pelaksana) - Hapus', 'OPD' UNION ALL
  SELECT 'ikp_kab.view', 'Kinerja Prioritas (IKP) Kabupaten - Lihat', 'Kabupaten' UNION ALL
  SELECT 'ikp_kab.create', 'Kinerja Prioritas (IKP) Kabupaten - Tambah', 'Kabupaten' UNION ALL
  SELECT 'ikp_kab.update', 'Kinerja Prioritas (IKP) Kabupaten - Ubah', 'Kabupaten' UNION ALL
  SELECT 'ikp_kab.delete', 'Kinerja Prioritas (IKP) Kabupaten - Hapus', 'Kabupaten' UNION ALL
  SELECT 'ikp_bupati_monitoring.view', 'Monitoring Kinerja Prioritas (IKP) - Lihat', 'Bupati'
) x WHERE NOT EXISTS (SELECT 1 FROM `permissions` p WHERE p.name = x.name);

INSERT INTO `role_permissions` (`role_id`,`permission_id`)
SELECT r.id, p.id FROM `roles` r JOIN `permissions` p
  ON p.name IN ('ikp_opd.view','ikp_opd.create','ikp_opd.update','ikp_opd.delete','pemilik_kinerja.view','pemilik_kinerja.update','pemilik_kinerja.delete')
WHERE r.name IN ('admin_opd','admin_kecamatan')
  AND NOT EXISTS (SELECT 1 FROM `role_permissions` x WHERE x.role_id = r.id AND x.permission_id = p.id);

INSERT INTO `role_permissions` (`role_id`,`permission_id`)
SELECT r.id, p.id FROM `roles` r JOIN `permissions` p
  ON p.name IN ('ikp_kab.view','ikp_kab.create','ikp_kab.update','ikp_kab.delete','ikp_opd.view','pemilik_kinerja.view')
WHERE r.name = 'admin_kab'
  AND NOT EXISTS (SELECT 1 FROM `role_permissions` x WHERE x.role_id = r.id AND x.permission_id = p.id);

INSERT INTO `role_permissions` (`role_id`,`permission_id`)
SELECT r.id, p.id FROM `roles` r JOIN `permissions` p
  ON p.name IN ('ikp_kab.view','ikp_opd.view','pemilik_kinerja.view')
WHERE r.name = 'admin_inspektorat'
  AND NOT EXISTS (SELECT 1 FROM `role_permissions` x WHERE x.role_id = r.id AND x.permission_id = p.id);

INSERT INTO `role_permissions` (`role_id`,`permission_id`)
SELECT r.id, p.id FROM `roles` r JOIN `permissions` p ON p.name = 'ikp_bupati_monitoring.view'
WHERE r.name = 'bupati'
  AND NOT EXISTS (SELECT 1 FROM `role_permissions` x WHERE x.role_id = r.id AND x.permission_id = p.id);
