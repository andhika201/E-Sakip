-- =====================================================================
-- SELARASKAN REDAKSI IKU DENGAN RENSTRA — SEMUA OPD
-- 1 September 2026. Dijalankan lewat phpMyAdmin.
--
-- Lanjutan dari 2026-09-01_koperindag_sesuaikan_renstra.sql, yang menangani
-- perkara STRUKTUR (sasaran hilang, cascading dipindahkan). Berkas ini
-- hanya menangani perkara REDAKSI: tidak ada baris yang dibuat, dihapus,
-- atau dipindahkan. Tidak ada id yang berubah. Realisasi LAKIP tidak
-- disentuh sama sekali.
--
-- Boleh dijalankan sebelum atau sesudah berkas Koperindag; keduanya tidak
-- saling mengganggu, dan berkas ini aman diulang.
--
-- TIDAK ADA `ALTER TABLE`, jadi tidak ada yang bisa gagal karena skema.
--
-- CADANGKAN DULU.
--     mysqldump -u USER -p NAMA_DB > sebelum_selaras_teks_1sep.sql
-- =====================================================================
--
-- HASIL PENELUSURAN (dari Data_server.sql)
--
-- Ada 20 ketidakcocokan redaksi antara IKU dan Renstra: 8 pada sasaran,
-- 12 pada indikator, tersebar di 8 OPD. SEMUANYA sudah saya periksa
-- silsilahnya, dan SEMUANYA sah. Buktinya:
--
--   * Target tahunannya cocok angka per angka, termasuk deret yang khas
--     dan tidak mungkin kebetulan — 99,8|99,82|99,85|99,88|99,9 pada
--     Dukcapil, 76,64|77,27|78|78,53|79,17 pada Kec. Pringsewu.
--   * Indikator Renstra yang ditunjuk memang milik sasaran Renstra yang
--     ditunjuk sasaran IKU-nya. Tidak ada yang menyeberang.
--   * Di tiap sasaran, indikator IKU berpasangan SATU-SATU dengan
--     indikator Renstra, dan tidak ada saudara sekandung yang lebih cocok.
--     Pada sasaran Renstra 152 dan 216 malah cuma ada satu di tiap sisi,
--     sehingga salah tunjuk mustahil.
--
-- Jadi ini bukan silsilah yang salah arah. Ini Renstra yang disunting dan
-- IKU yang tidak ikut diperbarui. Menyalin redaksi Renstra ke IKU adalah
-- tindakan yang benar, bukan tebakan.
--
-- Sifat ketidakcocokannya dua macam:
--
--   SPASI SAJA (12) — spasi ganda di tengah, atau baris baru menempel di
--   ujung teks Renstra. Tidak terlihat di layar, tetapi membuat sync
--   menganggapnya berbeda. Ini yang paling banyak.
--
--   BEDA KATA (8):
--     sasaran  105  Prosentase Capaian Pelayanan ... -> Meningkatnya Capaian ...
--     indikator 20  Kepemilikan Akta Kematian       -> Penerbitan Akta Kematian
--     indikator 57  Prosentase Keamanan dan ...     -> Terciptanya Desa dan ...
--     indikator 141 Prosentase Administrasi ...     -> Persentase Administrasi ...
--     indikator 149 Prosentase Pemberdayaan ...     -> Insentif RT dan RW ...
--     indikator 150 Jumlah Kegiatan Pemberdayaan    -> Pembangunan Sarana dan ...
--     indikator 151 Prosentase pencapaian program   -> Fasilitasi Usulan Rencana ...
-- =====================================================================


SELECT '=== SEBELUM: ketidakcocokan IKU vs Renstra ===' AS laporan;
SELECT 'sasaran' AS bagian, COUNT(*) AS jumlah FROM `iku_sasaran` s
  JOIN `renstra_sasaran` rs ON rs.id = s.source_sasaran_id
 WHERE s.dihentikan_pada IS NULL AND rs.dihentikan_pada IS NULL AND s.sasaran <> rs.sasaran
UNION ALL
SELECT 'indikator', COUNT(*) FROM `iku_indikator` i
  JOIN `renstra_indikator_sasaran` ris ON ris.id = i.source_indikator_id
 WHERE i.dihentikan_pada IS NULL AND ris.dihentikan_pada IS NULL
   AND i.indikator <> ris.indikator_sasaran
UNION ALL
SELECT 'arsip revisi', COUNT(*) FROM `iku_revisi_indikator` ri
  JOIN `iku_revisi` r ON r.id = ri.revisi_id AND r.status IN ('berlaku','superseded')
  JOIN `iku_indikator` i ON i.id = ri.sumber_indikator_id
  JOIN `renstra_indikator_sasaran` ris ON ris.id = i.source_indikator_id AND ris.dihentikan_pada IS NULL
 WHERE ri.indikator <> ris.indikator_sasaran;


-- =====================================================================
-- BAGIAN 1 — RAPIKAN SPASI DI SEMUA TEMPAT
--
-- Sumber 12 dari 20 ketidakcocokan itu bukan kata yang berbeda, melainkan
-- spasi ganda dan baris baru yang tertinggal saat teks ditempel dari Word
-- atau Excel. Di layar tidak kelihatan; bagi mesin pembanding, berbeda.
--
-- Dirapikan di KETIGA tempat sekaligus — Renstra, IKU berjalan, dan arsip
-- revisi — supaya ketiganya memakai aturan yang sama. Kalau hanya IKU yang
-- dirapikan sementara Renstra tetap membawa baris barunya, sync akan
-- menganggapnya berbeda lagi besok.
--
-- Tidak ada satu kata pun yang berubah. Hanya spasi berlebih yang dibuang.
-- =====================================================================

-- 1a. Renstra — sumber kebenarannya, dirapikan lebih dulu.
UPDATE `renstra_sasaran`
   SET `sasaran` = TRIM(REGEXP_REPLACE(`sasaran`, '[[:space:]]+', ' '))
 WHERE `sasaran` <> TRIM(REGEXP_REPLACE(`sasaran`, '[[:space:]]+', ' '));

UPDATE `renstra_indikator_sasaran`
   SET `indikator_sasaran` = TRIM(REGEXP_REPLACE(`indikator_sasaran`, '[[:space:]]+', ' '))
 WHERE `indikator_sasaran` <> TRIM(REGEXP_REPLACE(`indikator_sasaran`, '[[:space:]]+', ' '));

-- 1b. IKU berjalan.
UPDATE `iku_sasaran`
   SET `sasaran` = TRIM(REGEXP_REPLACE(`sasaran`, '[[:space:]]+', ' ')), `updated_at` = NOW()
 WHERE `sasaran` <> TRIM(REGEXP_REPLACE(`sasaran`, '[[:space:]]+', ' '));

UPDATE `iku_indikator`
   SET `indikator` = TRIM(REGEXP_REPLACE(`indikator`, '[[:space:]]+', ' ')), `updated_at` = NOW()
 WHERE `indikator` <> TRIM(REGEXP_REPLACE(`indikator`, '[[:space:]]+', ' '));

-- 1c. Arsip revisi IKU. Hanya spasi, jadi tidak ada laporan yang berubah
--     bunyinya — dan realisasi LAKIP tidak menempel pada teks, melainkan
--     pada `sumber_indikator_id`, yang tidak disentuh sama sekali.
UPDATE `iku_revisi_sasaran`
   SET `sasaran` = TRIM(REGEXP_REPLACE(`sasaran`, '[[:space:]]+', ' ')), `updated_at` = NOW()
 WHERE `sasaran` <> TRIM(REGEXP_REPLACE(`sasaran`, '[[:space:]]+', ' '));

UPDATE `iku_revisi_indikator`
   SET `indikator` = TRIM(REGEXP_REPLACE(`indikator`, '[[:space:]]+', ' ')), `updated_at` = NOW()
 WHERE `indikator` <> TRIM(REGEXP_REPLACE(`indikator`, '[[:space:]]+', ' '));


-- =====================================================================
-- BAGIAN 2 — SALIN REDAKSI RENSTRA KE IKU BERJALAN
--
-- Menyentuh HANYA baris yang silsilahnya masih sah: `source_*_id` benar
-- menunjuk baris Renstra yang hidup. Baris yatim dilewati, karena bagi
-- mereka tidak ada sumber yang bisa dipercaya.
--
-- Teks diambil lewat JOIN, tidak diketik ulang di sini, supaya tidak ada
-- peluang salah salin.
--
-- Dampaknya terlihat di menu IKU, Cascading, dan Pohon Kinerja.
-- LAKIP TIDAK ikut berubah oleh bagian ini — ia membaca arsip, bukan IKU
-- berjalan. Itu urusan Bagian 3.
-- =====================================================================

-- 2a. Sasaran.
UPDATE `iku_sasaran` s
  JOIN `renstra_sasaran` rs ON rs.id = s.source_sasaran_id
   SET s.sasaran    = rs.sasaran,
       s.updated_at = NOW()
 WHERE s.dihentikan_pada IS NULL
   AND rs.dihentikan_pada IS NULL
   AND s.sasaran <> rs.sasaran;

-- 2b. Indikator, beserta satuan dan kondisi awalnya.
UPDATE `iku_indikator` i
  JOIN `renstra_indikator_sasaran` ris ON ris.id = i.source_indikator_id
   SET i.indikator  = ris.indikator_sasaran,
       i.satuan     = ris.satuan,
       i.baseline   = ris.baseline,
       i.updated_at = NOW()
 WHERE i.dihentikan_pada IS NULL
   AND ris.dihentikan_pada IS NULL
   AND (i.indikator <> ris.indikator_sasaran
        OR COALESCE(i.satuan, '')   <> COALESCE(ris.satuan, '')
        OR COALESCE(i.baseline, '') <> COALESCE(ris.baseline, ''));


-- =====================================================================
-- BAGIAN 3 — SALIN REDAKSI KE ARSIP REVISI  (BACA DULU)
--
-- Arsip revisi adalah dokumen yang DIBACA LAKIP. `LakipModel::
-- getIndexIkuTargets()` mengambil nama indikator dari `ri.indikator`,
-- bukan dari IKU berjalan. Jadi tanpa bagian ini, LAKIP akan terus
-- menampilkan redaksi lama meskipun IKU sudah benar.
--
-- YANG PERLU ANDA PUTUSKAN. Ini menulis ulang label pada dokumen
-- pertanggungjawaban yang sebagian sudah diisi. Setelah Bagian 1
-- merapikan spasi, yang tersisa di sini adalah 6 baris arsip pada 3 OPD,
-- dan dari seluruh 115 baris LAKIP yang sudah berisi capaian, HANYA SATU
-- yang labelnya berubah:
--
--     LAKIP #183 — Dukcapil, tahun 2025, capaian 100, status SELESAI
--     "Persentase Kepemilikan Akta Kematian yang dilaporkan"
--       -> "Persentase Penerbitan Akta Kematian bagi yang melapor"
--
-- Perhatikan statusnya: SELESAI, bukan draft. Jadi ini benar-benar menulis
-- ulang label pada satu baris laporan yang sudah dinyatakan rampung. Tidak
-- ada LAKIP yang dikunci versi (`lakip_version_id` NULL pada seluruh 150
-- baris), jadi secara teknis tidak ada segel yang dilanggar — tetapi
-- keputusannya tetap milik Anda, bukan saya.
--
-- Angka capaiannya TIDAK berubah, dan tetap menempel pada indikator yang
-- sama — realisasi menyambung lewat `ri.sumber_indikator_id`, yang tidak
-- disentuh. Yang berubah hanya bunyi labelnya.
--
-- Bagian ini AKTIF. Kalau Anda memilih arsip tetap memuat redaksi lama
-- sebagaimana saat dibekukan, beri tanda -- pada kedua perintah di bawah.
-- Konsekuensinya LAKIP 2025/2026 terus memakai kata lama, sementara IKU
-- dan Pohon Kinerja sudah memakai kata Renstra.
-- =====================================================================

-- 3a. Sasaran pada arsip.
UPDATE `iku_revisi_sasaran` ars
  JOIN `iku_revisi` r ON r.id = ars.revisi_id
  JOIN `iku_sasaran` liv ON liv.id = ars.sumber_sasaran_id
  JOIN `renstra_sasaran` rs ON rs.id = liv.source_sasaran_id
   SET ars.sasaran    = rs.sasaran,
       ars.updated_at = NOW()
 WHERE r.status IN ('berlaku', 'superseded')
   AND rs.dihentikan_pada IS NULL
   AND ars.sasaran <> rs.sasaran;

-- 3b. Indikator pada arsip. `satuan_nama` dibiarkan apa adanya — ia nama
--     satuan yang dibekukan, bukan redaksi indikator.
UPDATE `iku_revisi_indikator` ars
  JOIN `iku_revisi` r ON r.id = ars.revisi_id
  JOIN `iku_indikator` liv ON liv.id = ars.sumber_indikator_id
  JOIN `renstra_indikator_sasaran` ris ON ris.id = liv.source_indikator_id
   SET ars.indikator  = ris.indikator_sasaran,
       ars.updated_at = NOW()
 WHERE r.status IN ('berlaku', 'superseded')
   AND ris.dihentikan_pada IS NULL
   AND ars.indikator <> ris.indikator_sasaran;


-- =====================================================================
-- PEMERIKSAAN
-- =====================================================================

SELECT '=== SESUDAH: ketidakcocokan tersisa (semua harus 0) ===' AS laporan;
SELECT 'sasaran' AS bagian, COUNT(*) AS sisa FROM `iku_sasaran` s
  JOIN `renstra_sasaran` rs ON rs.id = s.source_sasaran_id
 WHERE s.dihentikan_pada IS NULL AND rs.dihentikan_pada IS NULL AND s.sasaran <> rs.sasaran
UNION ALL
SELECT 'indikator', COUNT(*) FROM `iku_indikator` i
  JOIN `renstra_indikator_sasaran` ris ON ris.id = i.source_indikator_id
 WHERE i.dihentikan_pada IS NULL AND ris.dihentikan_pada IS NULL
   AND i.indikator <> ris.indikator_sasaran
UNION ALL
SELECT 'arsip revisi', COUNT(*) FROM `iku_revisi_indikator` ri
  JOIN `iku_revisi` r ON r.id = ri.revisi_id AND r.status IN ('berlaku','superseded')
  JOIN `iku_indikator` i ON i.id = ri.sumber_indikator_id
  JOIN `renstra_indikator_sasaran` ris ON ris.id = i.source_indikator_id AND ris.dihentikan_pada IS NULL
 WHERE ri.indikator <> ris.indikator_sasaran;

SELECT '=== realisasi LAKIP harus utuh: tidak ada yang hilang ===' AS laporan;
SELECT COUNT(*) AS lakip_total,
       SUM(CASE WHEN TRIM(COALESCE(capaian_tahun_ini,'')) <> '' OR target_hitung IS NOT NULL
                THEN 1 ELSE 0 END) AS lakip_berisi
FROM `lakip`;

SELECT '=== jangkar LAKIP harus tetap tersambung ===' AS laporan;
SELECT COUNT(*) AS lakip_iku_tanpa_indikator
FROM `lakip` l
WHERE l.source_type = 'iku'
  AND l.source_entity_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `iku_indikator` i WHERE i.id = l.source_entity_id);

SELECT '=== spasi berlebih yang masih tersisa (harus 0) ===' AS laporan;
SELECT 'renstra_sasaran' AS tabel, COUNT(*) AS sisa FROM `renstra_sasaran`
 WHERE `sasaran` <> TRIM(REGEXP_REPLACE(`sasaran`, '[[:space:]]+', ' '))
UNION ALL
SELECT 'renstra_indikator', COUNT(*) FROM `renstra_indikator_sasaran`
 WHERE `indikator_sasaran` <> TRIM(REGEXP_REPLACE(`indikator_sasaran`, '[[:space:]]+', ' '))
UNION ALL
SELECT 'iku_sasaran', COUNT(*) FROM `iku_sasaran`
 WHERE `sasaran` <> TRIM(REGEXP_REPLACE(`sasaran`, '[[:space:]]+', ' '))
UNION ALL
SELECT 'iku_indikator', COUNT(*) FROM `iku_indikator`
 WHERE `indikator` <> TRIM(REGEXP_REPLACE(`indikator`, '[[:space:]]+', ' '));
