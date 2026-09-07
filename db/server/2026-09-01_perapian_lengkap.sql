-- =====================================================================
-- PERAPIAN LENGKAP UNTUK SERVER — 1 September 2026
-- Dijalankan lewat phpMyAdmin. Tidak butuh CLI.
--
-- Disusun dan DIUJI terhadap salinan `test.sql` (dump server 1 Sep 2026),
-- jadi angka-angka pada bagian pemeriksaan di bawah memang angka server
-- Anda, bukan perkiraan.
--
-- Catatan: skema server ternyata lebih maju daripada dump itu — sebagian
-- kolom sudah Anda pasang setelah dump diambil. Karena itu berkas ini
-- tidak lagi menebak kolom mana yang sudah ada; lihat Bagian 4.
--
-- CARA MENJALANKAN: tempel seluruh berkas sekaligus.
--
-- phpMyAdmin berhenti di galat pertama, jadi seluruh perintah yang BISA
-- menerbitkan galat (ALTER TABLE) sengaja ditaruh di BAGIAN 4, paling
-- belakang. Bagian 0 sampai 3 tidak memuat DDL sama sekali dan aman
-- diulang berapa kali pun.
--
-- Artinya: kalau Bagian 4 berakhir merah dengan #1060 "Duplicate column",
-- itu TIDAK APA-APA — kolomnya memang sudah ada, dan seluruh perapian di
-- Bagian 0-3 sudah terlanjur berhasil. Tidak ada yang perlu diulang.
--
-- CADANGKAN DULU. Tanpa itu tidak ada jalan mundur.
--     mysqldump -u USER -p NAMA_DB > sebelum_perapian_1sep.sql
-- =====================================================================


-- =====================================================================
-- BAGIAN 0 — SUSULAN DATA
--
-- Dua perintah ini aman diulang berapa kali pun; bila sudah pernah
-- dijalankan, tidak ada satu baris pun yang berubah.
-- =====================================================================

-- 0a. Turunkan tujuan Renstra bagi arsip revisi yang dibekukan sebelum
--     kolom `renstra_tujuan_id` ada.
UPDATE `iku_revisi_sasaran` ars
  JOIN `iku_sasaran` liv ON liv.id = ars.sumber_sasaran_id
   SET ars.renstra_tujuan_id = liv.renstra_tujuan_id
 WHERE ars.renstra_tujuan_id IS NULL
   AND liv.renstra_tujuan_id IS NOT NULL;

-- 0b. Pastikan tiap permohonan izin punya jenis.
UPDATE `dokumen_izin_sunting` SET `jenis` = 'sunting'
 WHERE `jenis` IS NULL OR `jenis` = '';


-- =====================================================================
-- BAGIAN 1 — BASELINE IKU KABUPATEN
--
-- Lingkup KABUPATEN ditandai `iku_sasaran.opd_id IS NULL`, dan
-- `iku_revisi.opd_key` menjadi 0 untuknya (kolom generated).
--
-- Skrip massal terdahulu (2026-08-30_D) menyaring `s.opd_id IS NOT NULL`,
-- sehingga kabupaten TIDAK PERNAH ikut dibekukan. Akibatnya `opd_key = 0`
-- tidak punya satu pun revisi, dan karena LakipSourceService mencari revisi
-- ber-opd_key 0 untuk mode kabupaten, LAKIP Kabupaten selamanya jatuh ke
-- RPJMD — IKU Kabupaten tersusun lengkap tapi tidak pernah bisa dinilai.
--
-- Pada dump server 1 Sep: `SELECT COUNT(*) FROM iku_revisi WHERE opd_key=0`
-- menghasilkan 0. Bagian ini yang mengisinya.
--
-- Aman diulang: tiap langkah menolak bekerja bila hasilnya sudah ada.
-- =====================================================================

SELECT '--- SEBELUM: revisi kabupaten ---' AS laporan;
SELECT COUNT(*) AS revisi_kabupaten FROM `iku_revisi` WHERE `opd_key` = 0;

-- 1a. Kepala revisi. `opd_key` & `berlaku_key` generated: JANGAN diisi.
INSERT INTO `iku_revisi`
    (`opd_id`, `tahun_mulai`, `tahun_akhir`, `nomor`, `nama`,
     `berlaku_mulai_tahun`, `status`, `catatan`, `disahkan_pada`, `dibekukan_pada`)
SELECT DISTINCT
    NULL, s.tahun_mulai, s.tahun_akhir, 0,
    CONCAT('Kondisi Awal IKU ', s.tahun_mulai, '-', s.tahun_akhir),
    s.tahun_mulai, 'berlaku',
    'Dibekukan otomatis dari kondisi IKU Kabupaten yang berlaku.',
    NOW(), NOW()
FROM `iku_sasaran` s
JOIN `iku_indikator` i ON i.iku_sasaran_id = s.id AND i.dihentikan_pada IS NULL
WHERE s.opd_id IS NULL
  AND s.dihentikan_pada IS NULL
  AND NOT EXISTS (SELECT 1 FROM `iku_revisi` r
                   WHERE r.opd_key = 0
                     AND r.tahun_mulai = s.tahun_mulai
                     AND r.tahun_akhir = s.tahun_akhir);

-- 1b. Bekukan sasaran kabupaten ke arsipnya.
INSERT INTO `iku_revisi_sasaran`
    (`revisi_id`, `sumber_sasaran_id`, `source_type`, `source_version_id`,
     `source_ref_id`, `sasaran`, `tahun_mulai`, `tahun_akhir`, `urutan`,
     `jenis_perubahan`, `created_at`, `updated_at`)
SELECT r.id, s.id, s.source_type, s.source_version_id, s.source_sasaran_id,
       s.sasaran, s.tahun_mulai, s.tahun_akhir, s.urutan,
       'tetap', NOW(), NOW()
FROM `iku_revisi` r
JOIN `iku_sasaran` s
  ON s.opd_id IS NULL
 AND s.tahun_mulai = r.tahun_mulai
 AND s.tahun_akhir = r.tahun_akhir
 AND s.dihentikan_pada IS NULL
WHERE r.nomor = 0
  AND r.opd_key = 0
  AND NOT EXISTS (SELECT 1 FROM `iku_revisi_sasaran` x WHERE x.revisi_id = r.id)
ORDER BY r.id, s.urutan, s.id;

-- 1c. Bekukan indikatornya. `satuan_nama` ikut dibekukan supaya arsip tetap
--     berbunyi benar walau master `satuan` kelak diganti namanya.
INSERT INTO `iku_revisi_indikator`
    (`revisi_id`, `revisi_sasaran_id`, `sumber_indikator_id`, `source_type`,
     `source_version_id`, `source_ref_id`, `indikator`, `definisi`,
     `rumusan_perhitungan`, `satuan`, `satuan_nama`, `sumber_data`,
     `penanggung_jawab`, `jenis_indikator`, `baseline`, `urutan`, `status`,
     `jenis_perubahan`, `perubahan_substansial`, `created_at`, `updated_at`)
SELECT rs.revisi_id, rs.id, i.id, i.source_type, i.source_version_id,
       i.source_indikator_id, i.indikator, i.definisi, i.rumusan_perhitungan,
       i.satuan,
       COALESCE(sa.satuan, NULLIF(i.satuan, '')),
       i.sumber_data, i.penanggung_jawab, i.jenis_indikator, i.baseline,
       i.urutan, COALESCE(i.status, 'draft'), 'tetap', 0, NOW(), NOW()
FROM `iku_revisi_sasaran` rs
JOIN `iku_revisi` r ON r.id = rs.revisi_id AND r.opd_key = 0 AND r.nomor = 0
JOIN `iku_indikator` i
  ON i.iku_sasaran_id = rs.sumber_sasaran_id AND i.dihentikan_pada IS NULL
LEFT JOIN `satuan` sa ON sa.id = i.satuan
WHERE NOT EXISTS (SELECT 1 FROM `iku_revisi_indikator` y WHERE y.revisi_sasaran_id = rs.id)
ORDER BY rs.id, i.urutan, i.id;

-- 1d. Bekukan target tahunannya.
INSERT INTO `iku_revisi_target`
    (`revisi_indikator_id`, `tahun`, `target`, `created_at`, `updated_at`)
SELECT ri.id, t.tahun, t.target, NOW(), NOW()
FROM `iku_revisi_indikator` ri
JOIN `iku_revisi` r ON r.id = ri.revisi_id AND r.opd_key = 0 AND r.nomor = 0
JOIN `iku_target` t ON t.iku_indikator_id = ri.sumber_indikator_id
WHERE NOT EXISTS (SELECT 1 FROM `iku_revisi_target` y
                   WHERE y.revisi_indikator_id = ri.id AND y.tahun = t.tahun);

SELECT '--- SESUDAH: revisi kabupaten ---' AS laporan;
SELECT r.id, r.tahun_mulai, r.tahun_akhir, r.status,
       (SELECT COUNT(*) FROM `iku_revisi_sasaran` x WHERE x.revisi_id = r.id) AS sasaran,
       (SELECT COUNT(*) FROM `iku_revisi_indikator` x WHERE x.revisi_id = r.id) AS indikator
FROM `iku_revisi` r WHERE r.opd_key = 0;


-- =====================================================================
-- BAGIAN 2 — SASARAN KEMBAR DI DALAM DRAFT REVISI
--
-- Sampai perbaikan kode 1 Sep, sync mencocokkan sasaran yang sudah ada di
-- draft HANYA lewat teksnya. Begitu redaksi sasaran di Renstra disunting,
-- teksnya tidak lagi sama, sync tidak menemukan padanannya, lalu menyisipkan
-- baris KEDUA untuk sasaran Renstra yang sama.
--
-- Cirinya pasti: dua baris `iku_revisi_sasaran` dalam satu revisi dengan
-- `source_ref_id` yang sama.
--
-- Pada dump server: revisi 72 dan 73 (Koperindag), silsilah #97 —
-- "Meningkatnya Daya Saing Koperasi" vs "…Koperasi dan UMKM".
--
-- DIGABUNG, BUKAN DIHAPUS. Baris kembar itu tidak kosong: ia justru memuat
-- indikator yang belum ada di baris aslinya (di sini: "Proporsi UKM Menjalin
-- Kemitraan dan Ekspor"). Menghapusnya begitu saja membuang indikator itu
-- diam-diam.
-- =====================================================================

SELECT '--- SEBELUM: sasaran kembar ---' AS laporan;
SELECT revisi_id, source_ref_id, COUNT(*) AS baris
FROM `iku_revisi_sasaran`
WHERE source_ref_id IS NOT NULL
GROUP BY revisi_id, source_ref_id HAVING COUNT(*) > 1;

-- 2a. Buang indikator kembar yang SUDAH punya padanan di baris yang
--     dipertahankan — itu memang salinan, bukan tambahan.
DELETE ri FROM `iku_revisi_indikator` ri
JOIN `iku_revisi_sasaran` kembar ON kembar.id = ri.revisi_sasaran_id
JOIN `iku_revisi_sasaran` simpan
     ON simpan.revisi_id     = kembar.revisi_id
    AND simpan.source_ref_id = kembar.source_ref_id
    AND simpan.sumber_sasaran_id IS NOT NULL
JOIN (SELECT x.revisi_sasaran_id, x.source_ref_id,
             LOWER(TRIM(REGEXP_REPLACE(x.indikator, '[[:space:]]+', ' '))) AS kunci
        FROM `iku_revisi_indikator` x) ada
     ON ada.revisi_sasaran_id = simpan.id
    AND (ada.source_ref_id = ri.source_ref_id
         OR ada.kunci = LOWER(TRIM(REGEXP_REPLACE(ri.indikator, '[[:space:]]+', ' '))))
WHERE kembar.sumber_sasaran_id IS NULL
  AND kembar.source_ref_id IS NOT NULL;

-- 2b. Pindahkan sisanya ke baris yang dipertahankan. Targetnya ikut, karena
--     baris indikatornya hanya BERPINDAH INDUK, tidak dibuat ulang.
UPDATE `iku_revisi_indikator` ri
JOIN `iku_revisi_sasaran` kembar ON kembar.id = ri.revisi_sasaran_id
JOIN `iku_revisi_sasaran` simpan
     ON simpan.revisi_id     = kembar.revisi_id
    AND simpan.source_ref_id = kembar.source_ref_id
    AND simpan.sumber_sasaran_id IS NOT NULL
   SET ri.revisi_sasaran_id = simpan.id,
       ri.updated_at        = NOW()
 WHERE kembar.sumber_sasaran_id IS NULL
   AND kembar.source_ref_id IS NOT NULL;

-- 2c. Baris kembar yang SUDAH KOSONG barulah dibuang.
DELETE kembar FROM `iku_revisi_sasaran` kembar
JOIN `iku_revisi_sasaran` simpan
     ON simpan.revisi_id     = kembar.revisi_id
    AND simpan.source_ref_id = kembar.source_ref_id
    AND simpan.sumber_sasaran_id IS NOT NULL
WHERE kembar.sumber_sasaran_id IS NULL
  AND kembar.source_ref_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM (SELECT revisi_sasaran_id FROM `iku_revisi_indikator`) z
                   WHERE z.revisi_sasaran_id = kembar.id);

SELECT '--- SESUDAH: sasaran kembar (harus kosong) ---' AS laporan;
SELECT revisi_id, source_ref_id, COUNT(*) AS baris
FROM `iku_revisi_sasaran`
WHERE source_ref_id IS NOT NULL
GROUP BY revisi_id, source_ref_id HAVING COUNT(*) > 1;


-- =====================================================================
-- BAGIAN 3 — SASARAN IKU YANG MELAYANG DI POHON KINERJA
--
-- Gejalanya: cabang terpisah berjudul "(Tanpa Tujuan RPJMD)" /
-- "(Tanpa Sasaran RPJMD)" / "(Tanpa Tujuan Renstra)".
--
-- Sebabnya: rantai IKU -> Renstra -> RPJMD buntu di ketiga jalannya
-- (silsilah indikator, silsilah sasaran, dan tujuan mandiri).
--
-- Pada dump server ada TIGA, dan penanganannya BERBEDA — sengaja tidak
-- disamakan, karena yang menempel padanya berbeda.
-- =====================================================================

SELECT '--- yang melayang beserta yang menempel padanya ---' AS laporan;
SELECT s.id AS sasaran, LEFT(s.sasaran, 40) AS nama,
       (SELECT COUNT(*) FROM `iku_indikator` i
         WHERE i.iku_sasaran_id = s.id AND i.dihentikan_pada IS NULL) AS indikator,
       (SELECT COUNT(*) FROM `cascading_sasaran_opd` c
          JOIN `iku_indikator` i2 ON i2.id = c.iku_indikator_id
         WHERE i2.iku_sasaran_id = s.id) AS cascading,
       (SELECT COUNT(*) FROM `lakip` l
          JOIN `iku_indikator` i3 ON i3.id = l.source_entity_id
         WHERE l.source_type = 'iku' AND i3.iku_sasaran_id = s.id) AS lakip
FROM `iku_sasaran` s
WHERE s.dihentikan_pada IS NULL AND s.opd_id IS NOT NULL
  AND s.renstra_tujuan_id IS NULL
  AND NOT EXISTS (SELECT 1 FROM `renstra_sasaran` rs
                    JOIN `renstra_tujuan` rt ON rt.id = rs.renstra_tujuan_id
                   WHERE rs.id = s.source_sasaran_id)
  AND NOT EXISTS (SELECT 1 FROM `iku_indikator` i
                    JOIN `renstra_indikator_sasaran` ris ON ris.id = i.source_indikator_id
                    JOIN `renstra_sasaran` rs2 ON rs2.id = ris.renstra_sasaran_id
                    JOIN `renstra_tujuan` rt2 ON rt2.id = rs2.renstra_tujuan_id
                   WHERE i.iku_sasaran_id = s.id AND i.dihentikan_pada IS NULL);


-- ---------------------------------------------------------------------
-- 3a. KECAMATAN PRINGSEWU (#46, #47)
--
-- Keduanya tidak pernah bersilsilah, dan pada dump server TIDAK ADA
-- cascading maupun realisasi LAKIP yang menempel padanya. Yang hilang bila
-- dihapus hanyalah 3 indikator beserta 15 target tahunannya.
--
-- RALAT ATAS SARAN SAYA SEBELUMNYA. Saya sempat menulis bahwa datanya aman
-- dihapus karena "user tinggal Sync ulang dari Renstra". Itu KELIRU untuk
-- OPD ini, dan saya memeriksanya belakangan:
--
--   `versiRenstraTersedia()` hanya menawarkan versi Renstra yang isinya
--   sudah TERARSIP di `renstra_versi_sasaran`. Di server, tabel itu cuma
--   berisi 4 baris untuk SATU versi (Koperindag #25). 37 versi Renstra
--   lain berstatus published tanpa arsip isi — termasuk kedua versi milik
--   Kecamatan Pringsewu (#34 dan #35).
--
--   Akibatnya tombol "Sekalian salin isi Renstra" TIDAK AKAN MUNCUL bagi
--   Kecamatan Pringsewu. Menghapus #46/#47 berarti operatornya harus
--   mengetik ulang 3 indikator dan 15 target itu satu per satu.
--
-- (Ini keadaan lama, bukan kerusakan: basis data lokal pun hanya punya 2
--  baris arsip. Versi yang disahkan sebelum fitur pengarsipan ada memang
--  tidak menyimpan isinya.)
--
-- Karena itu ADA DUA PILIHAN. Yang aktif di bawah adalah PILIHAN 1, sesuai
-- keputusan Anda "kalau aman dihapus, mending dihapus saja" — sekarang
-- dengan konsekuensinya yang sebenarnya.
--
-- PERIKSA DULU angka `cascading` dan `lakip` pada tabel di atas. Bila
-- salah satunya TIDAK NOL, JANGAN jalankan blok ini — hubungi saya.
--
-- ......................................................................
-- PILIHAN 1 (AKTIF) — HAPUS, isi disusun ulang MANUAL oleh operator.
-- ......................................................................

-- Putuskan jejak arsip lebih dulu supaya tidak menunjuk baris yang tiada.
UPDATE `iku_revisi_indikator` ri
  JOIN `iku_indikator` i ON i.id = ri.sumber_indikator_id
   SET ri.sumber_indikator_id = NULL
 WHERE i.iku_sasaran_id IN (46, 47);

UPDATE `iku_revisi_sasaran` rs
   SET rs.sumber_sasaran_id = NULL
 WHERE rs.sumber_sasaran_id IN (46, 47);

-- Indikator & target ikut terhapus lewat foreign key ON DELETE CASCADE.
DELETE FROM `iku_sasaran` WHERE `id` IN (46, 47);

-- ......................................................................
-- PILIHAN 2 — PERTAHANKAN, cukup diberi Tujuan Renstra.
--
-- Indikator dan target tetap utuh, tidak ada yang perlu diketik ulang,
-- dan cabang melayangnya tetap hilang dari Pohon Kinerja. Bedanya dengan
-- Koperindag: Kecamatan Pringsewu punya LIMA tujuan, jadi pemasangannya
-- adalah keputusan perencanaan — saya tidak menebaknya untuk Anda.
--
--     id=94   Meningkatnya Ketentraman dan Ketertiban Umum
--     id=95   Meningkatnya indek Kepuasan Masyarakat
--     id=96   Meningkatnya Pemberdayaan Masyarakat Desa dan Kelurahan
--     id=106  Meningkatnya Kualitas Kinerja Aparatur Pemerintahan
--     id=114  Meningkatnya Pemberdayaan Lembaga Kemasyarakatan Desa
--
-- Untuk memakainya: beri tanda -- pada tiga perintah PILIHAN 1 di atas,
-- lalu buka tanda -- di bawah ini dan isi id tujuannya sesuai keputusan
-- Kecamatan Pringsewu.
--
-- UPDATE `iku_sasaran` SET `renstra_tujuan_id` = <id> WHERE `id` = 46;
-- UPDATE `iku_sasaran` SET `renstra_tujuan_id` = <id> WHERE `id` = 47;
-- ......................................................................


-- ---------------------------------------------------------------------
-- 3b. KOPERINDAG (#101) — JANGAN DIHAPUS
--
-- Indikatornya (#127) menjadi SATU-SATUNYA jangkar bagi 5 baris cascading:
--     Meningkatnya kapasitas SDM UMKM        (es3)
--     Terlaksanannya SDM UMKM berkualitas    (es4)
--     Meningkatnya akses pemasaran UMKM      (es3)
--     Terwujudnya akses pasar UMKM           (es4)
--     Terwujudnya UKM adaptif teknologi      (es4)
--
-- Kelimanya ber-`renstra_indikator_sasaran_id` NULL. Menghapus indikator
-- 127 membuat foreign key meng-NULL-kan jangkarnya juga, sehingga kelima
-- baris itu kehilangan SELURUH jangkarnya — barisnya selamat, tetapi tidak
-- lagi punya tempat di pohon mana pun. Itu justru lebih buruk daripada
-- keadaan sekarang, dan bertentangan dengan permintaan mempertahankan
-- cascading.
--
-- Karena itu cukup diberi rumah. Renstra Koperindag yang sudah disahkan
-- (dokumen_versi #25, V1) hanya punya SATU tujuan: id 47. Jadi tidak ada
-- yang perlu ditebak.
-- ---------------------------------------------------------------------
UPDATE `iku_sasaran`
   SET `renstra_tujuan_id` = 47,
       `source_sasaran_id` = NULL   -- #178 sudah dihapus; berhenti menunjuk ketiadaan
 WHERE `id` = 101;


SELECT '--- SESUDAH: yang masih melayang (harus kosong) ---' AS laporan;
SELECT COUNT(*) AS masih_melayang
FROM `iku_sasaran` s
WHERE s.dihentikan_pada IS NULL AND s.opd_id IS NOT NULL
  AND s.renstra_tujuan_id IS NULL
  AND NOT EXISTS (SELECT 1 FROM `renstra_sasaran` rs
                    JOIN `renstra_tujuan` rt ON rt.id = rs.renstra_tujuan_id
                   WHERE rs.id = s.source_sasaran_id)
  AND NOT EXISTS (SELECT 1 FROM `iku_indikator` i
                    JOIN `renstra_indikator_sasaran` ris ON ris.id = i.source_indikator_id
                    JOIN `renstra_sasaran` rs2 ON rs2.id = ris.renstra_sasaran_id
                    JOIN `renstra_tujuan` rt2 ON rt2.id = rs2.renstra_tujuan_id
                   WHERE i.iku_sasaran_id = s.id AND i.dihentikan_pada IS NULL);

SELECT '--- cascading harus TETAP utuh ---' AS laporan;
SELECT COUNT(*) AS cascading_koperindag_masih_berjangkar
FROM `cascading_sasaran_opd` WHERE `iku_indikator_id` = 127;


-- =====================================================================
-- BAGIAN 4 — SKEMA
--
-- SENGAJA PALING BELAKANG, dan BOLEH GAGAL.
--
-- MySQL tidak mengenal `ADD COLUMN IF NOT EXISTS`, dan cara memeriksanya
-- lewat `information_schema` tertutup untuk user basis data server ini
-- (#1044 Access denied). Jadi tidak ada cara menulis ALTER yang sekaligus
-- aman diulang. Yang bisa dilakukan adalah memastikan kegagalannya tidak
-- merugikan — itulah sebabnya ia ditaruh di sini, sesudah semua perapian
-- selesai.
--
-- #1060 Duplicate column name  ->  kolomnya sudah ada. Abaikan. Selesai.
-- Galat LAIN                   ->  hubungi saya.
-- =====================================================================

-- Satuan pada tiap sub rencana aksi.
ALTER TABLE `target_sub_rencana`
  ADD COLUMN `satuan` VARCHAR(50) NULL
      COMMENT 'nama satuan target triwulan sub ini; NULL = mengikuti satuan indikator'
      AFTER `sub_rencana_aksi`;
