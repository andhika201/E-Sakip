-- =====================================================================
-- LURUSKAN ARAH INDIKATOR YANG BERTENTANGAN ANTAR DOKUMEN
-- Tanggal : 2026-09-23
-- Sifat   : IDEMPOTEN. Hanya mengubah 3 baris yang menyimpang dari
--           12 baris sepadannya. Tidak ada DELETE, tidak ada INSERT.
--
-- Lanjutan dari db/perbaiki_2026-09-23_jenis_indikator_iku_kabupaten.sql.
-- Skrip itu MENGISI jenis yang kosong; skrip ini MELURUSKAN jenis yang
-- terisi tetapi bertentangan dengan dokumen sepadannya.
--
-- =====================================================================
-- MASALAHNYA
--
-- Audit menyilangkan `jenis_indikator` untuk indikator BERNAMA SAMA di
-- seluruh tabel yang memilikinya. Satu nama terbukti tidak konsisten:
--
--   "Prosentase Sasaran Penyelenggaraan Urusan Pemerintahan Umum"
--
--     iku_indikator                #99,122,135,140,163  positif   (5)
--     iku_indikator                #78                  NEGATIF   (1)  <-- menyimpang
--     iku_revisi_indikator         #93,109,137,142      positif   (4)
--     iku_revisi_indikator         #72                  NEGATIF   (1)  <-- menyimpang
--     renstra_indikator_sasaran    #292,355,492,493,528 positif   (5)
--     renstra_indikator_sasaran    #484                 NEGATIF   (1)  <-- menyimpang
--
-- Dua belas baris mengatakan positif, tiga mengatakan negatif. Secara
-- makna pun positif yang benar: "prosentase sasaran yang terselenggara"
-- — makin tinggi persentasenya makin baik, bukan sebaliknya.
--
-- =====================================================================
-- DAMPAK ANGKANYA: NIHIL HARI INI
--
-- Satu-satunya baris LAKIP yang memakai indikator ini adalah lakip#165
-- (2025) dengan target 80 dan realisasi 80. Karena target = realisasi,
-- kedua arah menghasilkan angka yang sama:
--
--     positif : 80 / 80 x 100 = 100%
--     negatif : 80 / 80 x 100 = 100%
--
-- Jadi skrip ini TIDAK mengubah satu pun capaian yang sudah dilaporkan.
-- Yang diperbaiki adalah arah penilaiannya, supaya tahun depan — ketika
-- realisasinya tidak lagi sama dengan target — indikator ini tidak dinilai
-- terbalik. Dibiarkan, realisasi 90 terhadap target 80 akan dibaca 88,89%
-- (seolah meleset) padahal seharusnya 112,5% (melampaui target).
--
-- =====================================================================
-- YANG SENGAJA TIDAK DISENTUH
--
-- `pk_indikator` juga memuat tiga pertentangan:
--
--     #2424 "Angka Kemiskinan"                   = Indikator Positif
--           (iku#4,204,208 & rpjmd#103 = negatif)
--     #1932 "Angka Populasi bebas penyakit menular" = Indikator Negatif
--           (iku#33 & renstra#86 = positif)
--     #1933 "Angka populasi bebas PTM"              = Indikator Negatif
--           (iku#34 & renstra#87 = positif)
--
-- Ketiganya DIBIARKAN atas keputusan pemilik dokumen (23 Sep 2026).
-- Alasan teknisnya: `pk_indikator.jenis_indikator` hanya DISIMPAN dan
-- DITAMPILKAN di form PK — tidak ada satu pun perhitungan yang membacanya.
-- MONEV menghitung capaian dari `monev.metode_perhitungan`
-- (sum | trend_naik | trend_turun | trend_flat), bukan dari kolom ini.
-- Jadi ketiganya label yang keliru, bukan angka yang keliru.
-- =====================================================================

SELECT '========== SEBELUM ==========' AS `laporan`;

SELECT 'iku_indikator' AS tabel, id, jenis_indikator
  FROM iku_indikator
 WHERE indikator = 'Prosentase Sasaran Penyelenggaraan Urusan Pemerintahan Umum'
UNION ALL
SELECT 'iku_revisi_indikator', id, jenis_indikator
  FROM iku_revisi_indikator
 WHERE indikator = 'Prosentase Sasaran Penyelenggaraan Urusan Pemerintahan Umum'
UNION ALL
SELECT 'renstra_indikator_sasaran', id, jenis_indikator
  FROM renstra_indikator_sasaran
 WHERE indikator_sasaran = 'Prosentase Sasaran Penyelenggaraan Urusan Pemerintahan Umum'
ORDER BY 1, 2;

-- ---------------------------------------------------------------------
-- IKU berjalan
-- ---------------------------------------------------------------------
UPDATE `iku_indikator`
   SET jenis_indikator = 'positif', updated_at = NOW()
 WHERE indikator = 'Prosentase Sasaran Penyelenggaraan Urusan Pemerintahan Umum'
   AND LOWER(jenis_indikator) LIKE '%negatif%';

-- ---------------------------------------------------------------------
-- Arsip revisi IKU — inilah yang dibaca layar LAKIP
-- ---------------------------------------------------------------------
UPDATE `iku_revisi_indikator`
   SET jenis_indikator = 'positif', updated_at = NOW()
 WHERE indikator = 'Prosentase Sasaran Penyelenggaraan Urusan Pemerintahan Umum'
   AND LOWER(jenis_indikator) LIKE '%negatif%';

-- ---------------------------------------------------------------------
-- Renstra berjalan
-- ---------------------------------------------------------------------
UPDATE `renstra_indikator_sasaran`
   SET jenis_indikator = 'positif', updated_at = NOW()
 WHERE indikator_sasaran = 'Prosentase Sasaran Penyelenggaraan Urusan Pemerintahan Umum'
   AND LOWER(jenis_indikator) LIKE '%negatif%';

-- Arsip versi Renstra: disertakan supaya skrip tetap benar bila di server
-- ada barisnya (di basis data pengembangan tidak ada).
UPDATE `renstra_versi_indikator_sasaran`
   SET jenis_indikator = 'positif', updated_at = NOW()
 WHERE indikator_sasaran = 'Prosentase Sasaran Penyelenggaraan Urusan Pemerintahan Umum'
   AND LOWER(jenis_indikator) LIKE '%negatif%';

SELECT '========== SESUDAH ==========' AS `laporan`;

SELECT 'iku_indikator' AS tabel, id, jenis_indikator
  FROM iku_indikator
 WHERE indikator = 'Prosentase Sasaran Penyelenggaraan Urusan Pemerintahan Umum'
UNION ALL
SELECT 'iku_revisi_indikator', id, jenis_indikator
  FROM iku_revisi_indikator
 WHERE indikator = 'Prosentase Sasaran Penyelenggaraan Urusan Pemerintahan Umum'
UNION ALL
SELECT 'renstra_indikator_sasaran', id, jenis_indikator
  FROM renstra_indikator_sasaran
 WHERE indikator_sasaran = 'Prosentase Sasaran Penyelenggaraan Urusan Pemerintahan Umum'
ORDER BY 1, 2;

-- Capaian lakip#165 harus tetap 100% — pembuktian bahwa tidak ada angka
-- laporan yang bergeser oleh skrip ini.
SELECT l.id            AS lakip_id,
       l.tahun,
       t.target,
       l.capaian_tahun_ini                                  AS realisasi,
       i.jenis_indikator,
       ROUND(CAST(REPLACE(l.capaian_tahun_ini, ',', '.') AS DECIMAL(20,6))
           / NULLIF(CAST(REPLACE(t.target, ',', '.') AS DECIMAL(20,6)), 0) * 100, 2) AS capaian_persen
  FROM lakip l
  JOIN renstra_target t            ON t.id = l.renstra_target_id
  JOIN renstra_indikator_sasaran i ON i.id = t.renstra_indikator_id
 WHERE i.indikator_sasaran = 'Prosentase Sasaran Penyelenggaraan Urusan Pemerintahan Umum';

-- =====================================================================
-- ROLLBACK
--
--   UPDATE iku_indikator             SET jenis_indikator='negatif' WHERE id=78;
--   UPDATE iku_revisi_indikator      SET jenis_indikator='negatif' WHERE id=72;
--   UPDATE renstra_indikator_sasaran SET jenis_indikator='negatif' WHERE id=484;
--
-- Periksa dulu id-nya di server lewat query "SEBELUM" di atas — id di sana
-- bisa berbeda.
-- =====================================================================
