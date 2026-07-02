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
