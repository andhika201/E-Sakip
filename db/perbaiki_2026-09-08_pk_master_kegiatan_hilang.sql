-- =====================================================================
-- PK: KEGIATAN / SUB KEGIATAN TAMPIL KOSONG (nama hilang, Rp 0)
-- Tanggal : 2026-09-08
-- Gejala  : /adminopd/pk/pengawas?tahun=2026&pk_id=83 -> baris "KEGIATAN:"
--           tanpa nama, sub kegiatan tanpa nama, ANGGARAN Rp 0.
--
-- SEBAB (bukan bug tampilan):
--   `pk_kegiatan.kegiatan_id` dan `pk_subkegiatan.subkegiatan_id` TIDAK punya
--   foreign key, jadi id-nya boleh menggantung. Sebaliknya `kegiatan_pk`
--   ber-FK ON DELETE CASCADE ke `program_pk`, dan `sub_kegiatan_pk` cascade ke
--   `kegiatan_pk`. Ketika master program diganti/di-impor ulang (blok besar
--   pada 2026-07-14 03:18), baris master lama ikut terhapus berantai dan
--   rujukan di pk_* jadi yatim. Query PK memakai LEFT JOIN, sehingga barisnya
--   tetap muncul tapi kolom nama NULL dan anggaran 0.
--
--   Pembersihan pemakaian pk_* saat master dihapus baru masuk di commit
--   36a850a (14 Juli 2026) lewat deletePkKegiatanUsageByKegiatanIds() /
--   deletePkSubkegiatanUsageBySubIds(). Jadi ini KERUSAKAN WARISAN dari
--   sebelum tambalan itu, bukan bug yang masih aktif.
--
-- DASAR PEMETAAN PK 83 (Dishub, program_pk id 70)
--   Blok master lama program 70 = id 176..183 (8 baris; id 175 milik program
--   69, id 184 milik program 71). Blok master barunya = id 635..642, juga 8
--   baris, urutan kanonik yang sama -> selisih tetap +459.
--
--   Bukti silangnya dari id sub kegiatan (selisih tetap +1291). Rentang sub
--   lama yang terpakai jatuh PERSIS di dalam rentang induk barunya, dan
--   jumlahnya cocok satu per satu:
--     kegiatan lama 178 -> 637  sub {692,693}       -> {1983,1984}
--     kegiatan lama 179 -> 638  sub {695}           -> {1986}
--     kegiatan lama 180 -> 639  sub {696..700} (5)  -> {1987..1991} (5)
--     kegiatan lama 182 -> 641  sub {703,704}       -> {1994,1995}
--     kegiatan lama 183 -> 642  sub {705..708} (4)  -> {1996..1999} (4)
--   14 dari 14 sub kegiatan mendarat di induk yang benar.
--
-- LINGKUP: skrip ini HANYA memperbaiki PK 83. Lima baris sisanya (PK 8, 252,
-- 342, 389) sengaja TIDAK ditebak - lihat bagian 4.
-- =====================================================================


-- ---- 0. PRATINJAU: semua rujukan yatim di PK yang masih hidup --------
SELECT  p.id                AS pk_id,
        p.jenis,
        o.nama_opd,
        p.tahun,
        kk.id               AS pk_kegiatan_id,
        kk.kegiatan_id      AS id_master_hilang,
        pp.program_id       AS program_induk,
        (SELECT COUNT(*) FROM pk_subkegiatan s WHERE s.pk_kegiatan_id = kk.id) AS sub_ikut
FROM        pk_kegiatan   kk
LEFT JOIN   kegiatan_pk   kp ON kp.id = kk.kegiatan_id
LEFT JOIN   pk_program    pp ON pp.id = kk.pk_program_id
LEFT JOIN   pk_indikator  pi ON pi.id = pp.pk_indikator_id
LEFT JOIN   pk_sasaran    ps ON ps.id = pi.pk_sasaran_id
LEFT JOIN   pk            p  ON p.id  = ps.pk_id
LEFT JOIN   opd           o  ON o.id  = p.opd_id
WHERE  kp.id IS NULL
  AND  kk.kegiatan_id IS NOT NULL
  AND  ps.pk_id IS NOT NULL
ORDER BY p.id, kk.kegiatan_id;


-- ---- 1. ARSIP: salinan nilai lama sebelum diubah ---------------------
CREATE TABLE IF NOT EXISTS pk_master_hilang_arsip_20260908 (
    arsip_id      INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tabel         VARCHAR(20)  NOT NULL COMMENT 'pk_kegiatan | pk_subkegiatan',
    baris_id      INT UNSIGNED NOT NULL COMMENT 'id baris di tabel asalnya',
    nilai_lama    INT UNSIGNED NULL     COMMENT 'kegiatan_id / subkegiatan_id sebelum diperbaiki',
    nilai_baru    INT UNSIGNED NULL     COMMENT 'nilai penggantinya',
    catatan       VARCHAR(255) NULL,
    diarsipkan_pada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO pk_master_hilang_arsip_20260908 (tabel, baris_id, nilai_lama, nilai_baru, catatan)
SELECT 'pk_kegiatan', kk.id, kk.kegiatan_id, kk.kegiatan_id + 459,
       'PK 83 Dishub - program 70, blok master 176..183 -> 635..642'
FROM pk_kegiatan kk
WHERE kk.id IN (215, 216, 217, 218, 219)
  AND kk.kegiatan_id IN (178, 179, 180, 182, 183);

INSERT INTO pk_master_hilang_arsip_20260908 (tabel, baris_id, nilai_lama, nilai_baru, catatan)
SELECT 'pk_subkegiatan', psk.id, psk.subkegiatan_id, psk.subkegiatan_id + 1291,
       'PK 83 Dishub - sub kegiatan mengikuti induknya'
FROM pk_subkegiatan psk
WHERE psk.pk_kegiatan_id IN (215, 216, 217, 218, 219)
  AND psk.subkegiatan_id BETWEEN 692 AND 708;


-- ---- 2. PERBAIKAN PK 83: kegiatan -----------------------------------
-- Pemetaan ditulis eksplisit (bukan aritmetika buta) dan di-JOIN ke master
-- tujuan, sehingga otomatis jadi no-op kalau di server nilainya sudah lain.
UPDATE pk_kegiatan kk
JOIN (
              SELECT 215 AS pk_keg, 178 AS lama, 637 AS baru
    UNION ALL SELECT 216,           179,          638
    UNION ALL SELECT 217,           180,          639
    UNION ALL SELECT 218,           182,          641
    UNION ALL SELECT 219,           183,          642
) m  ON m.pk_keg = kk.id AND kk.kegiatan_id = m.lama
JOIN kegiatan_pk kp ON kp.id = m.baru AND kp.program_id = 70
SET  kk.kegiatan_id = m.baru;


-- ---- 3. PERBAIKAN PK 83: sub kegiatan --------------------------------
-- Syarat aman: sub pengganti HARUS bernaung di bawah induk yang sudah
-- diperbaiki pada langkah 2. Kalau tidak, baris dilewati.
UPDATE pk_subkegiatan psk
JOIN      pk_kegiatan     kk   ON kk.id = psk.pk_kegiatan_id
JOIN      sub_kegiatan_pk s    ON s.id  = psk.subkegiatan_id + 1291
                              AND s.kegiatan_id = kk.kegiatan_id
LEFT JOIN sub_kegiatan_pk lama ON lama.id = psk.subkegiatan_id
SET  psk.subkegiatan_id = psk.subkegiatan_id + 1291
WHERE psk.pk_kegiatan_id IN (215, 216, 217, 218, 219)
  AND psk.subkegiatan_id BETWEEN 692 AND 708
  AND lama.id IS NULL;               -- hanya yang memang yatim


-- ---- 4. SISA YANG TIDAK DITEBAK (wajib dipilih ulang manual) ---------
-- PK 8 & 252  (Setda, program_pk 120)
--     Blok master lama hanya 2 id (516,517) sedangkan blok master barunya 3
--     baris (550 Fasilitasi Kerjasama Daerah / 551 Pelaksanaan Kebijakan
--     Kesejahteraan Rakyat / 552 Fasilitasi dan Koordinasi Hukum). Jumlahnya
--     tidak sama, jadi urutannya tidak bisa dipakai. Menebak di sini berisiko
--     menempelkan nama kegiatan yang salah ke dokumen PK.
--     -> Petunjuk kuat dari jabatan penanda tangannya:
--        PK 8   = Kepala Bagian Hukum               -> kemungkinan besar 552
--        PK 252 = Kepala Bagian Kesejahteraan Rakyat -> kemungkinan besar 551
--        Tetap harus dikonfirmasi Setda sebelum ditulis.
--
-- PK 342 (Diskominfo, master hilang 190,191) dan PK 389 (DP3AP2KB, master
--     hilang 171): program induknya pun kosong (pk_program.program_id = 0),
--     dan blok master lamanya tidak punya blok pengganti yang bisa
--     disejajarkan. -> Harus dipilih ulang lewat tombol Edit pada PK-nya.
--
-- Daftar kerjanya:
SELECT  p.id AS pk_id, o.nama_opd, p.tahun,
        peg.nama_pegawai AS pihak_pertama,
        kk.id AS pk_kegiatan_id, kk.kegiatan_id AS id_master_hilang
FROM        pk_kegiatan  kk
LEFT JOIN   kegiatan_pk  kp  ON kp.id = kk.kegiatan_id
LEFT JOIN   pk_program   pp  ON pp.id = kk.pk_program_id
LEFT JOIN   pk_indikator pi  ON pi.id = pp.pk_indikator_id
LEFT JOIN   pk_sasaran   ps  ON ps.id = pi.pk_sasaran_id
LEFT JOIN   pk           p   ON p.id  = ps.pk_id
LEFT JOIN   opd          o   ON o.id  = p.opd_id
LEFT JOIN   pegawai      peg ON peg.id = p.pihak_1
WHERE  kp.id IS NULL AND kk.kegiatan_id IS NOT NULL AND ps.pk_id IS NOT NULL
ORDER BY p.id;


-- ---- 5. VERIFIKASI: PK 83 harus bersih -------------------------------
SELECT  kk.id AS pk_kegiatan_id,
        LEFT(kp.kegiatan, 60) AS kegiatan,
        kp.anggaran           AS anggaran_kegiatan,
        (SELECT COUNT(*) FROM pk_subkegiatan s
           JOIN sub_kegiatan_pk sm ON sm.id = s.subkegiatan_id
          WHERE s.pk_kegiatan_id = kk.id) AS sub_tampil,
        (SELECT COUNT(*) FROM pk_subkegiatan s
          WHERE s.pk_kegiatan_id = kk.id) AS sub_total
FROM pk_kegiatan kk
LEFT JOIN kegiatan_pk kp ON kp.id = kk.kegiatan_id
WHERE kk.id IN (215, 216, 217, 218, 219)
ORDER BY kk.id;


-- =====================================================================
-- 6. OPSIONAL - SAMPAH YANG TIDAK TAMPIL DI MANA PUN
--
-- Selain 10 baris di atas masih ada rujukan yatim yang rantai PK-nya sudah
-- putus (pk_sasaran/pk_indikator induknya hilang), sehingga tidak pernah
-- muncul di layar mana pun: ~58 baris pk_kegiatan, ~34 pk_subkegiatan, dan
-- ~200 pk_program. Sifatnya murni sampah, TAPI penghapusan tidak bisa
-- dibatalkan - jalankan hanya setelah hitungannya dicek di server.
--
-- Hitung dulu:
--   SELECT COUNT(*) FROM pk_kegiatan kk
--   LEFT JOIN pk_program pp ON pp.id = kk.pk_program_id
--   LEFT JOIN pk_indikator pi ON pi.id = pp.pk_indikator_id
--   LEFT JOIN pk_sasaran ps ON ps.id = pi.pk_sasaran_id
--   WHERE ps.pk_id IS NULL;
--
-- Baru hapus (buka komentarnya bila angkanya sudah cocok):
--   DELETE psk FROM pk_subkegiatan psk
--   LEFT JOIN pk_kegiatan kk ON kk.id = psk.pk_kegiatan_id
--   LEFT JOIN pk_program pp ON pp.id = kk.pk_program_id
--   LEFT JOIN pk_indikator pi ON pi.id = pp.pk_indikator_id
--   LEFT JOIN pk_sasaran ps ON ps.id = pi.pk_sasaran_id
--   WHERE ps.pk_id IS NULL;
--
--   DELETE kk FROM pk_kegiatan kk
--   LEFT JOIN pk_program pp ON pp.id = kk.pk_program_id
--   LEFT JOIN pk_indikator pi ON pi.id = pp.pk_indikator_id
--   LEFT JOIN pk_sasaran ps ON ps.id = pi.pk_sasaran_id
--   WHERE ps.pk_id IS NULL;
--
--   DELETE pp FROM pk_program pp
--   LEFT JOIN pk_indikator pi ON pi.id = pp.pk_indikator_id
--   LEFT JOIN pk_sasaran ps ON ps.id = pi.pk_sasaran_id
--   WHERE ps.pk_id IS NULL;
-- =====================================================================
