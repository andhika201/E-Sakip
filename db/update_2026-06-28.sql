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
