-- =====================================================================
-- RBAC: role BUPATI (dashboard eksekutif kabupaten, READ-ONLY)
-- Tanggal : 2026-07-30
-- Sifat   : IDEMPOTEN (NOT EXISTS) + ADDITIVE.
--
-- Struktur yang dipakai (hasil pemeriksaan DB berjalan):
--   users.role    = VARCHAR(50)  -> TIDAK ada enum yang perlu diubah
--   roles         (name, label, is_system)
--   permissions   (name, label, grup)
--   role_permissions (role_id, permission_id)  UNIQUE(role_id, permission_id)
--
-- Skrip ini TIDAK membuat akun Bupati dan TIDAK mengubah password siapa pun.
-- Akun Bupati dibuat lewat Master Data > User oleh Super Admin (pilih role Bupati).
--
-- Jalankan: mysql -u root test_sakip < db/update_2026-07-30_role_bupati.sql
-- Alternatif (disarankan): php spark migrate
-- =====================================================================

-- ---------- 1. Master role ----------
INSERT INTO `roles` (`name`, `label`, `is_system`, `created_at`, `updated_at`)
SELECT 'bupati', 'Bupati', 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `roles` WHERE `name` = 'bupati');

UPDATE `roles`
SET `label`     = COALESCE(NULLIF(`label`, ''), 'Bupati'),
    `is_system` = 1
WHERE `name` = 'bupati';

-- ---------- 2. Permission read-only khusus Bupati ----------
INSERT INTO `permissions` (`name`, `label`, `grup`, `created_at`, `updated_at`)
SELECT 'dashboard_bupati.view', 'Dashboard Eksekutif Bupati - Lihat', 'Bupati', NOW(), NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'dashboard_bupati.view');

INSERT INTO `permissions` (`name`, `label`, `grup`, `created_at`, `updated_at`)
SELECT 'pk_bupati_monitoring.view', 'Monitoring Perjanjian Kinerja - Lihat', 'Bupati', NOW(), NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'pk_bupati_monitoring.view');

INSERT INTO `permissions` (`name`, `label`, `grup`, `created_at`, `updated_at`)
SELECT 'target_bupati_monitoring.view', 'Monitoring Target & Rencana Aksi - Lihat', 'Bupati', NOW(), NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'target_bupati_monitoring.view');

INSERT INTO `permissions` (`name`, `label`, `grup`, `created_at`, `updated_at`)
SELECT 'monev_bupati_monitoring.view', 'Monitoring MONEV - Lihat', 'Bupati', NOW(), NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'monev_bupati_monitoring.view');

INSERT INTO `permissions` (`name`, `label`, `grup`, `created_at`, `updated_at`)
SELECT 'lakip_bupati_monitoring.view', 'Monitoring LAKIP - Lihat', 'Bupati', NOW(), NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'lakip_bupati_monitoring.view');

-- ---------- 3. Pemetaan role -> permission ----------
-- dashboard.view diikutkan agar entri Dashboard pada sidebar terpadu muncul.
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT rb.id, p.id
FROM `roles` rb
JOIN `permissions` p ON p.name IN (
  'dashboard.view',
  'dashboard_bupati.view',
  'pk_bupati_monitoring.view',
  'target_bupati_monitoring.view',
  'monev_bupati_monitoring.view',
  'lakip_bupati_monitoring.view'
)
WHERE rb.name = 'bupati'
  AND NOT EXISTS (
    SELECT 1 FROM `role_permissions` x
    WHERE x.role_id = rb.id AND x.permission_id = p.id
  );

-- ---------- 4. Verifikasi ----------
-- SELECT p.name FROM role_permissions rp
--   JOIN roles r ON r.id = rp.role_id
--   JOIN permissions p ON p.id = rp.permission_id
--  WHERE r.name = 'bupati' ORDER BY p.name;
