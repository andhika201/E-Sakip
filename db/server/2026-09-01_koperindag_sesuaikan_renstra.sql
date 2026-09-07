-- =====================================================================
-- KOPERINDAG — SELARASKAN IKU DENGAN RENSTRA
-- 1 September 2026. Dijalankan lewat phpMyAdmin.
--
-- Acuan yang benar adalah RENSTRA BERJALAN. Skrip ini membawa IKU
-- Koperindag (opd_id 16) mengikutinya, sambil menyelamatkan segala yang
-- sudah menempel.
--
-- Disusun dan DIUJI terhadap salinan `Data_server.sql`.
--
-- TIDAK ADA `ALTER TABLE` di berkas ini, jadi tidak ada perintah yang bisa
-- gagal karena "kolom sudah ada". Kalau ada yang merah, berhenti.
--
-- Aman diulang: tiap langkah menolak bekerja bila hasilnya sudah ada.
--
-- CADANGKAN DULU.
--     mysqldump -u USER -p NAMA_DB > sebelum_koperindag_1sep.sql
-- =====================================================================
--
-- KEADAAN SEKARANG (dari Data_server.sql)
--
--   RENSTRA — 3 sasaran, 4 indikator:
--     97  Meningkatnya Daya Saing Koperasi dan UMKM
--          215  Persentase Meningkatnya Koperasi yang Berkualitas   30,30 .. 50,00
--          527  Proporsi UKM Menjalin Kemitraan dan Ekspor           1,81 ..  2,89
--     179 Meningkatnya kinerja dan daya saing sektor perdagangan
--          470  Kontribusi PDRB Perdagangan Besar dan Eceran
--     181 Meningkatnya pertumbuhan dan daya saing IKM
--          472  Kontribusi PDRB Industri Pengolahan
--
--   IKU — 4 sasaran, 4 indikator:
--     100 Meningkatnya Daya Saing Koperasi          ->97   126 ->215  (teks tertinggal)
--     101 Meningkatnya Daya Saing UMKM              ->NULL 127 ->469  (leluhur SUDAH TIADA)
--     102 Meningkatnya kinerja ...                  ->179  128 ->470  (teks tertinggal)
--     103 Meningkatnya pertumbuhan ...              ->181  129 ->472  (sudah cocok)
--
-- Jadi ada dua hal berbeda, dan ditangani berbeda:
--
--   (a) TEKS TERTINGGAL. Sasaran 100 dan indikator 126/128 silsilahnya
--       BENAR — targetnya cocok angka per angka dengan Renstra — hanya
--       redaksinya belum ikut diperbarui. Cukup disalin ulang.
--
--   (b) SASARAN 101 TIDAK ADA LAGI DI RENSTRA. Leluhurnya, sasaran Renstra
--       178 dan indikator Renstra 469, dua-duanya sudah dihapus. Yang
--       menggantikannya di Renstra adalah indikator 527 "Proporsi UKM
--       Menjalin Kemitraan dan Ekspor", yang bernaung di sasaran 97
--       bersama indikator koperasi.
--
--       Padanya menempel 5 baris cascading UMKM dan 2 baris LAKIP.
--       Itulah yang diselamatkan: dipindahkan ke indikator 527 yang baru,
--       BUKAN dibuang bersama induknya.
-- =====================================================================


SELECT '=== SEBELUM: IKU Koperindag ===' AS laporan;
SELECT s.id AS sas, LEFT(s.sasaran, 42) AS sasaran, s.source_sasaran_id AS ref,
       i.id AS ind, LEFT(i.indikator, 44) AS indikator, i.source_indikator_id AS ref_ind
FROM `iku_sasaran` s
LEFT JOIN `iku_indikator` i ON i.iku_sasaran_id = s.id AND i.dihentikan_pada IS NULL
WHERE s.opd_id = 16 AND s.dihentikan_pada IS NULL
ORDER BY s.urutan, i.urutan;


-- =====================================================================
-- BAGIAN 1 — SALIN ULANG REDAKSI DARI RENSTRA
--
-- Hanya menyentuh baris yang silsilahnya MASIH SAH (`source_*_id` benar
-- menunjuk baris Renstra yang hidup). Baris yatim sengaja tidak ikut,
-- karena bagi mereka tidak ada sumber yang bisa dipercaya.
--
-- Teks diambil lewat JOIN, bukan diketik ulang di sini — supaya tidak ada
-- peluang salah salin.
-- =====================================================================

-- 1a. Redaksi sasaran.
UPDATE `iku_sasaran` s
  JOIN `renstra_sasaran` rs ON rs.id = s.source_sasaran_id
   SET s.sasaran    = rs.sasaran,
       s.updated_at = NOW()
 WHERE s.opd_id = 16
   AND s.dihentikan_pada IS NULL
   AND rs.dihentikan_pada IS NULL
   AND s.sasaran <> rs.sasaran;

-- 1b. Redaksi indikator, beserta satuan dan kondisi awalnya.
UPDATE `iku_indikator` i
  JOIN `iku_sasaran` s ON s.id = i.iku_sasaran_id
  JOIN `renstra_indikator_sasaran` ris ON ris.id = i.source_indikator_id
   SET i.indikator  = ris.indikator_sasaran,
       i.satuan     = ris.satuan,
       i.baseline   = ris.baseline,
       i.updated_at = NOW()
 WHERE s.opd_id = 16
   AND i.dihentikan_pada IS NULL
   AND ris.dihentikan_pada IS NULL
   AND (i.indikator <> ris.indikator_sasaran
        OR COALESCE(i.satuan, '')   <> COALESCE(ris.satuan, '')
        OR COALESCE(i.baseline, '') <> COALESCE(ris.baseline, ''));


-- =====================================================================
-- BAGIAN 2 — INDIKATOR UMKM YANG BARU (Renstra 527)
--
-- Dibuat lebih dulu, SEBELUM yang lama dibongkar, supaya cascading dan
-- LAKIP punya tempat berpindah dan tidak pernah menggantung sedetik pun.
-- =====================================================================

-- 2a. Baris indikatornya, disalin dari Renstra.
INSERT INTO `iku_indikator`
    (`iku_sasaran_id`, `indikator`, `satuan`, `baseline`, `jenis_indikator`,
     `urutan`, `status`, `jenis_perubahan`, `perubahan_substansial`,
     `source_type`, `source_indikator_id`, `created_at`, `updated_at`)
SELECT s.id, ris.indikator_sasaran, ris.satuan, ris.baseline,
       COALESCE(ris.jenis_indikator, 'positif'),
       (SELECT COUNT(*) FROM `iku_indikator` x
         WHERE x.iku_sasaran_id = s.id AND x.dihentikan_pada IS NULL),
       'draft', 'tetap', 0, 'renstra', ris.id, NOW(), NOW()
FROM `renstra_indikator_sasaran` ris
JOIN `iku_sasaran` s
  ON s.source_sasaran_id = ris.renstra_sasaran_id
 AND s.opd_id = 16
 AND s.dihentikan_pada IS NULL
WHERE ris.id = 527
  AND ris.dihentikan_pada IS NULL
  AND NOT EXISTS (SELECT 1 FROM `iku_indikator` y
                   WHERE y.iku_sasaran_id = s.id AND y.source_indikator_id = ris.id
                     AND y.dihentikan_pada IS NULL);

-- 2b. Target tahunannya, juga disalin dari Renstra.
INSERT INTO `iku_target` (`iku_indikator_id`, `tahun`, `target`, `created_at`, `updated_at`)
SELECT i.id, rt.tahun, rt.target, NOW(), NOW()
FROM `iku_indikator` i
JOIN `iku_sasaran` s ON s.id = i.iku_sasaran_id AND s.opd_id = 16
JOIN `renstra_target` rt ON rt.renstra_indikator_id = i.source_indikator_id
WHERE i.source_indikator_id = 527
  AND i.dihentikan_pada IS NULL
  AND NOT EXISTS (SELECT 1 FROM `iku_target` y
                   WHERE y.iku_indikator_id = i.id AND y.tahun = rt.tahun);


-- =====================================================================
-- BAGIAN 3 — SELAMATKAN YANG MENEMPEL PADA INDIKATOR 127
--
-- Kelima baris cascading itu bertema UMKM seluruhnya:
--     945 es3  Meningkatnya kapasitas SDM UMKM
--     946 es4  Terlaksanannya SDM UMKM yang berkualitas
--     947 es3  Meningkatnya akses pemasaran UMKM
--     949 es4  Terwujudnya akses pasar UMKM yang lebih luas
--     950 es4  Terwujudnya UKM yang adaptif terhadap teknologi
--
-- `renstra_indikator_sasaran_id` mereka NULL, jadi indikator IKU adalah
-- SATU-SATUNYA jangkarnya. Karena foreign key-nya ON DELETE SET NULL,
-- menghapus 127 lebih dulu akan membuat kelimanya kehilangan seluruh
-- jangkar — barisnya selamat tapi tidak punya tempat di pohon mana pun.
-- Karena itu dipindahkan DULU, dihapus BELAKANGAN.
--
-- Rumah barunya adalah indikator UMKM dari Renstra, yang memang penerus
-- perkara yang sama.
-- =====================================================================

-- 3a. Pindahkan cascading.
UPDATE `cascading_sasaran_opd` c
  JOIN `iku_indikator` baru
    ON baru.source_indikator_id = 527
   AND baru.dihentikan_pada IS NULL
  JOIN `iku_sasaran` s ON s.id = baru.iku_sasaran_id AND s.opd_id = 16
   SET c.iku_indikator_id = baru.id,
       c.updated_at       = NOW()
 WHERE c.iku_indikator_id = 127;

-- 3b. Pindahkan LAKIP. Kedua barisnya (2025 & 2026) masih kosong —
--     `capaian_tahun_ini` hampa, `target_hitung` NULL, status draft — jadi
--     tidak ada capaian yang berpindah makna. Dipindahkan, bukan dihapus,
--     supaya baris tahunnya tidak hilang dari laporan.
UPDATE `lakip` l
  JOIN `iku_indikator` baru
    ON baru.source_indikator_id = 527
   AND baru.dihentikan_pada IS NULL
  JOIN `iku_sasaran` s ON s.id = baru.iku_sasaran_id AND s.opd_id = 16
   SET l.source_entity_id = baru.id,
       l.updated_at       = NOW()
 WHERE l.source_type = 'iku'
   AND l.source_entity_id = 127
   AND NOT EXISTS (SELECT 1 FROM (SELECT id, tahun, source_entity_id, source_type, opd_id
                                    FROM `lakip`) z
                    WHERE z.source_type = 'iku' AND z.opd_id = l.opd_id
                      AND z.tahun = l.tahun AND z.source_entity_id = baru.id);


-- =====================================================================
-- BAGIAN 4 — BONGKAR SASARAN 101
--
-- Baru sekarang, sesudah semua yang menempel punya rumah.
-- Indikator 127 dan 5 target tahunannya ikut terhapus lewat ON DELETE
-- CASCADE. Tidak ada `iku_program` yang menempel (sudah diperiksa: 0).
-- =====================================================================

-- 4a. Putuskan jejak arsip lebih dulu, supaya tidak menunjuk baris yang tiada.
UPDATE `iku_revisi_indikator` ri
  JOIN `iku_indikator` i ON i.id = ri.sumber_indikator_id
   SET ri.sumber_indikator_id = NULL
 WHERE i.iku_sasaran_id = 101;

UPDATE `iku_revisi_sasaran` rs
   SET rs.sumber_sasaran_id = NULL
 WHERE rs.sumber_sasaran_id = 101;

-- 4b. Barulah dihapus.
DELETE FROM `iku_sasaran` WHERE `id` = 101 AND `opd_id` = 16;


-- =====================================================================
-- BAGIAN 5 — SEGARKAN ARSIP REVISI
--
-- Koperindag punya dua revisi terarsip, dan ISINYA PERSIS SAMA: revisi
-- ke-1 (superseded, 2026) dan "Kondisi Awal" (berlaku, 2027-). Keduanya
-- potret otomatis dari live, bukan dua dokumen yang pernah diputuskan
-- berbeda. Membiarkannya berarti LAKIP 2026 dan 2027 terus membaca nama
-- sasaran yang sudah tidak ada di Renstra.
--
-- Aman disegarkan karena SELURUH 8 baris LAKIP Koperindag masih kosong —
-- belum ada satu pun capaian yang dilaporkan. Kalau nanti sudah ada isi,
-- bagian seperti ini TIDAK boleh dijalankan lagi begitu saja.
--
-- Draft dan pengajuan tidak disentuh; hanya berlaku/superseded.
-- =====================================================================

-- 5a. Kosongkan isi arsip lama. `iku_revisi_indikator`, `iku_revisi_target`,
--     dan `iku_revisi_program` ikut terhapus lewat ON DELETE CASCADE.
--
--     PENJAGA: hanya revisi yang periodenya PUNYA padanan di IKU berjalan
--     yang dikosongkan. Tanpa ini, revisi berperiode lain (mis. 2029-2033)
--     akan dikosongkan oleh 5a tetapi tidak akan dibangun ulang oleh 5b,
--     karena 5b menjodohkan lewat tahun_mulai/tahun_akhir. Arsipnya lenyap
--     tanpa pengganti. Saat ini kedua revisi Koperindag berperiode
--     2025-2029 sehingga penjaga ini tidak menolak apa pun — ia ada untuk
--     berjaga bila skrip ini dijalankan lagi di kemudian hari.
DELETE rs FROM `iku_revisi_sasaran` rs
JOIN `iku_revisi` r ON r.id = rs.revisi_id
WHERE r.opd_key = 16
  AND r.status IN ('berlaku', 'superseded')
  AND EXISTS (SELECT 1 FROM `iku_sasaran` s
               WHERE s.opd_id = 16 AND s.dihentikan_pada IS NULL
                 AND s.tahun_mulai = r.tahun_mulai
                 AND s.tahun_akhir = r.tahun_akhir);

-- 5b. Bekukan ulang dari live yang sudah benar.
INSERT INTO `iku_revisi_sasaran`
    (`revisi_id`, `sumber_sasaran_id`, `source_type`, `source_version_id`,
     `source_ref_id`, `renstra_tujuan_id`, `sasaran`, `tahun_mulai`, `tahun_akhir`,
     `urutan`, `jenis_perubahan`, `created_at`, `updated_at`)
SELECT r.id, s.id, s.source_type, s.source_version_id, s.source_sasaran_id,
       s.renstra_tujuan_id, s.sasaran, s.tahun_mulai, s.tahun_akhir, s.urutan,
       'tetap', NOW(), NOW()
FROM `iku_revisi` r
JOIN `iku_sasaran` s
  ON s.opd_id = 16
 AND s.tahun_mulai = r.tahun_mulai
 AND s.tahun_akhir = r.tahun_akhir
 AND s.dihentikan_pada IS NULL
WHERE r.opd_key = 16
  AND r.status IN ('berlaku', 'superseded')
  AND NOT EXISTS (SELECT 1 FROM `iku_revisi_sasaran` x WHERE x.revisi_id = r.id)
ORDER BY r.id, s.urutan, s.id;

INSERT INTO `iku_revisi_indikator`
    (`revisi_id`, `revisi_sasaran_id`, `sumber_indikator_id`, `source_type`,
     `source_version_id`, `source_ref_id`, `indikator`, `definisi`,
     `rumusan_perhitungan`, `satuan`, `satuan_nama`, `sumber_data`,
     `penanggung_jawab`, `jenis_indikator`, `baseline`, `urutan`, `status`,
     `jenis_perubahan`, `perubahan_substansial`, `created_at`, `updated_at`)
SELECT rs.revisi_id, rs.id, i.id, i.source_type, i.source_version_id,
       i.source_indikator_id, i.indikator, i.definisi, i.rumusan_perhitungan,
       i.satuan, COALESCE(sa.satuan, NULLIF(i.satuan, '')),
       i.sumber_data, i.penanggung_jawab, i.jenis_indikator, i.baseline,
       i.urutan, COALESCE(i.status, 'draft'), 'tetap', 0, NOW(), NOW()
FROM `iku_revisi_sasaran` rs
JOIN `iku_revisi` r
  ON r.id = rs.revisi_id AND r.opd_key = 16 AND r.status IN ('berlaku', 'superseded')
JOIN `iku_indikator` i
  ON i.iku_sasaran_id = rs.sumber_sasaran_id AND i.dihentikan_pada IS NULL
LEFT JOIN `satuan` sa ON sa.id = i.satuan
WHERE NOT EXISTS (SELECT 1 FROM `iku_revisi_indikator` y WHERE y.revisi_sasaran_id = rs.id)
ORDER BY rs.id, i.urutan, i.id;

INSERT INTO `iku_revisi_target`
    (`revisi_indikator_id`, `tahun`, `target`, `created_at`, `updated_at`)
SELECT ri.id, t.tahun, t.target, NOW(), NOW()
FROM `iku_revisi_indikator` ri
JOIN `iku_revisi` r
  ON r.id = ri.revisi_id AND r.opd_key = 16 AND r.status IN ('berlaku', 'superseded')
JOIN `iku_target` t ON t.iku_indikator_id = ri.sumber_indikator_id
WHERE NOT EXISTS (SELECT 1 FROM `iku_revisi_target` y
                   WHERE y.revisi_indikator_id = ri.id AND y.tahun = t.tahun);


-- =====================================================================
-- BAGIAN 6 — DRAFT YANG TERLANJUR KEMBAR
--
-- Draft yang dibuat sebelum perbaikan kode 1 Sep memuat dua baris untuk
-- sasaran Renstra yang sama (mis. draft 78: baris 204 dan 208, dua-duanya
-- `source_ref_id` 97). Isinya sudah usang sekarang, karena live sudah
-- diselaraskan.
--
-- Draft belum diajukan dan tidak dibaca LAKIP, jadi menghapusnya tidak
-- menghilangkan apa pun yang tidak bisa dibuat ulang dalam sekali klik —
-- dan draft baru akan lahir sudah benar.
--
-- Hanya draft KOPERINDAG yang bersasaran kembar yang dihapus.
-- =====================================================================

DELETE r FROM `iku_revisi` r
WHERE r.opd_key = 16
  AND r.status = 'draft'
  AND EXISTS (SELECT 1 FROM (SELECT revisi_id, source_ref_id
                               FROM `iku_revisi_sasaran`
                              WHERE source_ref_id IS NOT NULL
                              GROUP BY revisi_id, source_ref_id
                             HAVING COUNT(*) > 1) k
               WHERE k.revisi_id = r.id);


-- =====================================================================
-- PEMERIKSAAN
-- =====================================================================

SELECT '=== SESUDAH: IKU Koperindag (harus 3 sasaran, 4 indikator) ===' AS laporan;
SELECT s.id AS sas, LEFT(s.sasaran, 42) AS sasaran, s.source_sasaran_id AS ref,
       i.id AS ind, LEFT(i.indikator, 44) AS indikator, i.source_indikator_id AS ref_ind,
       (SELECT COUNT(*) FROM `iku_target` t WHERE t.iku_indikator_id = i.id) AS target
FROM `iku_sasaran` s
LEFT JOIN `iku_indikator` i ON i.iku_sasaran_id = s.id AND i.dihentikan_pada IS NULL
WHERE s.opd_id = 16 AND s.dihentikan_pada IS NULL
ORDER BY s.urutan, i.urutan;

SELECT '=== masih ada yang beda dari Renstra? (harus kosong) ===' AS laporan;
SELECT s.id AS sasaran, LEFT(s.sasaran, 40) AS iku, LEFT(rs.sasaran, 40) AS renstra
FROM `iku_sasaran` s JOIN `renstra_sasaran` rs ON rs.id = s.source_sasaran_id
WHERE s.opd_id = 16 AND s.dihentikan_pada IS NULL AND s.sasaran <> rs.sasaran;

SELECT '=== cascading UMKM harus tetap 5 dan BERJANGKAR ===' AS laporan;
SELECT c.id, c.level, LEFT(c.nama_sasaran, 44) AS nama_sasaran,
       c.iku_indikator_id AS jangkar, LEFT(i.indikator, 40) AS indikator
FROM `cascading_sasaran_opd` c
LEFT JOIN `iku_indikator` i ON i.id = c.iku_indikator_id
WHERE c.id IN (945, 946, 947, 949, 950);

SELECT '=== cascading yatim di Koperindag (harus 0) ===' AS laporan;
SELECT COUNT(*) AS cascading_tanpa_jangkar
FROM `cascading_sasaran_opd`
WHERE opd_id = 16 AND renstra_indikator_sasaran_id IS NULL AND iku_indikator_id IS NULL;

SELECT '=== LAKIP Koperindag (harus 8 baris, semua berjangkar) ===' AS laporan;
SELECT l.id, l.tahun, l.source_entity_id AS jangkar, LEFT(i.indikator, 40) AS indikator
FROM `lakip` l
LEFT JOIN `iku_indikator` i ON i.id = l.source_entity_id AND l.source_type = 'iku'
WHERE l.opd_id = 16 ORDER BY l.tahun, l.id;

SELECT '=== arsip revisi sesudah disegarkan ===' AS laporan;
SELECT r.id AS revisi, r.nomor, r.status, r.berlaku_mulai_tahun AS mulai,
       (SELECT COUNT(*) FROM `iku_revisi_sasaran` x WHERE x.revisi_id = r.id) AS sasaran,
       (SELECT COUNT(*) FROM `iku_revisi_indikator` x WHERE x.revisi_id = r.id) AS indikator,
       (SELECT COUNT(*) FROM `iku_revisi_target` t
          JOIN `iku_revisi_indikator` i2 ON i2.id = t.revisi_indikator_id
         WHERE i2.revisi_id = r.id) AS target
FROM `iku_revisi` r WHERE r.opd_key = 16 ORDER BY r.id;
