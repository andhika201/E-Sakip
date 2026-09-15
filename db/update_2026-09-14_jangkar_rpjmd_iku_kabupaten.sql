-- =====================================================================
-- JANGKAR RPJMD UNTUK SASARAN IKU KABUPATEN
--     + mapping manual Cascading Kabupaten berkunci indikator IKU
--
-- Jalankan SEKALI pada basis data server (mysql < berkas ini).
-- Aman diulang: tiap langkah memeriksa keadaan sebelumnya.
--
-- MENGAPA
--   Cascading Kabupaten kini bertulang punggung IKU Kabupaten, bukan RPJMD.
--   Sasaran IKU yang lahir di IKU (bukan hasil sync RPJMD) tidak punya
--   tujuan/misi untuk dituruni — persis seperti sasaran mandiri OPD yang
--   memerlukan `renstra_tujuan_id`. Kolom `rpjmd_tujuan_id` adalah
--   padanannya di lingkup kabupaten. NULL = belum dijangkarkan; barisnya
--   tetap tampil di Cascading dengan kolom Misi/Tujuan kosong sampai
--   diisi lewat form revisi IKU Kabupaten.
--
--   `rpjmd_cascading` (mapping manual OPD/Program per indikator) dulu
--   berkunci indikator RPJMD; indikator yang hanya ada di IKU tidak bisa
--   dipetakan. Kini berkunci indikator IKU. Tabelnya kosong di produksi
--   (0 baris pada 13 Sep 2026), jadi tidak ada data yang dipindah.
-- =====================================================================

SELECT '========== SEBELUM ==========' AS laporan;
SHOW COLUMNS FROM `iku_sasaran` LIKE 'rpjmd_tujuan_id';
SHOW COLUMNS FROM `iku_revisi_sasaran` LIKE 'rpjmd_tujuan_id';
SHOW COLUMNS FROM `rpjmd_cascading` LIKE 'iku_indikator_id';
SELECT COUNT(*) AS baris_mapping_manual FROM `rpjmd_cascading`;

-- ---------------------------------------------------------------------
-- 1. iku_sasaran.rpjmd_tujuan_id
-- ---------------------------------------------------------------------
SET @ada := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'iku_sasaran'
               AND COLUMN_NAME = 'rpjmd_tujuan_id');
SET @sql := IF(@ada = 0,
  'ALTER TABLE `iku_sasaran`
     ADD COLUMN `rpjmd_tujuan_id` INT UNSIGNED NULL
         COMMENT ''tujuan RPJMD bagi sasaran IKU KABUPATEN yang lahir di IKU; NULL = ikut source_sasaran_id / belum dijangkarkan''
         AFTER `renstra_tujuan_id`,
     ADD KEY `idx_iku_sasaran_rpjmd_tujuan` (`rpjmd_tujuan_id`)',
  'SELECT ''iku_sasaran.rpjmd_tujuan_id sudah ada'' AS laporan');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ---------------------------------------------------------------------
-- 2. iku_revisi_sasaran.rpjmd_tujuan_id (arsip revisi; disalin ke live
--    saat revisi disahkan, seperti renstra_tujuan_id)
-- ---------------------------------------------------------------------
SET @ada := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'iku_revisi_sasaran'
               AND COLUMN_NAME = 'rpjmd_tujuan_id');
SET @sql := IF(@ada = 0,
  'ALTER TABLE `iku_revisi_sasaran`
     ADD COLUMN `rpjmd_tujuan_id` INT UNSIGNED NULL
         COMMENT ''tujuan RPJMD bagi sasaran IKU KABUPATEN yang lahir di IKU; NULL = ikut source_ref_id''
         AFTER `renstra_tujuan_id`,
     ADD KEY `idx_iku_revisi_sasaran_rpjmd_tujuan` (`rpjmd_tujuan_id`)',
  'SELECT ''iku_revisi_sasaran.rpjmd_tujuan_id sudah ada'' AS laporan');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Draft yang sedang terbuka mewarisi jangkar dari baris live-nya.
UPDATE `iku_revisi_sasaran` ars
  JOIN `iku_sasaran` liv ON liv.id = ars.sumber_sasaran_id
   SET ars.rpjmd_tujuan_id = liv.rpjmd_tujuan_id
 WHERE ars.rpjmd_tujuan_id IS NULL
   AND liv.rpjmd_tujuan_id IS NOT NULL;

-- ---------------------------------------------------------------------
-- 3. rpjmd_cascading: berkunci indikator IKU
--    - indikator_sasaran_id (RPJMD) menjadi boleh NULL, tetap diisi bila
--      indikator IKU-nya punya silsilah RPJMD — untuk pelaporan lama.
--    - iku_indikator_id ditambahkan dan masuk kunci unik.
-- ---------------------------------------------------------------------
SET @ada := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'rpjmd_cascading'
               AND COLUMN_NAME = 'iku_indikator_id');
SET @sql := IF(@ada = 0,
  'ALTER TABLE `rpjmd_cascading`
     MODIFY COLUMN `indikator_sasaran_id` INT UNSIGNED NULL,
     ADD COLUMN `iku_indikator_id` INT UNSIGNED NULL
         COMMENT ''indikator IKU Kabupaten yang dipetakan; kunci utama mapping sejak 2026-09-14''
         AFTER `indikator_sasaran_id`,
     ADD KEY `idx_rpjmd_cascading_iku` (`iku_indikator_id`)',
  'SELECT ''rpjmd_cascading.iku_indikator_id sudah ada'' AS laporan');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Baris mapping LAMA (berkunci indikator RPJMD) — kalau ada — diberi kunci
-- IKU lewat silsilah, supaya tetap tampil setelah kode membaca kolom baru.
-- Dipilih indikator IKU Kabupaten yang masih berjalan (dihentikan_pada NULL);
-- yang tidak punya padanan dibiarkan NULL dan dilaporkan di bawah.
UPDATE `rpjmd_cascading` map
  JOIN `iku_indikator` iki ON iki.source_indikator_id = map.indikator_sasaran_id
                          AND iki.source_type = 'rpjmd' AND iki.dihentikan_pada IS NULL
  JOIN `iku_sasaran` iks ON iks.id = iki.iku_sasaran_id AND iks.opd_id IS NULL
   SET map.iku_indikator_id = iki.id
 WHERE map.iku_indikator_id IS NULL;
SELECT COUNT(*) AS mapping_lama_tanpa_padanan_iku FROM `rpjmd_cascading` WHERE `iku_indikator_id` IS NULL;

-- Kunci unik lama (indikator_sasaran_id, opd_id, pk_program_id, tahun)
-- tidak lagi mencegah kembar untuk indikator murni IKU. Diganti.
--
-- FK `fk_cascade_indikator` bersandar pada `uniq_map` (kolom pertamanya
-- indikator_sasaran_id); InnoDB menolak membuang index yang dipakai FK.
-- Maka index biasa untuk kolom itu dibuat DULU, baru `uniq_map` dibuang.
SET @ada := (SELECT COUNT(*) FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'rpjmd_cascading'
               AND INDEX_NAME = 'idx_rpjmd_cascading_rpjmd');
SET @sql := IF(@ada = 0,
  'ALTER TABLE `rpjmd_cascading` ADD KEY `idx_rpjmd_cascading_rpjmd` (`indikator_sasaran_id`)',
  'SELECT ''idx_rpjmd_cascading_rpjmd sudah ada'' AS laporan');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @ada := (SELECT COUNT(*) FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'rpjmd_cascading'
               AND INDEX_NAME = 'uniq_map');
SET @sql := IF(@ada > 0,
  'ALTER TABLE `rpjmd_cascading` DROP INDEX `uniq_map`',
  'SELECT ''uniq_map sudah dibuang'' AS laporan');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @ada := (SELECT COUNT(*) FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'rpjmd_cascading'
               AND INDEX_NAME = 'uniq_map_iku');
SET @sql := IF(@ada = 0,
  'ALTER TABLE `rpjmd_cascading`
     ADD UNIQUE KEY `uniq_map_iku` (`iku_indikator_id`, `opd_id`, `pk_program_id`, `tahun`)',
  'SELECT ''uniq_map_iku sudah ada'' AS laporan');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SELECT '========== SESUDAH ==========' AS laporan;
SHOW COLUMNS FROM `iku_sasaran` LIKE 'rpjmd_tujuan_id';
SHOW COLUMNS FROM `iku_revisi_sasaran` LIKE 'rpjmd_tujuan_id';
SHOW COLUMNS FROM `rpjmd_cascading` LIKE 'iku_indikator_id';
SHOW INDEX FROM `rpjmd_cascading` WHERE Key_name IN ('uniq_map', 'uniq_map_iku');
