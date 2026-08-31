-- =====================================================================
-- PULANGKAN LINGKUP BARIS LAKIP YANG MASIH PUNYA JANGKAR
-- Tanggal : 2026-08-29
-- Sifat   : IDEMPOTEN. Hanya MENGISI kolom lingkup yang masih NULL pada
--           baris yang jangkarnya masih hidup. Tidak ada DELETE, tidak ada
--           penimpaan nilai yang sudah terisi.
--
-- =====================================================================
-- MASALAHNYA
--
-- `LakipModel::$allowedFields` tertinggal enam kolom lingkup (tahun, opd_id,
-- mode, source_type, source_version_id, source_entity_id), sehingga setiap
-- penyimpanan lewat model MEMBUANG keenamnya diam-diam. Baris yang lahir
-- sesudah migrasi 2026-08-20 karena itu kembali yatim — persis penyakit yang
-- migrasi itu obati.
--
-- Kodenya sudah ditambal (lihat komentar panjang di LakipModel). Skrip ini
-- memulangkan baris yang telanjur lahir cacat NAMUN masih bisa dipulangkan:
-- jangkar `renstra_target_id`/`rpjmd_target_id`-nya masih hidup, jadi
-- lingkupnya bisa diturunkan dengan pasti — bukan ditebak.
--
-- Semantiknya DISALIN PERSIS dari migrasi 2026-08-20 (bagian backfill),
-- supaya baris pulangan tidak bisa dibedakan dari baris yang sehat sejak
-- lahir.
--
-- Baris yang jangkarnya SUDAH putus (renstra_target-nya terhapus) tidak
-- disentuh skrip ini — pemulangannya lewat pencocokan sidik jari yang
-- perlu persetujuan per baris, di skrip terpisah.
-- =====================================================================

SELECT '========== SEBELUM ==========' AS `laporan`;

SELECT COUNT(*)                                            AS baris_lakip,
       SUM(mode IS NULL)                                   AS tanpa_lingkup,
       SUM(mode IS NULL AND renstra_target_id IS NOT NULL) AS bisa_dipulangkan_renstra,
       SUM(mode IS NULL AND rpjmd_target_id  IS NOT NULL)  AS bisa_dipulangkan_rpjmd
FROM lakip;

-- ---- jalur Renstra (mode=opd) ---------------------------------------
UPDATE `lakip` l
JOIN `renstra_target` rt             ON rt.id  = l.renstra_target_id
JOIN `renstra_indikator_sasaran` ris ON ris.id = rt.renstra_indikator_id
JOIN `renstra_sasaran` rs            ON rs.id  = ris.renstra_sasaran_id
SET l.tahun            = rt.tahun,
    l.opd_id           = rs.opd_id,
    l.mode             = 'opd',
    l.source_type      = 'renstra',
    l.source_entity_id = rt.id
WHERE l.tahun IS NULL AND l.renstra_target_id IS NOT NULL;

-- ---- jalur RPJMD (mode=kabupaten) -----------------------------------
UPDATE `lakip` l
JOIN `rpjmd_target` rpj ON rpj.id = l.rpjmd_target_id
SET l.tahun            = rpj.tahun,
    l.opd_id           = 0,
    l.mode             = 'kabupaten',
    l.source_type      = 'rpjmd',
    l.source_entity_id = rpj.id
WHERE l.tahun IS NULL AND l.rpjmd_target_id IS NOT NULL;

SELECT '========== SESUDAH ==========' AS `laporan`;

SELECT COUNT(*)          AS baris_lakip,
       SUM(mode IS NULL) AS tanpa_lingkup_sisa
FROM lakip;

SELECT '-- yang tersisa yatim: jangkarnya memang sudah putus --' AS `laporan`;

SELECT COUNT(*) AS yatim_tanpa_jangkar
FROM lakip
WHERE mode IS NULL AND renstra_target_id IS NULL AND rpjmd_target_id IS NULL;
