-- =====================================================================
-- JANGKAR ULANG BARIS LAKIP YANG LINGKUPNYA UTUH TAPI JANGKARNYA PUTUS
-- Tanggal : 2026-08-29
-- Sifat   : IDEMPOTEN & DETERMINISTIK. Hanya mengisi jangkar yang NULL,
--           dengan syarat lingkup + jejak target lamanya cocok persis.
--
-- =====================================================================
-- ASAL-USULNYA
--
-- 20 baris realisasi tersimpan LENGKAP lingkupnya (opd, tahun, mode,
-- source_type='renstra') tetapi jangkar `renstra_target_id`-nya NULL:
-- target Renstra-nya dihapus-tanam-ulang sesudah baris lahir, dan FK
-- ON DELETE SET NULL memutus tautannya.
--
-- Bedanya dengan 123 yatim yang diarsipkan: baris-baris ini menyimpan
-- `source_entity_id` = id target LAMA. Lewat cadangan basis data bergenerasi
-- lama, id lama itu diterjemahkan ke indikatornya, lalu indikator yang sama
-- dicari di Renstra HARI INI — pemetaannya deterministik, bukan tebakan:
--
--     source_entity_id (lama) -> indikator -> renstra_target (baru)
--
-- 16 dari 20 terpetakan penuh; pasangannya tertulis LITERAL di bawah dan
-- sudah diverifikasi identik antara lokal dan server (id, tahun, nilai).
-- 4 sisanya diperiksa MANUAL lewat layar masing-masing akun:
--   * Setwan #204: indikatornya MASIH ADA (teks sama; padanan teks otomatis
--     meleset karena beda spasi) dan slotnya kosong -> dipulangkan.
--   * Sekda #169: Renstra Sekda dirampingkan 3 indikator -> 1; "Indeks RB
--     General" adalah nama lama "Indeks Reformasi Birokrasi" -> dipulangkan.
--   * Sekda #189 (RB Tematik) & #190 (Survey Kepuasan Internal): indikatornya
--     DIHAPUS dari Renstra -> nilai diarsipkan, baris dikeluarkan.
--
-- =====================================================================
-- BARIS DINKES 26 AGUSTUS ADALAH YANG TERBARU
--
-- Untuk 4 slot Dinkes, entri 26 Agustus (baris 266/268/269/271) menggantikan
-- entri Januari-Juli yang sempat diusulkan lewat sidik jari (arsip #43/45/
-- 102/146). Kata terakhir operator yang menang; yang kalah tetap terbaca
-- utuh di lakip_yatim_arsip dengan nasib 'digantikan'.
-- =====================================================================

SELECT '========== SEBELUM ==========' AS laporan;
SELECT COUNT(*) AS berlingkup_tanpa_jangkar FROM lakip
 WHERE mode='opd' AND source_type='renstra' AND renstra_target_id IS NULL;

-- ---- Dinas Kesehatan 2025 (entri 26 Agustus) -------------------------
UPDATE lakip SET renstra_target_id=5212, source_entity_id=5212 WHERE id=266 AND renstra_target_id IS NULL AND source_entity_id=5032; -- AKI: 62
UPDATE lakip SET renstra_target_id=5217, source_entity_id=5217 WHERE id=267 AND renstra_target_id IS NULL AND source_entity_id=5037; -- Balita: 5
UPDATE lakip SET renstra_target_id=5197, source_entity_id=5197 WHERE id=268 AND renstra_target_id IS NULL AND source_entity_id=5052; -- PTM: 89,86
UPDATE lakip SET renstra_target_id=5192, source_entity_id=5192 WHERE id=269 AND renstra_target_id IS NULL AND source_entity_id=5047; -- bebas PM: 98,95
UPDATE lakip SET renstra_target_id=5202, source_entity_id=5202 WHERE id=270 AND renstra_target_id IS NULL AND source_entity_id=5057; -- pemeriksaan gratis: 50
UPDATE lakip SET renstra_target_id=5207, source_entity_id=5207 WHERE id=271 AND renstra_target_id IS NULL AND source_entity_id=5062; -- fasyankes: 100

-- ---- Dinas Kesehatan 2026 (kerangka kosong, tetap dirapikan) ---------
UPDATE lakip SET renstra_target_id=5193, source_entity_id=5193 WHERE id=272 AND renstra_target_id IS NULL AND source_entity_id=5048;
UPDATE lakip SET renstra_target_id=5198, source_entity_id=5198 WHERE id=273 AND renstra_target_id IS NULL AND source_entity_id=5053;
UPDATE lakip SET renstra_target_id=5203, source_entity_id=5203 WHERE id=274 AND renstra_target_id IS NULL AND source_entity_id=5058;
UPDATE lakip SET renstra_target_id=5208, source_entity_id=5208 WHERE id=275 AND renstra_target_id IS NULL AND source_entity_id=5063;
UPDATE lakip SET renstra_target_id=5213, source_entity_id=5213 WHERE id=276 AND renstra_target_id IS NULL AND source_entity_id=5033;
UPDATE lakip SET renstra_target_id=5218, source_entity_id=5218 WHERE id=277 AND renstra_target_id IS NULL AND source_entity_id=5038;

-- ---- Disporapar 2025 -------------------------------------------------
UPDATE lakip SET renstra_target_id=5242, source_entity_id=5242 WHERE id=152 AND renstra_target_id IS NULL AND source_entity_id=3142; -- masyarakat pengelola: 274302
UPDATE lakip SET renstra_target_id=5247, source_entity_id=5247 WHERE id=153 AND renstra_target_id IS NULL AND source_entity_id=3147; -- event pariwisata: 2

-- ---- Sekretariat DPRD & Sekretariat Daerah (verifikasi manual) -------
UPDATE lakip SET renstra_target_id=5172, source_entity_id=5172 WHERE id=204 AND renstra_target_id IS NULL AND source_entity_id=2702; -- kepuasan DPRD: 75,95
UPDATE lakip SET renstra_target_id=5187, source_entity_id=5187 WHERE id=169 AND renstra_target_id IS NULL AND source_entity_id=2797; -- IRB: 61.4

-- Indikatornya sudah tidak ada di Renstra: nilainya diarsipkan agar tidak
-- lenyap, barisnya dikeluarkan dari tabel kerja.
INSERT INTO lakip_yatim_arsip (nasib, catatan, lakip_id, isi)
SELECT 'indikator-dihapus',
       CONCAT('indikator lama (target ', l.source_entity_id, ') sudah dihapus dari Renstra Sekda'),
       l.id,
       JSON_OBJECT('id',l.id,'opd_id',l.opd_id,'tahun',l.tahun,
                   'target_lalu',l.target_lalu,'capaian_lalu',l.capaian_lalu,
                   'capaian_tahun_ini',l.capaian_tahun_ini,'status',l.status,
                   'source_entity_id',l.source_entity_id,'created_at',l.created_at)
FROM lakip l
WHERE l.id IN (189,190) AND l.renstra_target_id IS NULL
  AND NOT EXISTS (SELECT 1 FROM lakip_yatim_arsip a WHERE a.lakip_id=l.id AND a.nasib='indikator-dihapus');

DELETE FROM lakip WHERE id IN (189,190) AND renstra_target_id IS NULL;

-- ---- Dinas Pemberdayaan Masyarakat & Pekon ---------------------------
UPDATE lakip SET renstra_target_id=5237, source_entity_id=5237 WHERE id=265 AND renstra_target_id IS NULL AND source_entity_id=3682; -- Indeks Desa 2025
UPDATE lakip SET renstra_target_id=5238, source_entity_id=5238 WHERE id=264 AND renstra_target_id IS NULL AND source_entity_id=3683; -- Indeks Desa 2026

SELECT '========== SESUDAH ==========' AS laporan;
SELECT COUNT(*) AS berlingkup_tanpa_jangkar_sisa FROM lakip
 WHERE mode='opd' AND source_type='renstra' AND renstra_target_id IS NULL;

SELECT '-- sisa berlingkup tanpa jangkar (harus 0) --' AS laporan;
SELECT COUNT(*) AS sisa FROM lakip
 WHERE mode='opd' AND source_type='renstra' AND renstra_target_id IS NULL;

SELECT '-- slot dobel? (harus kosong) --' AS laporan;
SELECT renstra_target_id, COUNT(*) n FROM lakip
 WHERE renstra_target_id IS NOT NULL GROUP BY renstra_target_id HAVING COUNT(*)>1;
