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
