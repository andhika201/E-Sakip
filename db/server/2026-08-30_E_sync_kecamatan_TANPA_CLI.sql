-- =====================================================================
-- [SERVER] PENGGANTI `php spark iku:sync-renstra --fix` UNTUK 4 KECAMATAN
-- Tanggal : 2026-08-30
-- Sifat   : IDEMPOTEN & ADDITIVE. Hanya MENAMBAH baris IKU yang belum ada.
--           Tidak ada DELETE, tidak ada penimpaan isi IKU yang sudah ada.
--
-- =====================================================================
-- MENGAPA VERSI SQL INI ADA
--
-- Panduan aslinya memakai CLI. Bila server tidak menyediakan akses shell,
-- langkah itu terlewat — dan gejalanya persis seperti di server Anda:
--
--     SELECT COUNT(*) FROM cascading_sasaran_opd WHERE iku_indikator_id IS NULL;
--     -> 48
--
-- 48 baris itu milik empat kecamatan yang indikator Renstra-nya belum pernah
-- ditarik ke IKU. Karena cascading kini dibaca dari IKU, baris tanpa jangkar
-- TIDAK MUNCUL di matriks: pekerjaan Eselon III/IV mereka tampak hilang.
--
-- =====================================================================
-- LINGKUPNYA SEMPIT DAN DISENGAJA
--
-- Hanya empat OPD, hanya periode 2025-2029:
--
--     31 Kecamatan Pringsewu      35 Kecamatan Adiluwih
--     36 Kecamatan Banyumas       37 Kecamatan Pagelaran
--
-- Periode LAIN sengaja tidak disentuh. Kec. Banyumas punya Renstra nyasar
-- 2026-2030 dan Kec. Pringsewu punya 2029-2033; keduanya persoalan
-- tersendiri yang belum diputuskan, dan menyalinnya ke IKU akan
-- memperbanyak kekacauan itu alih-alih menyelesaikannya.
--
-- =====================================================================
-- YANG DISALIN, DAN JEJAKNYA
--
-- Tiap baris baru membawa silsilahnya:
--     iku_sasaran.source_sasaran_id   -> renstra_sasaran.id
--     iku_indikator.source_indikator_id -> renstra_indikator_sasaran.id
--     source_type = 'renstra'
--
-- Silsilah inilah yang kemudian dipakai penjangkaran cascading dan migrasi
-- LAKIP. Tanpa itu, baris baru lahir yatim.
--
-- =====================================================================
-- SESUDAH MENJALANKAN INI
--
-- Jalankan ULANG (keduanya idempoten):
--     db/server/2026-08-27_C_jangkar_cascading_isi.sql
--     db/migrasi_2026-08-29_lakip_ke_iku.sql
-- =====================================================================


SELECT '========== SEBELUM ==========' AS laporan;

SELECT COUNT(*) AS cascading_tanpa_jangkar
FROM cascading_sasaran_opd WHERE iku_indikator_id IS NULL;

SELECT o.id AS opd, o.nama_opd,
       COUNT(DISTINCT ris.id) AS indikator_renstra,
       SUM(ii.id IS NULL)     AS belum_ada_di_iku
FROM renstra_sasaran rs
JOIN opd o ON o.id = rs.opd_id
JOIN renstra_indikator_sasaran ris ON ris.renstra_sasaran_id = rs.id
LEFT JOIN iku_indikator ii
       ON ii.source_indikator_id = ris.id
      AND ii.source_type = 'renstra'
      AND ii.dihentikan_pada IS NULL
WHERE rs.opd_id IN (31, 35, 36, 37)
  AND rs.tahun_mulai = 2025 AND rs.tahun_akhir = 2029
GROUP BY o.id, o.nama_opd;


-- ---------------------------------------------------------------------
-- 1. SASARAN IKU yang belum ada
--
-- `urutan` melanjutkan nomor terbesar yang sudah dipakai OPD itu, supaya
-- sasaran baru muncul di ekor daftar — bukan menyerobot ke depan.
-- ---------------------------------------------------------------------
INSERT INTO `iku_sasaran`
    (`opd_id`, `sasaran`, `tahun_mulai`, `tahun_akhir`, `urutan`,
     `source_type`, `source_sasaran_id`, `created_at`, `updated_at`)
SELECT rs.opd_id, rs.sasaran, rs.tahun_mulai, rs.tahun_akhir,
       (SELECT COALESCE(MAX(x.urutan), 0)
          FROM iku_sasaran x
         WHERE x.opd_id = rs.opd_id
           AND x.tahun_mulai = rs.tahun_mulai
           AND x.tahun_akhir = rs.tahun_akhir) + 1,
       'renstra', rs.id, NOW(), NOW()
FROM renstra_sasaran rs
WHERE rs.opd_id IN (31, 35, 36, 37)
  AND rs.tahun_mulai = 2025 AND rs.tahun_akhir = 2029
  AND NOT EXISTS (
      SELECT 1 FROM iku_sasaran iks
       WHERE iks.source_sasaran_id = rs.id
         AND iks.source_type = 'renstra'
         AND iks.dihentikan_pada IS NULL
  )
ORDER BY rs.opd_id, rs.id;


-- ---------------------------------------------------------------------
-- 2. INDIKATOR IKU yang belum ada
--
-- Ditempelkan ke sasaran IKU yang silsilahnya menunjuk sasaran Renstra
-- yang sama — bukan lewat pencocokan teks, yang bisa meleset karena beda
-- spasi atau huruf besar.
-- ---------------------------------------------------------------------
INSERT INTO `iku_indikator`
    (`iku_sasaran_id`, `indikator`, `satuan`, `jenis_indikator`, `baseline`,
     `urutan`, `source_type`, `source_indikator_id`, `created_at`, `updated_at`)
SELECT iks.id, ris.indikator_sasaran, ris.satuan, ris.jenis_indikator,
       ris.baseline,
       (SELECT COALESCE(MAX(y.urutan), 0)
          FROM iku_indikator y WHERE y.iku_sasaran_id = iks.id) + 1,
       'renstra', ris.id, NOW(), NOW()
FROM renstra_indikator_sasaran ris
JOIN renstra_sasaran rs ON rs.id = ris.renstra_sasaran_id
JOIN iku_sasaran iks
  ON iks.source_sasaran_id = rs.id
 AND iks.source_type = 'renstra'
 AND iks.dihentikan_pada IS NULL
WHERE rs.opd_id IN (31, 35, 36, 37)
  AND rs.tahun_mulai = 2025 AND rs.tahun_akhir = 2029
  AND NOT EXISTS (
      SELECT 1 FROM iku_indikator ii
       WHERE ii.source_indikator_id = ris.id
         AND ii.source_type = 'renstra'
         AND ii.dihentikan_pada IS NULL
  )
ORDER BY rs.id, ris.id;


-- ---------------------------------------------------------------------
-- 3. TARGET TAHUNAN
--
-- Hanya tahun yang berada DI DALAM periode sasarannya. Target di luar
-- periode adalah sisa dokumen lama; menyalinnya membuat IKU berisi tahun
-- yang tidak ia klaim.
-- ---------------------------------------------------------------------
INSERT INTO `iku_target`
    (`iku_indikator_id`, `tahun`, `target`, `created_at`, `updated_at`)
SELECT ii.id, rt.tahun, rt.target, NOW(), NOW()
FROM iku_indikator ii
JOIN iku_sasaran iks ON iks.id = ii.iku_sasaran_id
JOIN renstra_target rt ON rt.renstra_indikator_id = ii.source_indikator_id
WHERE iks.opd_id IN (31, 35, 36, 37)
  AND iks.tahun_mulai = 2025 AND iks.tahun_akhir = 2029
  AND ii.source_type = 'renstra'
  AND rt.tahun BETWEEN iks.tahun_mulai AND iks.tahun_akhir
  AND NOT EXISTS (
      SELECT 1 FROM iku_target t
       WHERE t.iku_indikator_id = ii.id AND t.tahun = rt.tahun
  )
ORDER BY ii.id, rt.tahun;


-- ---------------------------------------------------------------------
-- 4. PERIKSA HASIL
-- ---------------------------------------------------------------------
SELECT '========== SESUDAH ==========' AS laporan;

SELECT o.id AS opd, o.nama_opd,
       COUNT(DISTINCT ris.id) AS indikator_renstra,
       SUM(ii.id IS NULL)     AS masih_belum_di_iku
FROM renstra_sasaran rs
JOIN opd o ON o.id = rs.opd_id
JOIN renstra_indikator_sasaran ris ON ris.renstra_sasaran_id = rs.id
LEFT JOIN iku_indikator ii
       ON ii.source_indikator_id = ris.id
      AND ii.source_type = 'renstra'
      AND ii.dihentikan_pada IS NULL
WHERE rs.opd_id IN (31, 35, 36, 37)
  AND rs.tahun_mulai = 2025 AND rs.tahun_akhir = 2029
GROUP BY o.id, o.nama_opd;
-- kolom "masih_belum_di_iku" harus 0 pada keempat baris

SELECT '-- indikator baru tanpa silsilah (harus 0) --' AS laporan;

SELECT COUNT(*) AS n
FROM iku_indikator ii
JOIN iku_sasaran iks ON iks.id = ii.iku_sasaran_id
WHERE iks.opd_id IN (31, 35, 36, 37)
  AND ii.source_indikator_id IS NULL
  AND ii.dihentikan_pada IS NULL;
