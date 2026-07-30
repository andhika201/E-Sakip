-- =====================================================================
-- Ambang status capaian kinerja (dashboard) — kembaran SQL dari migration
-- app/Database/Migrations/2026-07-28-000001_CreateDashboardStatusThresholds.php
-- dan seeder app/Database/Seeds/DashboardStatusThresholdSeeder.php
--
-- Dipakai karena `php spark migrate` pada instalasi ini masih terhalang
-- migrasi lama yang tidak idempoten (kolom rpjmd_sasaran.csf sudah ada di
-- database live). Skrip ini AMAN dijalankan ulang.
--
--   mysql -u root test_sakip < db/update_2026-07-28_dashboard_thresholds.sql
-- =====================================================================

CREATE TABLE IF NOT EXISTS `dashboard_status_thresholds` (
  `id`             INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code`           VARCHAR(50)  NOT NULL COMMENT 'kunci tetap: critical|attention|near_target|achieved|exceeded',
  `name`           VARCHAR(100) NOT NULL,
  `min_value`      DECIMAL(10,2) NULL DEFAULT NULL COMMENT 'batas bawah persentase (inklusif)',
  `max_value`      DECIMAL(10,2) NULL DEFAULT NULL COMMENT 'batas atas persentase (inklusif); NULL = tanpa batas atas',
  `color`          VARCHAR(20)  NOT NULL DEFAULT 'abu' COMMENT 'slug warna terbatas: merah|oranye|kuning|hijau|biru|abu',
  `icon`           VARCHAR(50)  NULL DEFAULT NULL,
  `sort_order`     INT(11)      NOT NULL DEFAULT 0,
  `is_active`      TINYINT(1)   NOT NULL DEFAULT 1,
  `effective_from` DATE         NULL DEFAULT NULL,
  `created_by`     INT(11)      NULL DEFAULT NULL,
  `updated_by`     INT(11)      NULL DEFAULT NULL,
  `created_at`     DATETIME     NULL DEFAULT NULL,
  `updated_at`     DATETIME     NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dashboard_threshold_code` (`code`),
  KEY `idx_dashboard_threshold_aktif` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Seed default (idempoten: baris yang sudah ada tidak ditimpa nilainya).
INSERT INTO `dashboard_status_thresholds`
  (`code`, `name`, `min_value`, `max_value`, `color`, `icon`, `sort_order`, `is_active`, `created_at`, `updated_at`)
VALUES
  ('critical',    'Kritis',            0.00,   59.99, 'merah',  'fa-circle-exclamation', 1, 1, NOW(), NOW()),
  ('attention',   'Perlu Perhatian',  60.00,   79.99, 'oranye', 'fa-triangle-exclamation', 2, 1, NOW(), NOW()),
  ('near_target', 'Mendekati Target', 80.00,   94.99, 'kuning', 'fa-circle-half-stroke', 3, 1, NOW(), NOW()),
  ('achieved',    'Tercapai',         95.00,  105.00, 'hijau',  'fa-circle-check', 4, 1, NOW(), NOW()),
  ('exceeded',    'Melampaui Target', 105.01,  NULL,  'biru',   'fa-arrow-trend-up', 5, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE `code` = VALUES(`code`);

-- Catat migration-nya supaya `php spark migrate` tidak mencoba membuat ulang
-- tabel ini setelah drift migrasi lama diperbaiki.
INSERT INTO `migrations` (`version`, `class`, `group`, `namespace`, `time`, `batch`)
SELECT '2026-07-28-000001',
       'App\\Database\\Migrations\\CreateDashboardStatusThresholds',
       'default', 'App', UNIX_TIMESTAMP(),
       COALESCE((SELECT MAX(m.batch) FROM `migrations` m), 0) + 1
WHERE NOT EXISTS (
  SELECT 1 FROM `migrations` m2
  WHERE m2.class = 'App\\Database\\Migrations\\CreateDashboardStatusThresholds'
);
