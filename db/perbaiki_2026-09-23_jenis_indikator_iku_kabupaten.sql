-- =====================================================================
-- ISI `jenis_indikator` IKU KABUPATEN YANG MASIH NULL
-- Tanggal : 2026-09-23
-- Sifat   : IDEMPOTEN. Hanya MENGISI kolom yang masih NULL/kosong.
--           Tidak ada DELETE, tidak ada penimpaan nilai yang sudah terisi.
--
-- =====================================================================
-- MASALAHNYA
--
-- Di layar LAKIP Kabupaten, "Tingkat Pengangguran Terbuka" tahun 2025
-- tampil 110,71% (target 4,20 / realisasi 4,65) — seolah melampaui target,
-- padahal indikator itu "semakin rendah semakin baik": realisasi yang lebih
-- TINGGI dari target justru capaian yang KURANG, yaitu 90,32%.
--
-- Penyebabnya bukan rumusnya. `iku_revisi_indikator.jenis_indikator` untuk
-- indikator itu bernilai NULL, dan setiap pemakainya dahulu menambal NULL
-- dengan fallback 'indikator positif':
--
--     $jenis = $r['jenis_indikator'] ?? 'indikator positif';
--
-- sehingga indikator yang arahnya belum ditentukan diam-diam dihitung
-- realisasi/target. Untuk indikator negatif hasilnya TERBALIK, dan tidak ada
-- satu pun penanda di layar bahwa angkanya ditebak.
--
-- Fallback itu sudah dicabut di sisi kode (layar Kab & OPD, cetak, Excel,
-- dan LakipKabupatenCapaianService): jenis kosong kini memulangkan null —
-- capaian ditandai belum dapat dihitung, bukan ditebak. Skrip ini melengkapi
-- sisi datanya supaya kolom capaian tidak berubah jadi '-' massal.
--
-- =====================================================================
-- DARI MANA NILAINYA DIAMBIL
--
-- BUKAN tebakan. Seluruh indikator yang NULL adalah IKU tingkat kabupaten
-- (revisi 116 & 118) hasil revisi yang kehilangan kolom ini saat disalin.
-- Indikator dengan NAMA YANG SAMA masih tercatat jenisnya di dokumen induk:
--
--   * `iku_indikator`           -> IKU kabupaten sebelum revisi ('positif'/'negatif')
--   * `rpjmd_indikator_sasaran` -> RPJMD ('indikator positif'/'indikator negatif')
--
-- Dua sumber itu tidak pernah saling bertentangan untuk nama yang sama, jadi
-- pemetaannya pasti. Satu-satunya nama tanpa padanan di kedua sumber adalah
-- "Persentase Ketersediaan Pangan terhadap kebutuhan pangan"; ia ditetapkan
-- 'positif' secara eksplisit di bawah (rasio ketersediaan terhadap kebutuhan:
-- makin tinggi makin baik; target 100 realisasi 100, jadi angkanya sama saja).
--
-- Nilai ditulis dalam ejaan pendek 'positif'/'negatif' mengikuti kebiasaan
-- tabel IKU. Ejaan panjang RPJMD dinormalkan lewat LOCATE(...'negatif').
--
-- Yang berubah di layar LAKIP Kabupaten 2025 (sumber: IKU versi 116):
--
--   Tingkat Pengangguran Terbuka   110,71%  ->   90,32%   (negatif)
--   Indeks Risiko Bencana          152,16%  ->   65,72%   (negatif)
--   Angka Kemiskinan                97,69%  ->  102,37%   (negatif)
--
-- Delapan indikator sisanya memang positif, angkanya tidak bergerak.
-- =====================================================================

SELECT '========== SEBELUM ==========' AS `laporan`;

SELECT 'iku_indikator' AS tabel,
       COUNT(*)                                                      AS total,
       SUM(jenis_indikator IS NULL OR jenis_indikator = '')          AS kosong
FROM iku_indikator
UNION ALL
SELECT 'iku_revisi_indikator',
       COUNT(*),
       SUM(jenis_indikator IS NULL OR jenis_indikator = '')
FROM iku_revisi_indikator;

-- Rincian baris yang akan diisi, beserta jenis yang akan ditulis.
SELECT 'iku_revisi_indikator' AS tabel, ri.id, ri.revisi_id, ri.indikator,
       (SELECT CASE WHEN LOCATE('negatif', LOWER(x.jenis_indikator)) > 0
                    THEN 'negatif' ELSE 'positif' END
          FROM (SELECT jenis_indikator FROM iku_indikator
                 WHERE indikator = ri.indikator
                   AND jenis_indikator IS NOT NULL AND jenis_indikator <> ''
                UNION ALL
                SELECT jenis_indikator FROM rpjmd_indikator_sasaran
                 WHERE indikator_sasaran = ri.indikator
                   AND jenis_indikator IS NOT NULL AND jenis_indikator <> ''
               ) x LIMIT 1) AS jenis_rujukan
FROM iku_revisi_indikator ri
WHERE ri.jenis_indikator IS NULL OR ri.jenis_indikator = ''
ORDER BY ri.id;

-- =====================================================================
-- 1. ARSIP REVISI (`iku_revisi_indikator`) — inilah yang DIBACA layar LAKIP
--    lewat LakipModel::getIndexIkuTargets().
-- =====================================================================
UPDATE `iku_revisi_indikator` ri
SET ri.jenis_indikator = (
        SELECT CASE WHEN LOCATE('negatif', LOWER(x.jenis_indikator)) > 0
                    THEN 'negatif' ELSE 'positif' END
        FROM (SELECT jenis_indikator FROM iku_indikator
               WHERE indikator = ri.indikator
                 AND jenis_indikator IS NOT NULL AND jenis_indikator <> ''
              UNION ALL
              SELECT jenis_indikator FROM rpjmd_indikator_sasaran
               WHERE indikator_sasaran = ri.indikator
                 AND jenis_indikator IS NOT NULL AND jenis_indikator <> ''
             ) x
        LIMIT 1
    ),
    ri.updated_at = NOW()
WHERE (ri.jenis_indikator IS NULL OR ri.jenis_indikator = '')
  AND EXISTS (
        SELECT 1 FROM iku_indikator i
         WHERE i.indikator = ri.indikator
           AND i.jenis_indikator IS NOT NULL AND i.jenis_indikator <> ''
      UNION ALL
        SELECT 1 FROM rpjmd_indikator_sasaran s
         WHERE s.indikator_sasaran = ri.indikator
           AND s.jenis_indikator IS NOT NULL AND s.jenis_indikator <> ''
  );

-- =====================================================================
-- 2. IKU BERJALAN (`iku_indikator`) — supaya revisi berikutnya menyalin
--    jenis yang benar, bukan mewariskan NULL yang sama sekali lagi.
-- =====================================================================
UPDATE `iku_indikator` i
SET i.jenis_indikator = (
        SELECT CASE WHEN LOCATE('negatif', LOWER(x.jenis_indikator)) > 0
                    THEN 'negatif' ELSE 'positif' END
        FROM (SELECT jenis_indikator FROM iku_revisi_indikator
               WHERE indikator = i.indikator
                 AND jenis_indikator IS NOT NULL AND jenis_indikator <> ''
              UNION ALL
              SELECT jenis_indikator FROM rpjmd_indikator_sasaran
               WHERE indikator_sasaran = i.indikator
                 AND jenis_indikator IS NOT NULL AND jenis_indikator <> ''
             ) x
        LIMIT 1
    ),
    i.updated_at = NOW()
WHERE (i.jenis_indikator IS NULL OR i.jenis_indikator = '')
  AND EXISTS (
        SELECT 1 FROM iku_revisi_indikator ri
         WHERE ri.indikator = i.indikator
           AND ri.jenis_indikator IS NOT NULL AND ri.jenis_indikator <> ''
      UNION ALL
        SELECT 1 FROM rpjmd_indikator_sasaran s
         WHERE s.indikator_sasaran = i.indikator
           AND s.jenis_indikator IS NOT NULL AND s.jenis_indikator <> ''
  );

-- =====================================================================
-- 3. SISA TANPA PADANAN — ditetapkan eksplisit, bukan hasil pencocokan.
--    Rasio ketersediaan pangan terhadap kebutuhan: makin tinggi makin baik.
-- =====================================================================
UPDATE `iku_revisi_indikator`
SET jenis_indikator = 'positif', updated_at = NOW()
WHERE (jenis_indikator IS NULL OR jenis_indikator = '')
  AND indikator = 'Persentase Ketersediaan Pangan terhadap kebutuhan pangan';

UPDATE `iku_indikator`
SET jenis_indikator = 'positif', updated_at = NOW()
WHERE (jenis_indikator IS NULL OR jenis_indikator = '')
  AND indikator = 'Persentase Ketersediaan Pangan terhadap kebutuhan pangan';

SELECT '========== SESUDAH ==========' AS `laporan`;

SELECT 'iku_indikator' AS tabel,
       COUNT(*)                                             AS total,
       SUM(jenis_indikator IS NULL OR jenis_indikator = '') AS sisa_kosong
FROM iku_indikator
UNION ALL
SELECT 'iku_revisi_indikator',
       COUNT(*),
       SUM(jenis_indikator IS NULL OR jenis_indikator = '')
FROM iku_revisi_indikator;

-- Capaian LAKIP Kabupaten 2025 sesudah perbaikan (sumber: IKU versi 116).
-- Pengangguran Terbuka harus 90,32 — bukan 110,71.
SELECT ri.indikator,
       ri.jenis_indikator                                          AS jenis,
       COALESCE(NULLIF(l.target_hitung, ''),  rt.target)           AS target,
       COALESCE(NULLIF(l.capaian_hitung, ''), l.capaian_tahun_ini) AS realisasi,
       ROUND(
           LEAST(
               CASE WHEN ri.jenis_indikator = 'negatif'
                    THEN CAST(REPLACE(COALESCE(NULLIF(l.target_hitung, ''),  rt.target), ',', '.') AS DECIMAL(20,6))
                       / NULLIF(CAST(REPLACE(COALESCE(NULLIF(l.capaian_hitung, ''), l.capaian_tahun_ini), ',', '.') AS DECIMAL(20,6)), 0) * 100
                    ELSE CAST(REPLACE(COALESCE(NULLIF(l.capaian_hitung, ''), l.capaian_tahun_ini), ',', '.') AS DECIMAL(20,6))
                       / NULLIF(CAST(REPLACE(COALESCE(NULLIF(l.target_hitung, ''),  rt.target), ',', '.') AS DECIMAL(20,6)), 0) * 100
               END,
               200
           ), 2
       ) AS capaian_persen
FROM iku_revisi_indikator ri
LEFT JOIN iku_revisi_target rt
       ON rt.revisi_indikator_id = ri.id AND rt.tahun = 2025
LEFT JOIN lakip l
       ON l.source_entity_id   = ri.sumber_indikator_id
      AND l.tahun              = '2025'
      AND l.mode               = 'kabupaten'
      AND l.source_version_id  = 116
WHERE ri.revisi_id = 116
  AND ri.jenis_perubahan <> 'dihentikan'
ORDER BY ri.id;

-- =====================================================================
-- ROLLBACK (seluruh nilai lama memang NULL)
--
--   UPDATE iku_revisi_indikator SET jenis_indikator = NULL
--    WHERE id BETWEEN 391 AND 401 OR id BETWEEN 439 AND 453;
--   UPDATE iku_indikator SET jenis_indikator = NULL
--    WHERE id BETWEEN 195 AND 220;
--
-- Periksa dulu rentang id-nya di server sebelum dipakai — id di sana bisa
-- berbeda. Yang aman: catat id hasil query "SEBELUM" di atas.
-- =====================================================================
