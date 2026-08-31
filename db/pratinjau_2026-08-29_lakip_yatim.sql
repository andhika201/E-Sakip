-- =====================================================================
-- PRATINJAU NASIB 123 BARIS LAKIP YATIM — BACA-SAJA, TIDAK MENGUBAH APA PUN
-- Tanggal : 2026-08-29
--
-- Baris-baris ini kehilangan jangkar `renstra_target_id`-nya karena target
-- Renstra dihapus-tanam-ulang (FK ON DELETE SET NULL), SEBELUM kolom lingkup
-- diisi. Tidak ada penunjuk langsung yang tersisa; yang tersisa hanyalah
-- SIDIK JARI: `target_lalu` yang dulu disalin dari target tahun sebelumnya.
--
-- Skrip ini menunjukkan usulan nasib tiap baris. EKSEKUSINYA menunggu
-- persetujuan — terutama kelompok A, yang pencocokannya tidak mutlak.
-- =====================================================================

SELECT '===== A. KANDIDAT PEMULANGAN — sidik jari cocok ke TEPAT SATU indikator =====' AS laporan;

SELECT y.id                    AS lakip_id,
       y.capaian_tahun_ini     AS capaian_yang_diketik,
       y.target_lalu           AS sidik_jari,
       DATE(y.created_at)      AS diketik_pada,
       o.nama_opd              AS usulan_opd,
       ris.indikator_sasaran   AS usulan_indikator,
       rt.tahun                AS tahun_target_yang_cocok
FROM lakip y
JOIN renstra_target rt ON TRIM(rt.target) = TRIM(y.target_lalu) AND TRIM(IFNULL(y.target_lalu,'')) <> ''
JOIN renstra_indikator_sasaran ris ON ris.id = rt.renstra_indikator_id
JOIN renstra_sasaran rs ON rs.id = ris.renstra_sasaran_id
JOIN opd o ON o.id = rs.opd_id
WHERE y.mode IS NULL AND y.renstra_target_id IS NULL AND y.rpjmd_target_id IS NULL
  AND 1 = (SELECT COUNT(DISTINCT rt2.renstra_indikator_id) FROM renstra_target rt2
            WHERE TRIM(rt2.target) = TRIM(y.target_lalu))
ORDER BY o.nama_opd, y.id;

SELECT '===== B. PUNYA KEMBARAN SEHAT — datanya sudah diketik ulang, aman diarsipkan =====' AS laporan;

SELECT y.id AS lakip_id, y.capaian_tahun_ini, DATE(y.created_at) diketik_pada,
       s.id AS kembaran_sehat_id, s.tahun, s.opd_id
FROM lakip y
JOIN lakip s ON s.mode IS NOT NULL
           AND s.capaian_tahun_ini <=> y.capaian_tahun_ini
           AND s.target_lalu       <=> y.target_lalu
           AND s.capaian_lalu      <=> y.capaian_lalu
WHERE y.mode IS NULL AND y.renstra_target_id IS NULL AND y.rpjmd_target_id IS NULL
  AND y.capaian_tahun_ini IS NOT NULL AND y.capaian_tahun_ini <> ''
ORDER BY y.id;

SELECT '===== C. KOSONG — tidak pernah diisi nilai, aman diarsipkan =====' AS laporan;

SELECT COUNT(*) AS baris_kosong FROM lakip
WHERE mode IS NULL AND renstra_target_id IS NULL AND rpjmd_target_id IS NULL
  AND (capaian_tahun_ini IS NULL OR capaian_tahun_ini = '');

SELECT '===== D. TAK TERCOCOKKAN — nilainya diarsipkan supaya tidak lenyap =====' AS laporan;

SELECT COUNT(*) AS baris_tak_tercocokkan FROM lakip y
WHERE y.mode IS NULL AND y.renstra_target_id IS NULL AND y.rpjmd_target_id IS NULL
  AND y.capaian_tahun_ini IS NOT NULL AND y.capaian_tahun_ini <> ''
  AND NOT EXISTS (SELECT 1 FROM renstra_target rt WHERE TRIM(rt.target) = TRIM(y.target_lalu)
                  GROUP BY NULL HAVING COUNT(DISTINCT rt.renstra_indikator_id) = 1);
