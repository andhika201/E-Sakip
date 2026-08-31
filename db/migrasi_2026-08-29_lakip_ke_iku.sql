-- =====================================================================
-- MIGRASI REALISASI LAKIP: BERKUNCI RENSTRA -> BERKUNCI IKU
-- Tanggal : 2026-08-29
-- Sifat   : IDEMPOTEN & DAPAT DIBALIK. Tidak ada DELETE. Kolom
--           `renstra_target_id` SENGAJA DIPERTAHANKAN sebagai jejak asal
--           sekaligus jalan pulang.
--
-- PRASYARAT MUTLAK:
--   1. Kode terbaru sudah terpasang (LakipModel yang sudah ditambal).
--   2. `php spark iku:sahkan-massal --fix` sudah dijalankan, sehingga
--      setiap OPD punya revisi yang MEMAYUNGI tahun laporannya.
--   Tanpa (2), baris bermigrasi tidak akan tampil di layar mana pun.
--
-- =====================================================================
-- MENGAPA MIGRASI, BUKAN CUKUP JEMBATAN
--
-- Jembatan di `getLakipMapIku()` sudah membuat realisasi lama TAMPIL di
-- layar bersumber IKU. Tetapi ia hanya kompensasi BACA. Begitu operator
-- menyunting baris jembatan, `getLakipByIku()` tidak menemukan baris
-- berkunci IKU untuk indikator itu, sehingga form berperilaku sebagai
-- "tambah" dan melahirkan baris KEDUA — satu berkunci Renstra, satu
-- berkunci IKU, untuk slot yang sama.
--
-- Itu bukan dugaan: pada pengujian, indikator IKU 126 (Koperindag) sudah
-- punya baris Renstra #170, dan menyunting lewat layar IKU melahirkan
-- baris baru #289. Migrasi ini menutup percabangan itu di akarnya.
--
-- =====================================================================
-- YANG DIUBAH PER BARIS
--
--   source_type       : 'renstra' -> 'iku'
--   source_entity_id  : id target Renstra -> id indikator IKU BERJALAN
--   source_version_id : id revisi IKU yang memayungi tahun baris itu
--   renstra_target_id : TIDAK DIUBAH (jejak asal & jalan pulang)
--
-- =====================================================================
-- JALAN PULANG (bila perlu dibatalkan)
--
--   UPDATE lakip l JOIN renstra_target rt ON rt.id = l.renstra_target_id
--      SET l.source_type='renstra', l.source_entity_id=rt.id,
--          l.source_version_id=NULL
--    WHERE l.mode='opd' AND l.source_type='iku' AND l.renstra_target_id IS NOT NULL;
-- =====================================================================

SELECT '========== SEBELUM ==========' AS laporan;

SELECT source_type, COUNT(*) baris,
       SUM(capaian_tahun_ini IS NOT NULL AND capaian_tahun_ini<>'') terisi
FROM lakip WHERE mode='opd' GROUP BY source_type;

-- ---------------------------------------------------------------------
-- MIGRASI
--
-- Syarat tiap baris (semuanya wajib, tidak ada yang ditebak):
--   * jangkar Renstra-nya masih hidup;
--   * indikator IKU-nya bersilsilah ke indikator Renstra yang sama;
--   * ada revisi IKU yang memayungi tahun baris itu.
-- Baris yang tidak memenuhi TIDAK disentuh — ia tetap terbaca lewat
-- jembatan, dan tampil apa adanya.
-- ---------------------------------------------------------------------
UPDATE lakip l
JOIN renstra_target rt ON rt.id = l.renstra_target_id
JOIN iku_indikator ii
     ON ii.source_indikator_id = rt.renstra_indikator_id
    AND ii.source_type        = 'renstra'
    AND ii.dihentikan_pada IS NULL
SET l.source_type       = 'iku',
    l.source_entity_id  = ii.id,
    l.source_version_id = (
        SELECT r.id FROM iku_revisi r
         WHERE r.opd_key = l.opd_id
           AND r.status IN ('berlaku','superseded')
           AND r.berlaku_mulai_tahun <= l.tahun
           AND (r.berlaku_sampai_tahun IS NULL OR r.berlaku_sampai_tahun >= l.tahun)
           AND r.tahun_mulai <= l.tahun
           AND r.tahun_akhir >= l.tahun
         ORDER BY r.berlaku_mulai_tahun DESC, r.nomor DESC
         LIMIT 1
    )
WHERE l.mode = 'opd'
  AND l.source_type = 'renstra'
  AND EXISTS (
        SELECT 1 FROM iku_revisi r
         WHERE r.opd_key = l.opd_id
           AND r.status IN ('berlaku','superseded')
           AND r.berlaku_mulai_tahun <= l.tahun
           AND (r.berlaku_sampai_tahun IS NULL OR r.berlaku_sampai_tahun >= l.tahun)
           AND r.tahun_mulai <= l.tahun
           AND r.tahun_akhir >= l.tahun
  );

SELECT '========== SESUDAH ==========' AS laporan;

SELECT source_type, COUNT(*) baris,
       SUM(capaian_tahun_ini IS NOT NULL AND capaian_tahun_ini<>'') terisi
FROM lakip WHERE mode='opd' GROUP BY source_type;

SELECT '-- pemeriksaan 1: baris IKU tanpa versi payung (harus 0) --' AS laporan;
SELECT COUNT(*) n FROM lakip WHERE mode='opd' AND source_type='iku' AND source_version_id IS NULL;

SELECT '-- pemeriksaan 2: tabrakan slot indikator+tahun (harus kosong) --' AS laporan;
SELECT source_entity_id, tahun, COUNT(*) n FROM lakip
 WHERE mode='opd' AND source_type='iku'
 GROUP BY source_entity_id, tahun HAVING COUNT(*) > 1;

SELECT '-- pemeriksaan 3: yang SENGAJA tertinggal (tak bersilsilah) --' AS laporan;
SELECT l.id, o.nama_opd, l.tahun, l.capaian_tahun_ini, LEFT(ris.indikator_sasaran,44) indikator
  FROM lakip l
  JOIN opd o ON o.id = l.opd_id
  JOIN renstra_target rt ON rt.id = l.renstra_target_id
  JOIN renstra_indikator_sasaran ris ON ris.id = rt.renstra_indikator_id
 WHERE l.mode='opd' AND l.source_type='renstra'
 ORDER BY o.nama_opd;
