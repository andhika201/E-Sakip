-- =====================================================================
-- PENGESAHAN LAKIP + PERMINTAAN PERBAIKAN
-- Tanggal : 2026-08-30
-- Sifat   : ADDITIVE. Dua tabel baru + satu izin. Tidak menyentuh data
--           LAKIP yang sudah ada, tidak mengubah kolom mana pun.
--
-- =====================================================================
-- APA YANG DIBANGUN
--
-- Alur yang dipakai sehari-hari di lingkungan pemerintahan:
--
--   1. OPD selesai mengisi LAKIP tahun N       -> tekan SAHKAN
--   2. Angka tahun N terkunci                  -> tidak bisa disunting siapa pun
--   3. Ditemukan typo / salah ketik            -> OPD AJUKAN PERBAIKAN + alasan
--   4. admin_kab memeriksa                     -> SETUJUI atau TOLAK
--   5. Bila disetujui, tahun itu dibuka         -> OPD perbaiki
--   6. Selesai memperbaiki                     -> SAHKAN ULANG
--
-- Sengaja TANPA versi/snapshot. Yang disimpan adalah KEADAAN
-- (disahkan / dibuka) beserta RIWAYAT permintaannya — bukan salinan beku
-- angka. Angka LAKIP tetap satu, di tabel `lakip`, apa adanya.
--
-- =====================================================================
-- KONVENSI LINGKUP
--
-- Mengikuti tabel `lakip` yang sudah ada:
--   mode='opd'        -> opd_id = id OPD
--   mode='kabupaten'  -> opd_id = 0
--
-- `opd_id` TIDAK diberi foreign key ke `opd` justru karena nilai 0 dipakai
-- sebagai penanda lingkup kabupaten — sama seperti `lakip`.
-- =====================================================================


SELECT '========== SEBELUM ==========' AS laporan;
SHOW TABLES LIKE 'lakip_pengesahan';


-- ---------------------------------------------------------------------
-- 1. KEADAAN PENGESAHAN — satu baris per (tahun, mode, opd)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lakip_pengesahan` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tahun`          INT NOT NULL,
    `mode`           VARCHAR(12) NOT NULL COMMENT 'opd | kabupaten',
    `opd_id`         INT UNSIGNED NOT NULL COMMENT '0 = lingkup kabupaten',

    `status`         VARCHAR(12) NOT NULL DEFAULT 'disahkan'
                     COMMENT 'disahkan = terkunci; dibuka = boleh disunting sementara',

    `nomor`          VARCHAR(100) NULL COMMENT 'nomor surat/berita acara, bila ada',
    `catatan`        TEXT NULL,

    `disahkan_oleh`  INT UNSIGNED NULL,
    `disahkan_pada`  DATETIME NULL,
    `dibuka_oleh`    INT UNSIGNED NULL COMMENT 'admin_kab yang menyetujui pembukaan',
    `dibuka_pada`    DATETIME NULL,

    `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_lakip_pengesahan_lingkup` (`tahun`, `mode`, `opd_id`),
    KEY `idx_lakip_pengesahan_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ---------------------------------------------------------------------
-- 2. RIWAYAT PERMINTAAN PERBAIKAN
--
-- Riwayat DISIMPAN SELURUHNYA, termasuk yang ditolak dan ditarik. Justru
-- riwayat inilah nilai tambah alur ini dibanding sekadar tombol buka-kunci:
-- terlihat siapa meminta, alasannya apa, siapa memutuskan, dan kapan.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lakip_buka_permintaan` (
    `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `pengesahan_id`   INT UNSIGNED NOT NULL,

    `alasan`          TEXT NOT NULL COMMENT 'wajib — apa yang salah dan mengapa perlu dibuka',
    `status`          VARCHAR(12) NOT NULL DEFAULT 'menunggu'
                      COMMENT 'menunggu | disetujui | ditolak | ditarik',

    `diminta_oleh`    INT UNSIGNED NULL,
    `diminta_pada`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    `tanggapan`       TEXT NULL COMMENT 'catatan admin_kab saat menyetujui/menolak',
    `ditanggapi_oleh` INT UNSIGNED NULL,
    `ditanggapi_pada` DATETIME NULL,

    `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_lakip_permintaan_pengesahan` (`pengesahan_id`),
    KEY `idx_lakip_permintaan_status` (`status`),
    CONSTRAINT `fk_lakip_permintaan_pengesahan`
        FOREIGN KEY (`pengesahan_id`) REFERENCES `lakip_pengesahan` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ---------------------------------------------------------------------
-- 3. IZIN BARU — hanya untuk MEMUTUSKAN permintaan pembukaan
--
-- Mengajukan permintaan tidak butuh izin baru: yang boleh mengesahkan
-- (lakip_opd.finalisasi) otomatis boleh meminta pembukaan atas miliknya.
-- ---------------------------------------------------------------------
INSERT INTO `permissions` (`name`, `label`, `grup`)
SELECT 'lakip_opd.buka_kunci', 'LAKIP OPD - Setujui Pembukaan Kunci', 'OPD'
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `name` = 'lakip_opd.buka_kunci');

INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `roles` r
JOIN `permissions` p ON p.name = 'lakip_opd.buka_kunci'
WHERE r.name IN ('admin_kab', 'admin')
  AND NOT EXISTS (
      SELECT 1 FROM `role_permissions` x
       WHERE x.role_id = r.id AND x.permission_id = p.id
  );


SELECT '========== SESUDAH ==========' AS laporan;

SHOW TABLES LIKE 'lakip_pengesahan';
SHOW TABLES LIKE 'lakip_buka_permintaan';

SELECT r.name AS peran, p.name AS izin
FROM role_permissions rp
JOIN permissions p ON p.id = rp.permission_id
JOIN roles r ON r.id = rp.role_id
WHERE p.name = 'lakip_opd.buka_kunci';
