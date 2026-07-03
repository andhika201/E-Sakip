-- #####################################################################
-- E-SAKIP CI — BUNDLE UPDATE UNTUK UPLOAD KE DATABASE SERVER
-- Dibuat  : 2026-07-03
-- Isi     : Gabungan SEMUA update inkremental (2026-06-13 s/d 2026-07-02)
--           dalam urutan kronologis. Semua bagian IDEMPOTEN & ADDITIVE
--           (aman dijalankan berulang, tidak menghapus data pengguna).
--
-- CARA PAKAI
-- ==========
-- A) Database server SUDAH ADA (sudah pernah import test_sakip.sql):
--      mysql -u USER -p NAMA_DB < db/update_2026-07-02_UPLOAD_SERVER.sql
--
-- B) Database server MASIH KOSONG (fresh):
--      1) buat database:  CREATE DATABASE nama_db CHARACTER SET utf8mb4;
--      2) import struktur+data awal:
--           mysql -u USER -p nama_db < db/test_sakip.sql
--      3) baru jalankan bundle ini:
--           mysql -u USER -p nama_db < db/update_2026-07-02_UPLOAD_SERVER.sql
--
-- C) Lewat phpMyAdmin: pilih database -> tab "Import" -> unggah file ini.
--
-- CATATAN: file ini memakai perintah DELIMITER (untuk stored procedure
--          idempoten). Jalankan lewat mysql client atau phpMyAdmin —
--          keduanya mengenali DELIMITER. Jangan dijalankan lewat driver
--          yang mengeksekusi satu-statement tanpa dukungan DELIMITER.
-- #####################################################################


-- #####################################################################
-- >>> BAGIAN: update_2026-06-13.sql
-- #####################################################################
-- =====================================================================
--  E-SAKIP CI - Update Database (2026-06-13)
--  Mencakup: IKU (penanggung_jawab + normalisasi status), RBAC
--  (roles/permissions/role_permissions + seed), kolom simpeg_id pada
--  opd/pangkat/jabatan (integrasi SIMPEG), dan tabel activity_logs.
--
--  Aman dijalankan berulang (idempoten) di MySQL 8 / MariaDB.
--  Jalankan pada database aplikasi (mis. test_sakip):
--     mysql -u root -p test_sakip < db/update_2026-06-13.sql
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1) IKU: kolom penanggung_jawab + normalisasi kolom status -> draft/selesai
-- ---------------------------------------------------------------------

-- tambah kolom penanggung_jawab bila belum ada
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'iku' AND COLUMN_NAME = 'penanggung_jawab');
SET @s := IF(@c = 0,
    'ALTER TABLE `iku` ADD COLUMN `penanggung_jawab` VARCHAR(255) NULL AFTER `definisi`',
    'DO 0');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- normalisasi nilai status SEBELUM ubah tipe kolom
UPDATE `iku` SET `status` = 'selesai' WHERE LOWER(TRIM(`status`)) = 'tercapai';
UPDATE `iku` SET `status` = 'draft'
    WHERE `status` IS NULL OR LOWER(TRIM(`status`)) NOT IN ('draft', 'selesai');

-- samakan tipe kolom (idempoten)
ALTER TABLE `iku` MODIFY `status` VARCHAR(20) NOT NULL DEFAULT 'draft';

-- ---------------------------------------------------------------------
-- 2) RBAC: tabel roles, permissions, role_permissions
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `roles` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(50)  NOT NULL,
    `label`      VARCHAR(100) NULL,
    `is_system`  TINYINT(1)   NOT NULL DEFAULT 0,
    `created_at` DATETIME     NULL,
    `updated_at` DATETIME     NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `permissions` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(100) NOT NULL,
    `label`      VARCHAR(150) NULL,
    `grup`       VARCHAR(50)  NULL,
    `created_at` DATETIME     NULL,
    `updated_at` DATETIME     NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `role_permissions` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `role_id`       INT UNSIGNED NOT NULL,
    `permission_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `rp` (`role_id`, `permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2a) seed ROLES (name = slug yang sama dengan users.role)
INSERT IGNORE INTO `roles` (`name`, `label`, `is_system`, `created_at`, `updated_at`) VALUES
    ('admin',     'Super Admin',     1, NOW(), NOW()),
    ('admin_kab', 'Admin Kabupaten', 1, NOW(), NOW()),
    ('admin_opd', 'Admin OPD',       1, NOW(), NOW());

-- 2b) Hapus permission lama berbasis ".manage" (digantikan CRUD)
DELETE rp FROM `role_permissions` rp
    JOIN `permissions` p ON p.id = rp.permission_id
    WHERE p.name LIKE '%.manage';
DELETE FROM `permissions` WHERE name LIKE '%.manage';

-- 2c) Permission khusus (non-CRUD)
INSERT IGNORE INTO `permissions` (`name`, `label`, `grup`, `created_at`, `updated_at`) VALUES
    ('dashboard.view',     'Lihat Dashboard',          'Umum',        NOW(), NOW()),
    ('master.access',      'Akses Panel Master Data',  'Master Data', NOW(), NOW()),
    ('tentang_kami.view',  'Tentang Kami - Lihat',     'Umum',        NOW(), NOW()),
    ('tentang_kami.update','Tentang Kami - Ubah',      'Umum',        NOW(), NOW());

-- 2d) Permission CRUD untuk SEMUA modul (modul x aksi)
INSERT IGNORE INTO `permissions` (`name`, `label`, `grup`, `created_at`, `updated_at`)
SELECT CONCAT(m.k, '.', a.k), CONCAT(m.l, ' - ', a.l), m.g, NOW(), NOW()
FROM (
    SELECT 'pegawai' k, 'Pegawai' l, 'Master Data' g
    UNION ALL SELECT 'pangkat','Pangkat','Master Data'
    UNION ALL SELECT 'jabatan','Jabatan','Master Data'
    UNION ALL SELECT 'opd','OPD','Master Data'
    UNION ALL SELECT 'user','User','Master Data'
    UNION ALL SELECT 'role','Role','Master Data'
    UNION ALL SELECT 'satuan','Satuan','Master Data'
    UNION ALL SELECT 'rpjmd','RPJMD','Kabupaten'
    UNION ALL SELECT 'rkpd','RKPD','Kabupaten'
    UNION ALL SELECT 'iku_kab','IKU Kabupaten','Kabupaten'
    UNION ALL SELECT 'cascading_kab','Cascading Kabupaten','Kabupaten'
    UNION ALL SELECT 'rkt_kab','RKT Kabupaten','Kabupaten'
    UNION ALL SELECT 'target_kab','Target Kabupaten','Kabupaten'
    UNION ALL SELECT 'monev_kab','Monev Kabupaten','Kabupaten'
    UNION ALL SELECT 'lakip_kab','LAKIP Kabupaten','Kabupaten'
    UNION ALL SELECT 'program_pk','Program PK','Kabupaten'
    UNION ALL SELECT 'pk_bupati','PK Bupati','Kabupaten'
    UNION ALL SELECT 'renstra','Renstra','OPD'
    UNION ALL SELECT 'rkt_opd','RKT OPD','OPD'
    UNION ALL SELECT 'iku_opd','IKU OPD','OPD'
    UNION ALL SELECT 'cascading_opd','Cascading OPD','OPD'
    UNION ALL SELECT 'target_opd','Target OPD','OPD'
    UNION ALL SELECT 'monev_opd','Monev OPD','OPD'
    UNION ALL SELECT 'lakip_opd','LAKIP OPD','OPD'
    UNION ALL SELECT 'pk_opd','PK OPD','OPD'
) m
CROSS JOIN (
    SELECT 'view' k, 'Lihat' l
    UNION ALL SELECT 'create','Tambah'
    UNION ALL SELECT 'update','Ubah'
    UNION ALL SELECT 'delete','Hapus'
) a;

-- 2e) seed ROLE_PERMISSIONS
-- admin (super admin) -> SEMUA permission
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r CROSS JOIN `permissions` p WHERE r.name = 'admin';

-- admin_kab -> semua Kabupaten + Umum
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r JOIN `permissions` p
    ON (p.grup = 'Kabupaten' OR p.name IN ('dashboard.view','tentang_kami.view','tentang_kami.update'))
WHERE r.name = 'admin_kab';

-- admin_opd -> semua OPD + Umum
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id FROM `roles` r JOIN `permissions` p
    ON (p.grup = 'OPD' OR p.name IN ('dashboard.view','tentang_kami.view','tentang_kami.update'))
WHERE r.name = 'admin_opd';

-- ---------------------------------------------------------------------
-- 3) Kolom simpeg_id pada opd / pangkat / jabatan (mapping ID SIMPEG)
-- ---------------------------------------------------------------------

-- opd.simpeg_id
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'opd' AND COLUMN_NAME = 'simpeg_id');
SET @s := IF(@c = 0, 'ALTER TABLE `opd` ADD COLUMN `simpeg_id` VARCHAR(50) NULL AFTER `id`', 'DO 0');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @i := (SELECT COUNT(*) FROM information_schema.STATISTICS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'opd' AND INDEX_NAME = 'idx_opd_simpeg_id');
SET @s := IF(@i = 0, 'CREATE INDEX `idx_opd_simpeg_id` ON `opd` (`simpeg_id`)', 'DO 0');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- pangkat.simpeg_id
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pangkat' AND COLUMN_NAME = 'simpeg_id');
SET @s := IF(@c = 0, 'ALTER TABLE `pangkat` ADD COLUMN `simpeg_id` VARCHAR(50) NULL AFTER `id`', 'DO 0');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @i := (SELECT COUNT(*) FROM information_schema.STATISTICS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pangkat' AND INDEX_NAME = 'idx_pangkat_simpeg_id');
SET @s := IF(@i = 0, 'CREATE INDEX `idx_pangkat_simpeg_id` ON `pangkat` (`simpeg_id`)', 'DO 0');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- jabatan.simpeg_id
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'jabatan' AND COLUMN_NAME = 'simpeg_id');
SET @s := IF(@c = 0, 'ALTER TABLE `jabatan` ADD COLUMN `simpeg_id` VARCHAR(50) NULL AFTER `id`', 'DO 0');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @i := (SELECT COUNT(*) FROM information_schema.STATISTICS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'jabatan' AND INDEX_NAME = 'idx_jabatan_simpeg_id');
SET @s := IF(@i = 0, 'CREATE INDEX `idx_jabatan_simpeg_id` ON `jabatan` (`simpeg_id`)', 'DO 0');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ---------------------------------------------------------------------
-- 4) Tabel activity_logs (log aktivitas pengguna)
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`     INT          NULL,
    `username`    VARCHAR(100) NULL,
    `role`        VARCHAR(50)  NULL,
    `action`      VARCHAR(50)  NULL,
    `module`      VARCHAR(100) NULL,
    `description` VARCHAR(255) NULL,
    `method`      VARCHAR(10)  NULL,
    `url`         VARCHAR(255) NULL,
    `ip_address`  VARCHAR(45)  NULL,
    `user_agent`  VARCHAR(255) NULL,
    `created_at`  DATETIME     NULL,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `action` (`action`),
    KEY `module` (`module`),
    KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 5) Kolom 2FA (TOTP authenticator) pada users
-- ---------------------------------------------------------------------
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'two_factor_secret');
SET @s := IF(@c = 0, 'ALTER TABLE `users` ADD COLUMN `two_factor_secret` VARCHAR(64) NULL AFTER `password`', 'DO 0');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'two_factor_enabled');
SET @s := IF(@c = 0, 'ALTER TABLE `users` ADD COLUMN `two_factor_enabled` TINYINT(1) NOT NULL DEFAULT 0 AFTER `two_factor_secret`', 'DO 0');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- =====================================================================
--  Selesai.
--  Catatan: kolom Renstra `baseline` & `jenis_indikator` sudah ada di skema,
--  jadi tidak ada perubahan tabel untuk modul Renstra.
-- =====================================================================

DELIMITER ;

-- #####################################################################
-- >>> BAGIAN: update_2026-06-28.sql
-- #####################################################################
-- =====================================================================
--  E-SAKIP CI - Update Database (2026-06-28)
--  Mencakup: tabel app_settings (Pengaturan Aplikasi) — nama web,
--  instansi/footer, logo, favicon, logo pengembang + serial number, SEO.
--
--  Aman dijalankan berulang (idempoten) di MySQL 8 / MariaDB.
--  Jalankan pada database aplikasi (mis. test_sakip):
--     mysql -u root -p test_sakip < db/update_2026-06-28.sql
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1) Tabel app_settings (key-value)
--    Dibaca aplikasi lewat helper setting() (app/Helpers/setting_helper.php),
--    dikelola Super Admin di halaman adminkab/pengaturan.
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `app_settings` (
    `skey`       VARCHAR(64) NOT NULL,
    `svalue`     TEXT        NULL,
    `updated_at` TIMESTAMP   NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`skey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 2) Seed nilai default (INSERT IGNORE -> tidak menimpa nilai yang sudah diubah)
-- ---------------------------------------------------------------------

INSERT IGNORE INTO `app_settings` (`skey`, `svalue`) VALUES
    ('app_name',         'e-SAKIP'),
    ('app_long_name',    'Sistem Akuntabilitas Kinerja Instansi Pemerintah'),
    ('instansi',         'Pemerintah Kabupaten Pringsewu'),
    ('instansi_address', 'Komplek Perkantoran Pemerintah Daerah Pringsewu, Lampung'),
    ('instansi_phone',   '+62-729-7531-567'),
    ('instansi_email',   'diskominfo@pringsewukab.go.id'),
    ('app_logo',         'assets/images/LogoTentang.png'),
    ('favicon',          'assets/images/sakipLogo.png'),
    ('dev_name',         'DevTech'),
    ('dev_logo',         'assets/images/devtech.png'),
    ('serial_number',    'ESAKIP-2025-001'),
    ('seo_description',  'e-SAKIP Kabupaten Pringsewu. Sistem Informasi Akuntabilitas Kinerja Instansi Pemerintah. Akses data RPJMD, RKPD, LAKIP, dan Kinerja secara transparan.'),
    ('seo_keywords',     'e-sakip, pringsewu, kabupaten pringsewu, akuntabilitas, kinerja, rpjmd, rkpd, lakip'),
    ('seo_author',       'DevTech - Dinas Komunikasi dan Informatika Kabupaten Pringsewu');

-- ---------------------------------------------------------------------
-- 3) Integrasi AI (Google Gemini) untuk fitur "Analisis AI"
--    gemini_api_key diisi via halaman Pengaturan Aplikasi (Super Admin).
-- ---------------------------------------------------------------------

INSERT IGNORE INTO `app_settings` (`skey`, `svalue`) VALUES
    ('gemini_api_key', ''),
    ('gemini_model',   'gemini-2.5-flash');

-- =====================================================================
--  Selesai.
--  Catatan: nilai logo/favicon yang diunggah via halaman Pengaturan
--  tersimpan di public/uploads/ dan path-nya menimpa nilai default di atas.
-- =====================================================================

DELIMITER ;

-- #####################################################################
-- >>> BAGIAN: update_2026-06-28_fk.sql
-- #####################################################################
-- =====================================================================
-- Penguatan Integritas Referensial SAKIP (RPJMD, Renstra, Cascading)
-- Tanggal   : 2026-06-28
-- Sifat     : IDEMPOTEN — aman dijalankan ulang.
-- Engine    : InnoDB (sudah terverifikasi).
-- Konvensi  : ON UPDATE CASCADE; ON DELETE CASCADE untuk relasi struktural
--             (induk-anak), ON DELETE SET NULL untuk soft-pointer nullable.
--             Mengikuti konvensi FK yang sudah ada di database.
-- Jalankan  : mysql -u root test_sakip < db/update_2026-06-28_fk.sql
-- Catatan   : Membersihkan 2 baris orphan renstra (opd_id=1 yang tidak ada
--             di tabel opd) beserta turunannya. Backup baris tsb ada di
--             db/backup_orphan_renstra_2026-06-28.sql (bisa di-restore).
-- =====================================================================

-- ---------------------------------------------------------------------
-- 1) BERSIHKAN DATA ORPHAN (renstra_sasaran.opd_id menunjuk OPD tak ada)
--    Urutan: target -> indikator -> sasaran (anak dulu, baru induk).
--    Hanya menghapus baris yang opd-nya benar-benar tidak ada.
-- ---------------------------------------------------------------------
DELETE t FROM renstra_target t
  JOIN renstra_indikator_sasaran i ON i.id = t.renstra_indikator_id
  JOIN renstra_sasaran s ON s.id = i.renstra_sasaran_id
  LEFT JOIN opd o ON o.id = s.opd_id
  WHERE o.id IS NULL;

DELETE i FROM renstra_indikator_sasaran i
  JOIN renstra_sasaran s ON s.id = i.renstra_sasaran_id
  LEFT JOIN opd o ON o.id = s.opd_id
  WHERE o.id IS NULL;

DELETE s FROM renstra_sasaran s
  LEFT JOIN opd o ON o.id = s.opd_id
  WHERE o.id IS NULL;

-- ---------------------------------------------------------------------
-- 2) HELPER: tambah FK hanya bila belum ada (idempoten)
-- ---------------------------------------------------------------------
DROP PROCEDURE IF EXISTS _add_fk_if_absent;
DELIMITER $$
CREATE PROCEDURE _add_fk_if_absent(IN p_table VARCHAR(64), IN p_name VARCHAR(64), IN p_ddl TEXT)
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME       = p_table
      AND CONSTRAINT_NAME  = p_name
      AND CONSTRAINT_TYPE  = 'FOREIGN KEY'
  ) THEN
    SET @sql = p_ddl;
    PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
  END IF;
END$$
DELIMITER ;

-- ---------------------------------------------------------------------
-- 3) FOREIGN KEY — RANTAI RPJMD
-- ---------------------------------------------------------------------
CALL _add_fk_if_absent('rpjmd_misi','fk_rpjmd_misi_visi',
  'ALTER TABLE rpjmd_misi ADD CONSTRAINT fk_rpjmd_misi_visi FOREIGN KEY (rpjmd_visi_id) REFERENCES rpjmd_visi(id) ON UPDATE CASCADE ON DELETE CASCADE');

CALL _add_fk_if_absent('rpjmd_tujuan','fk_rpjmd_tujuan_misi',
  'ALTER TABLE rpjmd_tujuan ADD CONSTRAINT fk_rpjmd_tujuan_misi FOREIGN KEY (misi_id) REFERENCES rpjmd_misi(id) ON UPDATE CASCADE ON DELETE CASCADE');

CALL _add_fk_if_absent('rpjmd_indikator_tujuan','fk_rpjmd_indtujuan_tujuan',
  'ALTER TABLE rpjmd_indikator_tujuan ADD CONSTRAINT fk_rpjmd_indtujuan_tujuan FOREIGN KEY (tujuan_id) REFERENCES rpjmd_tujuan(id) ON UPDATE CASCADE ON DELETE CASCADE');

CALL _add_fk_if_absent('rpjmd_target_tujuan','fk_rpjmd_targettujuan_ind',
  'ALTER TABLE rpjmd_target_tujuan ADD CONSTRAINT fk_rpjmd_targettujuan_ind FOREIGN KEY (indikator_tujuan_id) REFERENCES rpjmd_indikator_tujuan(id) ON UPDATE CASCADE ON DELETE CASCADE');

CALL _add_fk_if_absent('rpjmd_sasaran','fk_rpjmd_sasaran_tujuan',
  'ALTER TABLE rpjmd_sasaran ADD CONSTRAINT fk_rpjmd_sasaran_tujuan FOREIGN KEY (tujuan_id) REFERENCES rpjmd_tujuan(id) ON UPDATE CASCADE ON DELETE CASCADE');

CALL _add_fk_if_absent('rpjmd_indikator_sasaran','fk_rpjmd_indsasaran_sasaran',
  'ALTER TABLE rpjmd_indikator_sasaran ADD CONSTRAINT fk_rpjmd_indsasaran_sasaran FOREIGN KEY (sasaran_id) REFERENCES rpjmd_sasaran(id) ON UPDATE CASCADE ON DELETE CASCADE');

CALL _add_fk_if_absent('rpjmd_target','fk_rpjmd_target_indsasaran',
  'ALTER TABLE rpjmd_target ADD CONSTRAINT fk_rpjmd_target_indsasaran FOREIGN KEY (indikator_sasaran_id) REFERENCES rpjmd_indikator_sasaran(id) ON UPDATE CASCADE ON DELETE CASCADE');

-- ---------------------------------------------------------------------
-- 4) FOREIGN KEY — RANTAI RENSTRA (+ jembatan keselarasan ke RPJMD)
-- ---------------------------------------------------------------------
CALL _add_fk_if_absent('renstra_tujuan','fk_renstra_tujuan_rpjmd_sasaran',
  'ALTER TABLE renstra_tujuan ADD CONSTRAINT fk_renstra_tujuan_rpjmd_sasaran FOREIGN KEY (rpjmd_sasaran_id) REFERENCES rpjmd_sasaran(id) ON UPDATE CASCADE ON DELETE CASCADE');

CALL _add_fk_if_absent('renstra_sasaran','fk_renstra_sasaran_opd',
  'ALTER TABLE renstra_sasaran ADD CONSTRAINT fk_renstra_sasaran_opd FOREIGN KEY (opd_id) REFERENCES opd(id) ON UPDATE CASCADE ON DELETE CASCADE');

CALL _add_fk_if_absent('renstra_indikator_sasaran','fk_renstra_indsasaran_sasaran',
  'ALTER TABLE renstra_indikator_sasaran ADD CONSTRAINT fk_renstra_indsasaran_sasaran FOREIGN KEY (renstra_sasaran_id) REFERENCES renstra_sasaran(id) ON UPDATE CASCADE ON DELETE CASCADE');

CALL _add_fk_if_absent('renstra_target','fk_renstra_target_indikator',
  'ALTER TABLE renstra_target ADD CONSTRAINT fk_renstra_target_indikator FOREIGN KEY (renstra_indikator_id) REFERENCES renstra_indikator_sasaran(id) ON UPDATE CASCADE ON DELETE CASCADE');

-- ---------------------------------------------------------------------
-- 5) FOREIGN KEY — CASCADING (Pohon Kinerja OPD)
--    es3_indikator_id = soft-pointer nullable -> SET NULL (hindari siklus cascade)
-- ---------------------------------------------------------------------
CALL _add_fk_if_absent('cascading_sasaran_opd','fk_cascading_sasaran_opd',
  'ALTER TABLE cascading_sasaran_opd ADD CONSTRAINT fk_cascading_sasaran_opd FOREIGN KEY (opd_id) REFERENCES opd(id) ON UPDATE CASCADE ON DELETE CASCADE');

CALL _add_fk_if_absent('cascading_sasaran_opd','fk_cascading_es3_indikator',
  'ALTER TABLE cascading_sasaran_opd ADD CONSTRAINT fk_cascading_es3_indikator FOREIGN KEY (es3_indikator_id) REFERENCES cascading_indikator_opd(id) ON UPDATE CASCADE ON DELETE SET NULL');

-- ---------------------------------------------------------------------
-- 6) Bersihkan helper
-- ---------------------------------------------------------------------
DROP PROCEDURE IF EXISTS _add_fk_if_absent;

-- Selesai. IKU (iku.rpjmd_id / iku.renstra_id) sengaja DILEWATI sampai
-- makna kolomnya diverifikasi (lihat db/RELASI_SAKIP.md bagian 6).

DELIMITER ;

-- #####################################################################
-- >>> BAGIAN: update_2026-06-29_pk_renaksi.sql
-- #####################################################################
-- =====================================================================
-- Perluasan Monitoring Realisasi PK -> Rencana Aksi (Bupati & Eselon III)
-- Tanggal : 2026-06-29
-- Sifat   : IDEMPOTEN - aman dijalankan ulang. ADDITIVE (tidak menghapus data).
-- Tujuan  : Memberi target_rencana (Rencana Aksi) jangkar KETIGA ke indikator
--           PK (pk_indikator), di samping renstra_target_id & rpjmd_target_id,
--           agar realisasi PK Bupati (pk.jenis='bupati') dan PK Administrator/
--           Eselon III (pk.jenis='administrator') bisa diukur lewat MONEV
--           memakai mesin Rencana Aksi + MONEV yang sudah ada.
-- Engine  : InnoDB.
-- Konvensi: ON UPDATE CASCADE; ON DELETE CASCADE (renaksi tak bermakna tanpa
--           indikator induknya), mengikuti gaya db/update_2026-06-28_fk.sql.
-- Jalankan: mysql -u root test_sakip < db/update_2026-06-29_pk_renaksi.sql
-- Catatan : Kolom baru NULL-only saat dibuat, jadi penambahan FK aman terhadap
--           data lama (tidak ada baris yang melanggar).
-- =====================================================================

-- ---------------------------------------------------------------------
-- HELPER 1: tambah KOLOM hanya bila belum ada (idempoten)
-- ---------------------------------------------------------------------
DROP PROCEDURE IF EXISTS _add_col_if_absent;
DELIMITER $$
CREATE PROCEDURE _add_col_if_absent(IN p_table VARCHAR(64), IN p_col VARCHAR(64), IN p_ddl TEXT)
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = p_table
      AND COLUMN_NAME  = p_col
  ) THEN
    SET @sql = p_ddl; PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
  END IF;
END$$
DELIMITER ;

-- ---------------------------------------------------------------------
-- HELPER 2: tambah FOREIGN KEY hanya bila belum ada (idempoten)
-- ---------------------------------------------------------------------
DROP PROCEDURE IF EXISTS _add_fk_if_absent;
DELIMITER $$
CREATE PROCEDURE _add_fk_if_absent(IN p_table VARCHAR(64), IN p_name VARCHAR(64), IN p_ddl TEXT)
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME       = p_table
      AND CONSTRAINT_NAME  = p_name
      AND CONSTRAINT_TYPE  = 'FOREIGN KEY'
  ) THEN
    SET @sql = p_ddl; PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
  END IF;
END$$
DELIMITER ;

-- ---------------------------------------------------------------------
-- 1) Kolom jangkar baru: target_rencana.pk_indikator_id
--    (NULL = soft-pointer, sejajar renstra_target_id & rpjmd_target_id)
-- ---------------------------------------------------------------------
CALL _add_col_if_absent('target_rencana','pk_indikator_id',
  'ALTER TABLE target_rencana ADD COLUMN pk_indikator_id INT UNSIGNED NULL DEFAULT NULL AFTER rpjmd_target_id');

-- ---------------------------------------------------------------------
-- 2) Foreign Key target_rencana.pk_indikator_id -> pk_indikator.id
-- ---------------------------------------------------------------------
CALL _add_fk_if_absent('target_rencana','fk_target_rencana_pk_indikator',
  'ALTER TABLE target_rencana ADD CONSTRAINT fk_target_rencana_pk_indikator FOREIGN KEY (pk_indikator_id) REFERENCES pk_indikator(id) ON UPDATE CASCADE ON DELETE CASCADE');

-- ---------------------------------------------------------------------
-- 3) Bersihkan helper
-- ---------------------------------------------------------------------
DROP PROCEDURE IF EXISTS _add_col_if_absent;
DROP PROCEDURE IF EXISTS _add_fk_if_absent;

-- Selesai. Setelah ini, satu baris target_rencana boleh mengacu ke salah satu:
--   renstra_target_id  (Renstra / Eselon II / OPD)        -- sudah ada
--   rpjmd_target_id    (RPJMD / Kabupaten)                -- sudah ada
--   pk_indikator_id    (PK Bupati / PK Administrator-ES3) -- BARU

DELIMITER ;

-- #####################################################################
-- >>> BAGIAN: update_2026-06-30_iku_rumusan_sumber.sql
-- #####################################################################
-- =====================================================================
-- IKU: tambah kolom Formula/Rumusan Perhitungan & Sumber Data
-- Tanggal : 2026-06-30
-- Sifat   : IDEMPOTEN - aman dijalankan ulang. ADDITIVE (tidak menghapus data).
-- Tujuan  : Menyimpan "Formula/Rumusan Perhitungan" dan "Sumber Data" per IKU,
--           menggantikan tampilan kolom "Program Pendukung" yang dinonaktifkan.
-- Engine  : InnoDB. Kolom NULL-only -> aman terhadap baris lama.
-- Jalankan: mysql -u root test_sakip < db/update_2026-06-30_iku_rumusan_sumber.sql
-- =====================================================================

DROP PROCEDURE IF EXISTS _add_col_if_absent;
DELIMITER $$
CREATE PROCEDURE _add_col_if_absent(IN p_table VARCHAR(64), IN p_col VARCHAR(64), IN p_ddl TEXT)
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = p_table
      AND COLUMN_NAME  = p_col
  ) THEN
    SET @sql = p_ddl; PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
  END IF;
END$$
DELIMITER ;

CALL _add_col_if_absent('iku','rumusan_perhitungan',
  'ALTER TABLE iku ADD COLUMN rumusan_perhitungan TEXT NULL DEFAULT NULL AFTER definisi');

CALL _add_col_if_absent('iku','sumber_data',
  'ALTER TABLE iku ADD COLUMN sumber_data TEXT NULL DEFAULT NULL AFTER rumusan_perhitungan');

DROP PROCEDURE IF EXISTS _add_col_if_absent;

-- Selesai. Kolom baru: iku.rumusan_perhitungan, iku.sumber_data (keduanya NULL).

DELIMITER ;

-- #####################################################################
-- >>> BAGIAN: update_2026-07-02_pk_sasaran_opd.sql
-- #####################################################################
-- =====================================================================
-- Perangkat Daerah pendukung PK Bupati — mapping MANUAL (override otomatis)
-- Tanggal : 2026-07-02
-- Sifat   : IDEMPOTEN (CREATE TABLE IF NOT EXISTS). ADDITIVE.
-- Tujuan  : Simpan pemetaan manual Sasaran PK Bupati -> Perangkat Daerah (OPD)
--           pendukung, yang bisa diedit/ditambah dari halaman Target & Rencana
--           Aksi (kolom Aksi). Bila ada baris utk sebuah pk_sasaran_id, ia
--           MENGGANTIKAN hasil pencocokan otomatis (cascading) di tampilan.
-- Jalankan: mysql -u root test_sakip < db/update_2026-07-02_pk_sasaran_opd.sql
-- =====================================================================
CREATE TABLE IF NOT EXISTS `pk_sasaran_opd` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `pk_sasaran_id` INT UNSIGNED NOT NULL,
  `opd_id`        INT UNSIGNED NOT NULL,
  `created_at`    DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pksopd` (`pk_sasaran_id`, `opd_id`),
  KEY `idx_pksopd_sasaran` (`pk_sasaran_id`),
  KEY `idx_pksopd_opd` (`opd_id`),
  CONSTRAINT `fk_pksopd_sasaran` FOREIGN KEY (`pk_sasaran_id`) REFERENCES `pk_sasaran` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pksopd_opd`     FOREIGN KEY (`opd_id`)        REFERENCES `opd` (`id`)        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DELIMITER ;

-- #####################################################################
-- >>> BAGIAN: update_2026-07-02_rbac_kec_inspektorat.sql
-- #####################################################################
-- =====================================================================
-- RBAC: role admin_kecamatan (admin_kec) & admin_inspektorat
-- Tanggal : 2026-07-02
-- Sifat   : IDEMPOTEN (pakai NOT EXISTS) + ADDITIVE.
-- Tujuan  :
--   1) admin_kecamatan -> lengkapi izinnya SAMA seperti admin_opd
--      (kecamatan memakai modul yang sama dgn OPD: renstra/renja/iku/
--       cascading/pk/target/monev/lakip).
--   2) admin_inspektorat (BARU) -> read-only lintas OPD utk evaluasi:
--      RPJMD, Cascading, PK, LAKIP (+ dashboard & tentang kami).
-- Jalankan: mysql -u root test_sakip < db/update_2026-07-02_rbac_kec_inspektorat.sql
-- =====================================================================

-- ---------- 1. admin_kecamatan ----------
-- Tandai sbg role bawaan sistem (is_system=1) + set label.
UPDATE `roles`
SET `label`     = COALESCE(NULLIF(`label`, ''), 'Admin Kecamatan'),
    `is_system` = 1
WHERE `name` = 'admin_kecamatan';

-- Salin SEMUA izin admin_opd ke admin_kecamatan (yang belum ada saja).
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT rk.id, rp.permission_id
FROM `roles` rk
JOIN `roles` ro ON ro.name = 'admin_opd'
JOIN `role_permissions` rp ON rp.role_id = ro.id
WHERE rk.name = 'admin_kecamatan'
  AND NOT EXISTS (
    SELECT 1 FROM `role_permissions` x
    WHERE x.role_id = rk.id AND x.permission_id = rp.permission_id
  );

-- ---------- 2. admin_inspektorat ----------
INSERT INTO `roles` (`name`, `label`, `is_system`, `created_at`, `updated_at`)
SELECT 'admin_inspektorat', 'Admin Inspektorat', 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `roles` WHERE `name` = 'admin_inspektorat');
-- Bila role sudah ada dari run sebelumnya, pastikan ditandai sistem.
UPDATE `roles` SET `is_system` = 1 WHERE `name` = 'admin_inspektorat';

-- Izin read-only (view) untuk evaluasi lintas OPD.
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT ri.id, p.id
FROM `roles` ri
JOIN `permissions` p ON p.name IN (
  'dashboard.view',
  'tentang_kami.view',
  'rpjmd.view',
  'cascading_kab.view',
  'pk_bupati.view',
  'lakip_kab.view'
)
WHERE ri.name = 'admin_inspektorat'
  AND NOT EXISTS (
    SELECT 1 FROM `role_permissions` x
    WHERE x.role_id = ri.id AND x.permission_id = p.id
  );

DELIMITER ;
