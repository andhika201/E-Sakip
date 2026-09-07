-- ============================================================================
--  CEK KELENGKAPAN DATA DASHBOARD "PERLU PERHATIAN" — HANYA MEMBACA
--  (tidak ada ALTER/INSERT/UPDATE/DELETE pada tabel data — aman dijalankan
--   kapan saja, termasuk di server produksi)
--
--  Menjawab: "yang belum dan yang sudah itu yang mana?"
--  Aturannya SAMA dengan yang dipakai App\Services\OpdDashboardService, jadi
--  hasilnya harus cocok dengan kartu Perlu Perhatian di dashboard.
--
--  Cara pakai:
--    mysql -uroot test_sakip < db/cek_2026-09-02_kelengkapan_dashboard.sql
--
--  Ganti @tahun / @tw sesuai filter dashboard yang sedang dilihat.
--  @tw = triwulan TERAKHIR YANG SELESAI (lihat dash_triwulan_berjalan):
--  September 2026 -> 2.
--
--  CATATAN: berkas ini menghitung pk.jenis 'jpt' DAN 'camat' sekaligus,
--  sedangkan dashboard hanya menampilkan SATU jenis per sesi (camat untuk
--  admin_kecamatan, jpt untuk yang lain — lihat PREFERENSI_JENIS). OPD yang
--  punya kedua dokumen (mis. Kecamatan Sukoharjo) karena itu terlihat punya
--  lebih banyak indikator di sini daripada di layar. Persempit dengan
--  mengubah daftar pk.jenis di bawah bila ingin sama persis dengan layar.
-- ============================================================================

SET @tahun := 2026;
SET @tw    := 2;

-- ---------------------------------------------------------------------------
-- 1) RINCIAN PER INDIKATOR — kolom bernilai BELUM itulah tindak lanjutnya.
--    Tanda '-' berarti tahap itu belum relevan (mis. realisasi tanpa pagu).
-- ---------------------------------------------------------------------------
SELECT
  i.opd_id,
  LEFT(i.nama_opd, 30)                                                  AS opd,
  i.indikator_id,
  LEFT(i.indikator, 50)                                                 AS indikator,

  -- Rencana Aksi (tabel target_rencana) — akar semua tahap sesudahnya
  IF(COALESCE(r.jml, 0) > 0, 'SUDAH', 'BELUM')                          AS renaksi,
  IF(COALESCE(r.jml, 0) = 0, '-',
     IF(COALESCE(r.target_kosong, 0) = 0, 'SUDAH', 'BELUM'))            AS target_tw,

  -- MONEV (tabel monev): baris capaian, metode perhitungan, isian s.d. @tw
  IF(COALESCE(r.jml, 0) = 0, '-',
     IF(COALESCE(mv.baris, 0) > 0, 'SUDAH', 'BELUM'))                   AS monev_baris,
  IF(COALESCE(mv.baris, 0) = 0, '-',
     IF(COALESCE(mv.metode_kosong, 0) = 0, 'SUDAH', 'BELUM'))           AS metode,
  IF(COALESCE(mv.baris, 0) = 0, '-',
     IF(COALESCE(mv.capaian_kosong, 0) = 0, 'SUDAH', 'BELUM'))          AS capaian_tw,

  -- Satuan berpredikat (WTP, Nilai SAKIP) wajib punya baris satuan_skala
  IF(COALESCE(sp.predikat, 0) = 0, '-',
     IF(COALESCE(sp.skala, 0) > 0, 'SUDAH', 'BELUM'))                   AS skala_predikat,

  -- Anggaran: pagu dari program_pk lewat pk_program, realisasi dari monev_anggaran
  FORMAT(COALESCE(pg.pagu, 0), 0)                                       AS pagu,
  IF(COALESCE(pg.pagu, 0) = 0, '-',
     IF(COALESCE(ag.baris, 0) > 0, 'SUDAH', 'BELUM'))                   AS realisasi
FROM (
  SELECT pi.id AS indikator_id, pi.indikator, pi.id_satuan, pk.opd_id, o.nama_opd
  FROM pk_indikator pi
  JOIN pk_sasaran ps ON ps.id = pi.pk_sasaran_id
  JOIN pk            ON pk.id = ps.pk_id
  LEFT JOIN opd o    ON o.id  = pk.opd_id
  WHERE pk.tahun = @tahun
    AND pk.jenis IN ('jpt', 'camat')
) i
LEFT JOIN (
  SELECT tr.pk_indikator_id, tr.opd_id, COUNT(*) AS jml,
         SUM(
           (tr.target_triwulan_1 IS NULL OR tr.target_triwulan_1 = '')
           OR (@tw >= 2 AND (tr.target_triwulan_2 IS NULL OR tr.target_triwulan_2 = ''))
           OR (@tw >= 3 AND (tr.target_triwulan_3 IS NULL OR tr.target_triwulan_3 = ''))
           OR (@tw >= 4 AND (tr.target_triwulan_4 IS NULL OR tr.target_triwulan_4 = ''))
         ) AS target_kosong
  FROM target_rencana tr
  GROUP BY tr.pk_indikator_id, tr.opd_id
) r ON r.pk_indikator_id = i.indikator_id AND r.opd_id = i.opd_id
LEFT JOIN (
  SELECT tr.pk_indikator_id, tr.opd_id, COUNT(*) AS baris,
         SUM(m.metode_perhitungan IS NULL OR m.metode_perhitungan = '') AS metode_kosong,
         SUM(
           (m.capaian_triwulan_1 IS NULL OR m.capaian_triwulan_1 = '')
           OR (@tw >= 2 AND (m.capaian_triwulan_2 IS NULL OR m.capaian_triwulan_2 = ''))
           OR (@tw >= 3 AND (m.capaian_triwulan_3 IS NULL OR m.capaian_triwulan_3 = ''))
           OR (@tw >= 4 AND (m.capaian_triwulan_4 IS NULL OR m.capaian_triwulan_4 = ''))
         ) AS capaian_kosong
  FROM target_rencana tr
  -- BARIS UKUR CURRENT SAJA. Aturannya disamakan persis dengan
  -- OpdDashboardService::getIndicators(); kalau berbeda, SQL ini akan
  -- melaporkan "metode belum dipilih" untuk indikator yang di layar justru
  -- sudah lengkap — dan dua-duanya akan tampak meyakinkan.
  --
  --   sub ada      -> hanya baris sub yang mengukur
  --   sub tidak ada-> baris tingkat rencana aksi yang mengukur
  --   sub yatim    -> tidak mengukur apa pun (induknya sudah tiada)
  JOIN monev m
    ON m.target_rencana_id = tr.id
   AND (
         (COALESCE(m.target_sub_rencana_id, 0) > 0
          AND EXISTS (SELECT 1 FROM target_sub_rencana s2 WHERE s2.id = m.target_sub_rencana_id))
         OR
         (COALESCE(m.target_sub_rencana_id, 0) = 0
          AND NOT EXISTS (SELECT 1 FROM target_sub_rencana s1 WHERE s1.target_rencana_id = tr.id))
       )
  GROUP BY tr.pk_indikator_id, tr.opd_id
) mv ON mv.pk_indikator_id = i.indikator_id AND mv.opd_id = i.opd_id
LEFT JOIN (
  SELECT s.id AS satuan_id,
         (LOWER(COALESCE(s.tipe, '')) = 'predikat') AS predikat,
         (SELECT COUNT(*) FROM satuan_skala ss WHERE ss.satuan_id = s.id) AS skala
  FROM satuan s
) sp ON sp.satuan_id = i.id_satuan
LEFT JOIN (
  SELECT pp.pk_indikator_id, SUM(pr.anggaran) AS pagu
  FROM pk_program pp
  JOIN program_pk pr ON pr.id = pp.program_id AND pr.tahun_anggaran = @tahun
  GROUP BY pp.pk_indikator_id
) pg ON pg.pk_indikator_id = i.indikator_id
LEFT JOIN (
  SELECT tr.pk_indikator_id, tr.opd_id, COUNT(*) AS baris
  FROM target_rencana tr
  JOIN monev_anggaran ma ON ma.target_rencana_id = tr.id
  GROUP BY tr.pk_indikator_id, tr.opd_id
) ag ON ag.pk_indikator_id = i.indikator_id AND ag.opd_id = i.opd_id
ORDER BY i.opd_id, i.indikator_id;

-- ---------------------------------------------------------------------------
-- 2) REKAP PER OPD — berapa indikator yang masih BELUM di tiap tahap, plus
--    status LAKIP (sumber baris "laporan LAKIP belum final" di dashboard).
-- ---------------------------------------------------------------------------
SELECT
  i.opd_id,
  LEFT(i.nama_opd, 32)                                                  AS opd,
  COUNT(*)                                                              AS indikator,
  SUM(COALESCE(r.jml, 0) = 0)                                           AS renaksi_belum,
  SUM(COALESCE(r.jml, 0) > 0 AND COALESCE(r.target_kosong, 0) > 0)      AS target_belum,
  SUM(COALESCE(r.jml, 0) > 0 AND COALESCE(mv.baris, 0) = 0)             AS monev_belum,
  SUM(COALESCE(mv.metode_kosong, 0) > 0)                                AS metode_belum,
  SUM(COALESCE(mv.baris, 0) > 0 AND COALESCE(mv.capaian_kosong, 0) > 0) AS capaian_belum,
  SUM(COALESCE(pg.pagu, 0) > 0 AND COALESCE(ag.baris, 0) = 0)           AS realisasi_belum,
  (SELECT IF(COUNT(*) = 0, 'BELUM ADA DATA',
             IF(SUM(LOWER(l.status) NOT IN ('selesai', 'siap')) > 0, 'MASIH DRAFT', 'SUDAH FINAL'))
     FROM lakip l
     JOIN renstra_target rt             ON rt.id  = l.renstra_target_id
     JOIN renstra_indikator_sasaran ris ON ris.id = rt.renstra_indikator_id
     JOIN renstra_sasaran rs            ON rs.id  = ris.renstra_sasaran_id
    WHERE rs.opd_id = i.opd_id AND rt.tahun = @tahun)                   AS lakip
FROM (
  SELECT pi.id AS indikator_id, pi.indikator, pi.id_satuan, pk.opd_id, o.nama_opd
  FROM pk_indikator pi
  JOIN pk_sasaran ps ON ps.id = pi.pk_sasaran_id
  JOIN pk            ON pk.id = ps.pk_id
  LEFT JOIN opd o    ON o.id  = pk.opd_id
  WHERE pk.tahun = @tahun
    AND pk.jenis IN ('jpt', 'camat')
) i
LEFT JOIN (
  SELECT tr.pk_indikator_id, tr.opd_id, COUNT(*) AS jml,
         SUM(
           (tr.target_triwulan_1 IS NULL OR tr.target_triwulan_1 = '')
           OR (@tw >= 2 AND (tr.target_triwulan_2 IS NULL OR tr.target_triwulan_2 = ''))
           OR (@tw >= 3 AND (tr.target_triwulan_3 IS NULL OR tr.target_triwulan_3 = ''))
           OR (@tw >= 4 AND (tr.target_triwulan_4 IS NULL OR tr.target_triwulan_4 = ''))
         ) AS target_kosong
  FROM target_rencana tr
  GROUP BY tr.pk_indikator_id, tr.opd_id
) r ON r.pk_indikator_id = i.indikator_id AND r.opd_id = i.opd_id
LEFT JOIN (
  SELECT tr.pk_indikator_id, tr.opd_id, COUNT(*) AS baris,
         SUM(m.metode_perhitungan IS NULL OR m.metode_perhitungan = '') AS metode_kosong,
         SUM(
           (m.capaian_triwulan_1 IS NULL OR m.capaian_triwulan_1 = '')
           OR (@tw >= 2 AND (m.capaian_triwulan_2 IS NULL OR m.capaian_triwulan_2 = ''))
           OR (@tw >= 3 AND (m.capaian_triwulan_3 IS NULL OR m.capaian_triwulan_3 = ''))
           OR (@tw >= 4 AND (m.capaian_triwulan_4 IS NULL OR m.capaian_triwulan_4 = ''))
         ) AS capaian_kosong
  FROM target_rencana tr
  -- BARIS UKUR CURRENT SAJA. Aturannya disamakan persis dengan
  -- OpdDashboardService::getIndicators(); kalau berbeda, SQL ini akan
  -- melaporkan "metode belum dipilih" untuk indikator yang di layar justru
  -- sudah lengkap — dan dua-duanya akan tampak meyakinkan.
  --
  --   sub ada      -> hanya baris sub yang mengukur
  --   sub tidak ada-> baris tingkat rencana aksi yang mengukur
  --   sub yatim    -> tidak mengukur apa pun (induknya sudah tiada)
  JOIN monev m
    ON m.target_rencana_id = tr.id
   AND (
         (COALESCE(m.target_sub_rencana_id, 0) > 0
          AND EXISTS (SELECT 1 FROM target_sub_rencana s2 WHERE s2.id = m.target_sub_rencana_id))
         OR
         (COALESCE(m.target_sub_rencana_id, 0) = 0
          AND NOT EXISTS (SELECT 1 FROM target_sub_rencana s1 WHERE s1.target_rencana_id = tr.id))
       )
  GROUP BY tr.pk_indikator_id, tr.opd_id
) mv ON mv.pk_indikator_id = i.indikator_id AND mv.opd_id = i.opd_id
LEFT JOIN (
  SELECT pp.pk_indikator_id, SUM(pr.anggaran) AS pagu
  FROM pk_program pp
  JOIN program_pk pr ON pr.id = pp.program_id AND pr.tahun_anggaran = @tahun
  GROUP BY pp.pk_indikator_id
) pg ON pg.pk_indikator_id = i.indikator_id
LEFT JOIN (
  SELECT tr.pk_indikator_id, tr.opd_id, COUNT(*) AS baris
  FROM target_rencana tr
  JOIN monev_anggaran ma ON ma.target_rencana_id = tr.id
  GROUP BY tr.pk_indikator_id, tr.opd_id
) ag ON ag.pk_indikator_id = i.indikator_id AND ag.opd_id = i.opd_id
GROUP BY i.opd_id, i.nama_opd
ORDER BY i.opd_id;
